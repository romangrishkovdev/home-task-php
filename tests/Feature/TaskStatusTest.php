<?php

namespace Tests\Feature;

use Tests\TestCase;

class TaskStatusTest extends TestCase
{
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        // Get a JWT token before each test
        $this->token = $this->getJwtToken();
    }

    /**
     * Test getting status for a non-existent task.
     *
     * @return void
     */
    public function testGetStatusForNonExistentTask()
    {
        $nonExistentTaskId = 'a1b2c3d4-e5f6-7890-abcd-1234567890ab'; // A random, non-existent UUID

        $response = $this->get(
            '/api/v1/task/' . $nonExistentTaskId,
            ['Authorization' => 'Bearer ' . $this->token,
             'Accept' => 'application/json']
        );

        $response->seeStatusCode(404);
        $response->seeJson([
            'status' => false,
            'message' => 'Task not found',
        ]);
    }
} 