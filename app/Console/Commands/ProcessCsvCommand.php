<?php

namespace App\Console\Commands;

use App\Services\RedisService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use SplFileObject;

class ProcessCsvCommand extends Command
{
    protected $signature = 'process:csv';
    protected $description = 'Process CSV files from the queue';

    private RedisService $redisService;
    private const BATCH_SIZE = 1000;

    public function __construct(RedisService $redisService)
    {
        parent::__construct();
        $this->redisService = $redisService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Starting CSV processing worker...');

        while (true) {
            try {
                // Get task from queue
                $task = $this->redisService->popFromQueue();
                
                if (!$task) {
                    $this->info('No tasks in queue, waiting...');
                    sleep(5);
                    continue;
                }

                $taskId = $task['task_id'];
                $filename = storage_path("app/uploads/{$taskId}.csv");

                if (!file_exists($filename)) {
                    Log::error("CSV file not found for task_id: {$taskId}");
                    continue;
                }

                $this->info("Processing task: {$taskId}");

                // Process CSV file
                $file = new SplFileObject($filename, 'r');
                $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
                
                // Read headers
                $headers = $file->fgetcsv();
                if (!$headers) {
                    Log::error("Invalid CSV file (no headers) for task_id: {$taskId}");
                    continue;
                }

                $rowCount = 0;
                $batch = [];
                $batchCount = 0;

                // Process rows in batches
                while (!$file->eof()) {
                    $row = $file->fgetcsv();
                    if (!$row || count($row) !== count($headers)) {
                        continue;
                    }

                    $rowCount++;
                    $batch[] = array_combine($headers, $row);
                    $batchCount++;

                    if ($batchCount >= self::BATCH_SIZE) {
                        $this->saveBatch($taskId, $batch, $batchCount);
                        $batch = [];
                        $batchCount = 0;
                    }
                }

                // Save remaining rows
                if (!empty($batch)) {
                    $this->saveBatch($taskId, $batch, $batchCount);
                }

                // Save task statistics
                $stats = [
                    'row_count' => $rowCount,
                    'column_count' => count($headers),
                    'headers' => $headers
                ];

                $this->redisService->setTaskStatus($taskId, 'completed');
                $this->redisService->setTaskResult($taskId, $stats);

                Log::info("Task completed", [
                    'task_id' => $taskId,
                    'row_count' => $rowCount,
                    'column_count' => count($headers)
                ]);

                // Cleanup
                unlink($filename);
                Log::info("Deleted CSV file for task_id: {$taskId}");

            } catch (\Exception $e) {
                Log::error("Error processing task", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                sleep(5);
            }
        }

        return 0;
    }

    private function saveBatch(string $taskId, array $batch, int $batchCount): void
    {
        $batchKey = "csv_data:{$taskId}:{$batchCount}";
        $this->redisService->setBatch($batchKey, $batch);
        $this->info("Saved batch {$batchCount} for task {$taskId}");
    }
} 