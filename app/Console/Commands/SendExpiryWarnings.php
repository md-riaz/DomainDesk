<?php

namespace App\Console\Commands;

use App\Jobs\SendRenewalEmailJob;
use App\Models\Domain;
use Illuminate\Console\Command;

class SendExpiryWarnings extends Command
{
    protected $signature = 'domain:send-expiry-warnings 
                            {--partner= : Filter by specific partner ID}
                            {--dry-run : Preview what would happen without sending emails}';

    protected $description = 'Send expiry warning emails for domains expiring soon';

    protected array $warningDays = [30, 15, 7, 1];

    public function handle(): int
    {
        $partnerId = $this->option('partner') ? (int) $this->option('partner') : null;
        $isDryRun = $this->option('dry-run');

        $this->info("Starting expiry warning process...");
        
        if ($partnerId) {
            $this->info("Partner filter: {$partnerId}");
        }

        if ($isDryRun) {
            $this->warn("DRY RUN MODE - No emails will be sent");
        }

        $totalSent = 0;
        $results = [];

        foreach ($this->warningDays as $days) {
            $this->info("Processing {$days}-day warnings...");
            
            $count = $this->sendWarningsForDays($days, $partnerId, $isDryRun);
            $totalSent += $count;
            $results[$days] = $count;
            
            $this->info("  Sent: {$count}");
        }

        $this->newLine();
        $this->info("=== Expiry Warning Summary ===");
        foreach ($results as $days => $count) {
            $this->info("{$days}-day warnings: {$count}");
        }
        $this->info("Total: {$totalSent}");

        return self::SUCCESS;
    }

    /**
     * Send warnings for domains expiring in specific number of days
     */
    protected function sendWarningsForDays(int $days, ?int $partnerId, bool $isDryRun): int
    {
        $targetDate = now()->addDays($days)->startOfDay();
        $endDate = $targetDate->copy()->endOfDay();

        $query = Domain::where('status', \App\Enums\DomainStatus::Active)
            ->whereBetween('expires_at', [$targetDate, $endDate])
            ->where('auto_renew', false); // Only send to non-auto-renew domains

        if ($partnerId) {
            $query->where('partner_id', $partnerId);
        }

        $domains = $query->get();
        $count = 0;

        foreach ($domains as $domain) {
            if ($isDryRun) {
                $this->line("  Would send {$days}-day warning for: {$domain->name}");
            } else {
                SendRenewalEmailJob::dispatch($domain, null, 'expiry_warning');
                $this->line("  Sent {$days}-day warning for: {$domain->name}", 'info');
            }
            $count++;
        }

        return $count;
    }
}
