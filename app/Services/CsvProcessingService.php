<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use League\Csv\Reader;
use League\Csv\Writer;

class CsvProcessingService
{
    private const REDIS_KEY_PREFIX = 'csv_data:';
    private const BATCH_SIZE = 1000;

    public function processCsv(string $filePath): array
    {
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);
        
        $stats = [
            'total_rows' => 0,
            'countries' => [],
            'companies' => [],
            'subscription_years' => [],
            'start_time' => microtime(true)
        ];

        $batch = [];
        $batchCount = 0;

        foreach ($csv->getRecords() as $record) {
            $stats['total_rows']++;
            
            // Collect statistics
            $country = $record['Country'] ?? 'Unknown';
            $company = $record['Company'] ?? 'Unknown';
            $subscriptionYear = date('Y', strtotime($record['Subscription Date'] ?? 'now'));
            
            $stats['countries'][$country] = ($stats['countries'][$country] ?? 0) + 1;
            $stats['companies'][$company] = ($stats['companies'][$company] ?? 0) + 1;
            $stats['subscription_years'][$subscriptionYear] = ($stats['subscription_years'][$subscriptionYear] ?? 0) + 1;

            // Prepare batch for Redis
            $batch[] = json_encode($record);
            $batchCount++;

            // Save batch to Redis when it reaches the batch size
            if ($batchCount >= self::BATCH_SIZE) {
                $this->saveBatchToRedis($batch);
                $batch = [];
                $batchCount = 0;
            }
        }

        // Save remaining records
        if (!empty($batch)) {
            $this->saveBatchToRedis($batch);
        }

        $stats['processing_time'] = round(microtime(true) - $stats['start_time'], 2);
        unset($stats['start_time']);

        // Sort statistics
        arsort($stats['countries']);
        arsort($stats['companies']);
        ksort($stats['subscription_years']);

        // Keep only top 10 for countries and companies
        $stats['countries'] = array_slice($stats['countries'], 0, 10, true);
        $stats['companies'] = array_slice($stats['companies'], 0, 10, true);

        return $stats;
    }

    private function saveBatchToRedis(array $batch): void
    {
        if (empty($batch)) {
            return;
        }

        $pipeline = Redis::pipeline();
        foreach ($batch as $record) {
            $pipeline->rpush(self::REDIS_KEY_PREFIX . 'records', $record);
        }
        $pipeline->execute();
    }

    public function getStoredRecordsCount(): int
    {
        return Redis::llen(self::REDIS_KEY_PREFIX . 'records');
    }

    public function clearStoredData(): void
    {
        Redis::del(self::REDIS_KEY_PREFIX . 'records');
    }
} 