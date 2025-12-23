<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DeleteTempFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;

    /**
     * Create a new job instance.
     *
     * @param string $filePath
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            if (Storage::disk('local')->exists($this->filePath)) {
                Storage::disk('local')->delete($this->filePath);
                Log::info("Temporary import file deleted successfully: {$this->filePath}");
            } else {
                Log::warning("Attempted to delete temporary import file, but it did not exist: {$this->filePath}");
            }
        } catch (\Exception $e) {
            Log::error("Error deleting temporary import file: {$this->filePath}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

