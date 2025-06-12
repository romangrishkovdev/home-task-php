<?php

namespace App\Http\Controllers;

use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Post(
 *     path="/api/v1/auth/token",
 *     summary="Generate JWT token",
 *     tags={"Authentication"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"username", "password"},
 *             @OA\Property(property="username", type="string", example="test"),
 *             @OA\Property(property="password", type="string", example="test")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="JWT token generated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="token", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid input",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Invalid credentials")
 *         )
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
 */
class AuthController extends Controller
{
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Generate a new JWT token.
     *
     * @return JsonResponse
     */
    public function token(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required|string',
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return new JsonResponse([
                    'status' => false,
                    'message' => 'Invalid credentials'
                ], 400);
            }

            // For demo purposes, accept any username/password
            $token = $this->jwtService->generateToken($request->input('username'));
            return new JsonResponse(['token' => $token]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => false,
                'message' => 'Server error'
            ], 500);
        }
    }
} 