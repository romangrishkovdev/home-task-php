<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Laravel\Lumen\Testing\TestCase as BaseTestCase;
use Predis\Client;
use Firebase\JWT\JWT;

abstract class TestCase extends BaseTestCase
{
    protected Client $redis;
    protected string $token;

    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__ . '/bootstrap.php';
    }

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Initialize Redis client
        $this->redis = new Client([
            'host' => env('REDIS_HOST', 'redis'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', null),
        ]);

        // Clear Redis data
        $this->redis->flushall();

        // Generate JWT token
        $this->token = $this->generateToken();
    }

    /**
     * Generate JWT token for testing.
     */
    protected function generateToken(): string
    {
        $payload = [
            'sub' => config('jwt.sub'),
            'iat' => time(),
            'exp' => time() + config('jwt.ttl')
        ];

        return JWT::encode($payload, config('jwt.secret'), config('jwt.algo'));
    }

    /**
     * Create a test CSV file.
     */
    protected function createTestCsv(string $filename, int $rows = 10000): string
    {
        $headers = ['id', 'first_name', 'last_name', 'email', 'phone'];
        $file = new \SplFileObject($filename, 'w');
        
        // Write headers
        $file->fputcsv($headers);

        // Write data rows
        for ($i = 1; $i <= $rows; $i++) {
            $file->fputcsv([
                $i,
                "First{$i}",
                "Last{$i}",
                "user{$i}@example.com",
                "+1234567890{$i}"
            ]);
        }

        return $filename;
    }

    /**
     * Helper to get a JWT token.
     *
     * @return string
     */
    protected function getJwtToken(): string
    {
        $response = $this->post('/api/v1/auth/token', [
            'username' => 'test',
            'password' => 'test',
        ]);

        $this->assertResponseStatus(200);

        return json_decode($response->response->getContent(), true)['token'] ?? '';
    }

    /**
     * Clear Redis database after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        if (app()->bound('redis')) {
            app('redis')->flushdb();
        }
    }
} 