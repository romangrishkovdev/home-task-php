<?php

namespace App\Http\Controllers;

use App\Services\CalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Post(
 *     path="/api/v1/calculation",
 *     summary="Process calculation",
 *     tags={"Calculation"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"integers", "floats", "booleans", "strings"},
 *             @OA\Property(property="integers", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="floats", type="array", @OA\Items(type="number", format="float")),
 *             @OA\Property(property="booleans", type="array", @OA\Items(type="boolean")),
 *             @OA\Property(property="strings", type="array", @OA\Items(type="string"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Calculation successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="integer_sum", type="integer"),
 *             @OA\Property(property="float_average", type="number", format="float"),
 *             @OA\Property(property="boolean_true_count", type="integer"),
 *             @OA\Property(property="numeric_string_sum", type="integer")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid input data",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Unauthorized access")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="message", type="object", example={"integers": ["The integers must be an array."]})
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="An error occurred while processing your request")
 *         )
 *     )
 * )
 */
class CalculationController extends Controller
{
    private CalculationService $calculationService;

    public function __construct(CalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Calculate statistics from the input data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calculate(Request $request): JsonResponse
    {
        if (!$request->bearerToken()) {
            abort(401, 'Unauthorized access');
        }

        $validator = Validator::make($request->all(), [
            'integers' => 'required|array',
            'integers.*' => 'integer',
            'floats' => 'required|array',
            'floats.*' => 'numeric',
            'booleans' => 'required|array',
            'booleans.*' => 'boolean',
            'strings' => 'required|array',
            'strings.*' => 'string'
        ]);

        if ($validator->fails()) {
            abort(422, $validator->errors());
        }

        $validated = $validator->validated();
        $result = $this->calculationService->calculate($validated);

        Log::info('Calculation performed successfully', [
            'input' => $validated,
            'result' => $result
        ]);

        return new JsonResponse($result);
    }
} 