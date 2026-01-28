<?php

namespace App\Console\Commands;

use App\Enums\DomainStatus;
use App\Models\Domain;
use App\Services\DomainTransferService;
use Illuminate\Console\Command;

class CheckTransferStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:check-transfer-status 
                            {--domain= : Check specific domain by name}
                            {--limit=50 : Maximum number of domains to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and update domain transfer status from registrars';

    /**
     * Execute the console command.
     */
    public function handle(DomainTransferService $transferService): int
    {
        $this->info('Starting transfer status check...');

        // Get domains in transfer state
        $query = Domain::whereIn('status', [
            DomainStatus::PendingTransfer,
            DomainStatus::TransferInProgress,
            DomainStatus::TransferApproved,
        ]);

        // Filter by specific domain if provided
        if ($domainName = $this->option('domain')) {
            $query->where('name', $domainName);
        }

        $limit = (int) $this->option('limit');
        $domains = $query->orderBy('transfer_initiated_at', 'asc')
            ->limit($limit)
            ->get();

        if ($domains->isEmpty()) {
            $this->info('No domains found in transferring state.');
            return Command::SUCCESS;
        }

        $this->info("Found {$domains->count()} domain(s) to check.");

        $checked = 0;
        $updated = 0;
        $failed = 0;

        foreach ($domains as $domain) {
            $this->line("Checking {$domain->name}...");

            try {
                $result = $transferService->checkTransferStatus($domain);

                if ($result['success']) {
                    $checked++;
                    
                    // Check if status changed
                    $domain->refresh();
                    if (in_array($domain->status, [
                        DomainStatus::TransferCompleted,
                        DomainStatus::TransferFailed,
                        DomainStatus::TransferCancelled,
                    ])) {
                        $updated++;
                        $this->info("  ✓ Status updated to: {$domain->status->label()}");
                    } else {
                        $this->line("  - Still in progress: {$domain->status->label()}");
                    }
                } else {
                    $failed++;
                    $this->warn("  ✗ Failed to check: {$result['message']}");
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("  ✗ Error: {$e->getMessage()}");
            }

            // Small delay to avoid rate limiting
            if ($domains->count() > 1) {
                usleep(100000); // 100ms delay
            }
        }

        $this->newLine();
        $this->info("Transfer status check complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Domains Checked', $checked],
                ['Status Updated', $updated],
                ['Failed/Errors', $failed],
            ]
        );

        return Command::SUCCESS;
    }
}
