<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    private string $secret;
    private int $ttl;

    public function __construct()
    {
        $this->secret = config('jwt.secret');
        $this->ttl = config('jwt.ttl', 3600);
    }

    public function generateToken(string $email): string
    {
        $payload = [
            'iss' => config('app.url'),
            'iat' => time(),
            'exp' => time() + $this->ttl,
            'email' => $email
        ];

        return JWT::encode($payload, $this->secret, config('jwt.algo'));
    }

    public function validateToken(string $token): bool
    {
        try {
            JWT::decode($token, new Key($this->secret, config('jwt.algo')));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
} 