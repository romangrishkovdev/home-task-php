<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    /**
     * Test the /health endpoint.
     *
     * @return void
     */
    public function testHealthCheck()
    {
        $response = $this->get('/health', ['Accept' => 'application/json']);

        $response->seeStatusCode(200);
        $response->seeJson([
            'status' => 'ok',
        ]);
    }
} 