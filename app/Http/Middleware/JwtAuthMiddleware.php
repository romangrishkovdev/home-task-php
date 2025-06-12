<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class JwtAuthMiddleware
{
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        if (!$this->jwtService->validateToken($token)) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        return $next($request);
    }
} 