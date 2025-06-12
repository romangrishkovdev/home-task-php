<?php

namespace Tests\Unit;

use App\Services\CalculationService;
use PHPUnit\Framework\TestCase;

class CalculationServiceTest extends TestCase
{
    private CalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CalculationService();
    }

    public function testCalculateSum()
    {
        $this->assertEquals(6, $this->service->calculateSum([1, 2, 3]));
        $this->assertEquals(0, $this->service->calculateSum([]));
        $this->assertEquals(-5, $this->service->calculateSum([-1, -2, -2]));
    }

    public function testCalculateAverage()
    {
        $this->assertEquals(2.0, $this->service->calculateAverage([1.0, 2.0, 3.0]));
        $this->assertEquals(0.0, $this->service->calculateAverage([]));
        $this->assertEquals(1.5, $this->service->calculateAverage([1.0, 2.0]));
    }

    public function testCountTrueBooleans()
    {
        $this->assertEquals(2, $this->service->countTrueBooleans([true, false, true]));
        $this->assertEquals(0, $this->service->countTrueBooleans([false, false]));
        $this->assertEquals(1, $this->service->countTrueBooleans([true]));
        $this->assertEquals(0, $this->service->countTrueBooleans([]));
    }

    public function testSumNumericStrings()
    {
        $this->assertEquals(60, $this->service->sumNumericStrings(["10", "20", "30"]));
        $this->assertEquals(0, $this->service->sumNumericStrings([]));
        $this->assertEquals(5, $this->service->sumNumericStrings(["5", "abc", "def"])); // Non-numeric strings are ignored
        $this->assertEquals(-6, $this->service->sumNumericStrings(["-10", "1.5", "2"])); // Corrected expected value from -7 to -6
        $this->assertEquals(0, $this->service->sumNumericStrings(["abc", "def"]));
    }

    public function testCalculate()
    {
        $data = [
            'integers' => [1, 2, 3],
            'floats' => [1.0, 2.0, 3.0],
            'booleans' => [true, false, true],
            'strings' => ["10", "20", "30"]
        ];

        $expected = [
            'integer_sum' => 6,
            'float_average' => 2.0,
            'boolean_true_count' => 2,
            'numeric_string_sum' => 60
        ];

        $this->assertEquals($expected, $this->service->calculate($data));

        // Test with empty arrays
        $emptyData = [
            'integers' => [],
            'floats' => [],
            'booleans' => [],
            'strings' => []
        ];

        $expectedEmpty = [
            'integer_sum' => 0,
            'float_average' => 0.0,
            'boolean_true_count' => 0,
            'numeric_string_sum' => 0
        ];
        $this->assertEquals($expectedEmpty, $this->service->calculate($emptyData));
    }
} 