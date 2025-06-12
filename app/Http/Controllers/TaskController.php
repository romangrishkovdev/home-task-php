<?php

namespace App\Http\Controllers;

use App\Services\RedisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Response;
use Ramsey\Uuid\Uuid;
use SplFileObject;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Post(
 *     path="/api/v1/task",
 *     summary="Upload CSV file",
 *     tags={"Tasks"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="csv",
 *                     type="string",
 *                     format="binary"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="CSV file uploaded and summary generated",
 *         @OA\JsonContent(
 *             @OA\Property(property="row_count", type="integer", example=100001),
 *             @OA\Property(property="column_count", type="integer", example=13),
 *             @OA\Property(property="headers", type="array", @OA\Items(type="string"), example={"index", "customer_id", "first_name"})
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid input",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Invalid file or headers missing")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Server error")
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/v1/task/{taskId}",
 *     summary="Get task status",
 *     tags={"Tasks"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="taskId",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Task completed",
 *         @OA\JsonContent(
 *             @OA\Property(property="row_count", type="integer"),
 *             @OA\Property(property="column_count", type="integer"),
 *             @OA\Property(property="headers", type="array", @OA\Items(type="string"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=202,
 *         description="Task processing",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Task not found"
 *     )
 * )
 */
class TaskController extends Controller
{
    private RedisService $redisService;

    public function __construct(RedisService $redisService)
    {
        $this->redisService = $redisService;
    }

    /**
     * Store a new CSV file for processing.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function upload(Request $request): JsonResponse
    {
        Log::info('CSV upload request received.');

        $validator = Validator::make($request->all(), [
            'csv' => 'required|file|mimes:csv|max:102400' // Max 100MB
        ]);

        if ($validator->fails()) {
            Log::warning('CSV upload validation failed.', ['errors' => $validator->errors()]);
            return new JsonResponse([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        $file = $request->file('csv');
        $taskId = Uuid::uuid4()->toString();
        $filename = $taskId . '.csv';
        $filePath = storage_path('app/uploads/' . $filename);

        try {
            // Save file temporarily
            $file->move(storage_path('app/uploads'), $filename);
            Log::info('CSV file saved temporarily.', ['file' => $filePath]);

            // Calculate statistical summary synchronously
            $fileObject = new SplFileObject($filePath, 'r');
            $fileObject->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);

            // Get headers
            $headers = $fileObject->current();
            if (empty($headers) || !is_array($headers) || count(array_filter($headers)) === 0) {
                unlink($filePath); // Clean up the uploaded file
                Log::warning('CSV file has missing or empty headers.', ['file' => $filePath]);
                return new JsonResponse([
                    'status' => false,
                    'message' => 'CSV file must have non-empty headers.'
                ], 400);
            }
            $headers = array_map('trim', $headers);
            $columnCount = count($headers);

            // Count total rows
            $fileObject->seek(PHP_INT_MAX); // Seek to the end of the file
            $rowCount = $fileObject->key() + 1; // key() returns 0-indexed last line, +1 for total count including header

            $summary = [
                'row_count' => $rowCount,
                'column_count' => $columnCount,
                'headers' => $headers,
                'status' => 'processing' // Initial status
            ];

            // Save summary to Redis with TTL
            $this->redisService->setTaskStatus($taskId, $summary, 3600); // 3600 seconds TTL
            Log::info('CSV summary saved to Redis.', ['taskId' => $taskId, 'summary' => $summary]);

            // Queue the taskId for asynchronous batch processing by the Worker
            $this->redisService->pushToQueue($taskId, $filename);
            Log::info('Task ID queued for processing.', ['taskId' => $taskId]);

            return new JsonResponse(array_merge(['task_id' => $taskId], $summary), 202);

        } catch (\Exception $e) {
            Log::error('Error processing CSV upload.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $filePath ?? 'N/A'
            ]);
            // Clean up the uploaded file if an error occurs
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return new JsonResponse([
                'status' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the status of a task.
     *
     * @param string $taskId
     * @return JsonResponse
     */
    public function status(string $taskId): JsonResponse
    {
        try {
            $result = $this->redisService->getTaskStatus($taskId);

            if (!$result) {
                return new JsonResponse([
                    'status' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            if (isset($result['status']) && $result['status'] === 'processing') {
                return new JsonResponse([
                    'status' => 'processing'
                ], 202);
            }

            return new JsonResponse($result, 200);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => false,
                'message' => 'Server error'
            ], 500);
        }
    }
} 