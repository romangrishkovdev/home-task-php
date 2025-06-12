<?php

namespace Tests\Feature;

use Tests\TestCase;

class CalculationTest extends TestCase
{
    /**
     * Test successful calculation.
     *
     * @return void
     */
    public function testSuccessfulCalculation()
    {
        $token = $this->getJwtToken();

        $this->post('/api/v1/calculation', [
            'integers' => [1, 2, 3],
            'floats' => [1.5, 2.5],
            'booleans' => [true, false],
            'strings' => ["100", "200"]
        ], [
            'Authorization' => 'Bearer ' . $token
        ]);
        $this->assertResponseStatus(200);
        $this->seeJson([
            'integer_sum' => 6,
            'float_average' => 2.0,
            'boolean_true_count' => 1,
            'numeric_string_sum' => 300
        ]);
    }

    /**
     * Test missing JWT for calculation endpoint.
     *
     * @return void
     */
    public function testMissingJwt()
    {
        $this->post('/api/v1/calculation', [
            'integers' => [1, 2],
            'floats' => [1.0],
            'booleans' => [true],
            'strings' => ["1"]
        ]);
        $this->assertResponseStatus(401);
        $this->seeJson([
            'error' => 'Unauthorized'
        ]);
    }
} 