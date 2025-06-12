<?php

namespace App\Http\Controllers;

use App\Services\RedisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Get(
 *     path="/health",
 *     summary="Health check endpoint",
 *     tags={"System"},
 *     @OA\Response(
 *         response=200,
 *         description="Service is healthy",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Service is unhealthy",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string"),
 *             @OA\Property(property="message", type="string")
 *         )
 *     )
 * )
 */
class HealthController extends Controller
{
    private RedisService $redisService;

    public function __construct(RedisService $redisService)
    {
        $this->redisService = $redisService;
    }

    /**
     * Check the health of the application.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function check()
    {
        $accept = request()->header('Accept');
        try {
            $redis = $this->redisService->isAvailable();
            if (!$redis) {
                if (strpos($accept, 'text/html') !== false) {
                    return new \Illuminate\Http\Response(
                        '<!DOCTYPE html><html><head><title>Service Unavailable</title></head><body><h1>503 Service Unavailable</h1><p>Redis is not available</p></body></html>',
                        500,
                        ['Content-Type' => 'text/html']
                    );
                }
                return new \Illuminate\Http\JsonResponse(['status' => 'error', 'message' => 'Redis is not available'], 500);
            }
            if (strpos($accept, 'text/html') !== false) {
                return new \Illuminate\Http\Response(
                    '<!DOCTYPE html><html><head><title>OK</title></head><body><h1>200 OK</h1><p>Service is healthy</p></body></html>',
                    200,
                    ['Content-Type' => 'text/html']
                );
            }
            return new \Illuminate\Http\JsonResponse(['status' => 'ok'], 200);
        } catch (\Exception $e) {
            if (strpos($accept, 'text/html') !== false) {
                return new \Illuminate\Http\Response(
                    '<!DOCTYPE html><html><head><title>Service Unavailable</title></head><body><h1>503 Service Unavailable</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>',
                    500,
                    ['Content-Type' => 'text/html']
                );
            }
            return new \Illuminate\Http\JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
} 