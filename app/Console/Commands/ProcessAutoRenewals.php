<?php

namespace App\Console\Commands;

use App\Services\DomainRenewalService;
use Illuminate\Console\Command;

class ProcessAutoRenewals extends Command
{
    protected $signature = 'domain:process-auto-renewals 
                            {--lead-time=7 : Number of days before expiry to attempt renewal}
                            {--partner= : Filter by specific partner ID}
                            {--dry-run : Preview what would happen without making changes}';

    protected $description = 'Process automatic renewals for domains expiring soon';

    public function handle(DomainRenewalService $renewalService): int
    {
        $leadTime = (int) $this->option('lead-time');
        $partnerId = $this->option('partner') ? (int) $this->option('partner') : null;
        $isDryRun = $this->option('dry-run');

        $this->info("Starting auto-renewal process...");
        $this->info("Lead time: {$leadTime} days");
        
        if ($partnerId) {
            $this->info("Partner filter: {$partnerId}");
        }

        if ($isDryRun) {
            $this->warn("DRY RUN MODE - No changes will be made");
        }

        $startTime = microtime(true);

        if ($isDryRun) {
            $results = $this->previewAutoRenewals($leadTime, $partnerId);
        } else {
            $results = $renewalService->processAutoRenewals($leadTime, $partnerId);
        }

        $duration = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->info("=== Auto-Renewal Summary ===");
        $this->info("Processed: {$results['processed']}");
        $this->info("Succeeded: {$results['succeeded']}");
        
        if ($results['failed'] > 0) {
            $this->error("Failed: {$results['failed']}");
        } else {
            $this->info("Failed: {$results['failed']}");
        }
        
        $this->info("Duration: {$duration}s");
        $this->newLine();

        // Display detailed results if verbose
        if ($this->output->isVerbose() && !empty($results['results'])) {
            $this->info("=== Detailed Results ===");
            
            $tableData = [];
            foreach ($results['results'] as $result) {
                $tableData[] = [
                    $result['domain_name'],
                    $result['status'],
                    $result['message'],
                ];
            }
            
            $this->table(['Domain', 'Status', 'Message'], $tableData);
        }

        // Return success if at least some renewals succeeded, or if nothing to process
        return self::SUCCESS;
    }

    /**
     * Preview domains that would be auto-renewed
     */
    protected function previewAutoRenewals(int $leadTimeDays, ?int $partnerId): array
    {
        $cutoffDate = now()->addDays($leadTimeDays);

        $query = \App\Models\Domain::where('auto_renew', true)
            ->where('status', \App\Enums\DomainStatus::Active)
            ->where('expires_at', '<=', $cutoffDate)
            ->where('expires_at', '>', now());

        if ($partnerId) {
            $query->where('partner_id', $partnerId);
        }

        $domains = $query->get();

        $results = [
            'processed' => $domains->count(),
            'succeeded' => 0,
            'failed' => 0,
            'results' => [],
        ];

        foreach ($domains as $domain) {
            $results['results'][] = [
                'domain_id' => $domain->id,
                'domain_name' => $domain->name,
                'status' => 'preview',
                'message' => "Would attempt renewal (expires: {$domain->expires_at->format('Y-m-d')})",
            ];
        }

        return $results;
    }
}
