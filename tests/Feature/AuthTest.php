<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthTest extends TestCase
{
    /**
     * Test successful JWT token generation.
     *
     * @return void
     */
    public function testGenerateTokenSuccess()
    {
        $response = $this->post('/api/v1/auth/token', [
            'username' => 'test',
            'password' => 'test',
        ]);

        $response->seeStatusCode(200);
        $response->seeJsonStructure([
            'token',
        ]);
    }
} 