<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Services\RegistrarSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncDomainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 300; // 5 minutes
    public int $timeout = 120; // 2 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Domain $domain,
        public bool $force = false
    ) {
        $this->onQueue('domain-sync');
    }

    /**
     * Execute the job.
     */
    public function handle(RegistrarSyncService $syncService): void
    {
        Log::info("Starting domain sync job", [
            'domain' => $this->domain->name,
            'attempt' => $this->attempts(),
        ]);

        $result = $syncService->syncDomain($this->domain, $this->force);

        if (!$result['success']) {
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);
                Log::warning("Domain sync failed, retrying", [
                    'domain' => $this->domain->name,
                    'attempt' => $this->attempts(),
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            } else {
                Log::error("Domain sync failed after all retries", [
                    'domain' => $this->domain->name,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }
        } else {
            Log::info("Domain sync job completed", [
                'domain' => $this->domain->name,
                'changes' => count($result['changes'] ?? []),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Domain sync job failed permanently", [
            'domain' => $this->domain->name,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->domain->update([
            'sync_metadata' => [
                'last_sync_attempt' => now()->toIso8601String(),
                'last_sync_error' => $exception->getMessage(),
                'failed_permanently' => true,
            ],
        ]);
    }
}
