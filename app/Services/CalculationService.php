<?php

namespace App\Services;

class CalculationService
{
    /**
     * Calculate sum of integers
     *
     * @param array $integers
     * @return int
     */
    public function calculateSum(array $integers): int
    {
        return array_sum($integers);
    }

    /**
     * Calculate average of floats
     *
     * @param array $floats
     * @return float
     */
    public function calculateAverage(array $floats): float
    {
        if (empty($floats)) {
            return 0.0;
        }
        return array_sum($floats) / count($floats);
    }

    /**
     * Count true values in boolean array
     *
     * @param array $booleans
     * @return int
     */
    public function countTrueBooleans(array $booleans): int
    {
        return count(array_filter($booleans));
    }

    /**
     * Sum numeric strings, ignoring non-numeric
     *
     * @param array $strings
     * @return int
     */
    public function sumNumericStrings(array $strings): int
    {
        return array_sum(array_filter($strings, function($str) {
            return is_numeric($str);
        }));
    }

    /**
     * Process calculation for all arrays
     *
     * @param array $data
     * @return array
     */
    public function calculate(array $data): array
    {
        return [
            'integer_sum' => $this->calculateSum($data['integers']),
            'float_average' => $this->calculateAverage($data['floats']),
            'boolean_true_count' => $this->countTrueBooleans($data['booleans']),
            'numeric_string_sum' => $this->sumNumericStrings($data['strings'])
        ];
    }
} 