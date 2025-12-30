<?php

namespace App\Jobs;

use App\Imports\CandidatesImport;
use App\Models\ImportHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProcessCandidateImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200; // 20 menit
    public int $tries = 3;

    protected string $path;
    protected int $authUserId;
    protected int $importHistoryId;

    public function __construct(string $path, int $authUserId, int $importHistoryId)
    {
        $this->path = $path;
        $this->authUserId = $authUserId;
        $this->importHistoryId = $importHistoryId;
    }

    public function handle()
    {
        Log::info('ProcessCandidateImport: Job started', [
            'history_id' => $this->importHistoryId,
            'path' => $this->path,
        ]);

        $importHistory = ImportHistory::find($this->importHistoryId);

        if (!$importHistory) {
            Log::error('ProcessCandidateImport: ImportHistory not found', [
                'history_id' => $this->importHistoryId,
            ]);
            return;
        }

        try {
            $path = $this->path;
            
            // Support both relative (Storage path) and absolute paths
            if (!str_starts_with($path, '/') && !preg_match('/^[a-z]:/i', $path)) {
                // Relative path - use Storage
                $path = Storage::path($path);
            }

            if (!file_exists($path)) {
                throw new \Exception('Import file not found: ' . $this->path);
            }

            $import = new CandidatesImport($this->authUserId);

            Excel::import($import, $path);

            $processed = $import->getProcessedCount();
            $skipped = $import->getSkippedCount();
            $errors = $import->getErrors();

            $importHistory->update([
                'success_rows' => $processed,
                'failed_rows' => $skipped,
                'status' => 'completed',
                'error_message' => null,
                'error_details' => count($errors) > 0 ? $errors : null,
            ]);

            Log::info('ProcessCandidateImport: Import completed', [
                'history_id' => $this->importHistoryId,
                'processed' => $processed,
                'skipped' => $skipped,
                'error_count' => count($errors),
            ]);

        } catch (Throwable $e) {

            Log::error('ProcessCandidateImport: Import failed', [
                'history_id' => $this->importHistoryId,
                'error' => $e->getMessage(),
            ]);

            $importHistory->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e; // penting supaya queue retry

        } finally {

            // DELETE FILE HANYA JIKA JOB SUKSES ATAU FINAL FAIL
            $path = $this->path;
            
            // Support both relative and absolute paths
            if (!str_starts_with($path, '/') && !preg_match('/^[a-z]:/i', $path)) {
                $path = Storage::path($path);
            }
            
            if (file_exists($path)) {
                unlink($path);
                Log::info('ProcessCandidateImport: File deleted', [
                    'history_id' => $this->importHistoryId,
                ]);
            }
        }
    }

    public function failed(Throwable $exception)
    {
        Log::critical('ProcessCandidateImport: Job permanently failed', [
            'history_id' => $this->importHistoryId,
            'exception' => $exception->getMessage(),
        ]);

        $importHistory = ImportHistory::find($this->importHistoryId);
        if ($importHistory) {
            $importHistory->update([
                'status' => 'failed',
                'error_message' => 'Job failed permanently: ' . $exception->getMessage(),
            ]);
        }
    }
}
