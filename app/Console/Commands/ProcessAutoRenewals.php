<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDomainRenewalJob;
use App\Models\Domain;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessAutoRenewals extends Command
{
    protected $signature = 'domains:process-auto-renewals';

    protected $description = 'Process automatic domain renewals for domains expiring soon';

    public function handle(): int
    {
        $this->info('Processing automatic domain renewals...');

        $autoRenewDays = Setting::get('auto_renew_days_before_expiry', 7);

        $domains = Domain::active()
            ->autoRenew()
            ->where('expires_at', '<=', now()->addDays($autoRenewDays))
            ->where('expires_at', '>=', now())
            ->with(['client', 'partner.wallet', 'registrar'])
            ->get();

        $this->line("Found {$domains->count()} domain(s) eligible for auto-renewal");

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($domains as $domain) {
            try {
                $partner = $domain->partner;
                
                if (!$partner || !$partner->wallet) {
                    $this->warn("Skipping {$domain->name}: Partner or wallet not found");
                    Log::warning("Auto-renewal skipped: No partner wallet", [
                        'domain_id' => $domain->id,
                        'domain_name' => $domain->name,
                    ]);
                    $skipped++;
                    continue;
                }

                // Estimate renewal cost (you'd normally get this from pricing)
                $estimatedCost = 15.00; // Placeholder
                
                if ($partner->wallet->balance < $estimatedCost) {
                    $this->warn("Skipping {$domain->name}: Insufficient balance");
                    Log::warning("Auto-renewal skipped: Insufficient balance", [
                        'domain_id' => $domain->id,
                        'domain_name' => $domain->name,
                        'balance' => $partner->wallet->balance,
                        'estimated_cost' => $estimatedCost,
                    ]);
                    $skipped++;
                    continue;
                }

                // Queue the renewal job
                ProcessDomainRenewalJob::dispatch($domain);
                
                $this->line("✓ Queued auto-renewal for {$domain->name}");
                Log::info("Auto-renewal queued", [
                    'domain_id' => $domain->id,
                    'domain_name' => $domain->name,
                    'expires_at' => $domain->expires_at,
                ]);
                
                $processed++;
            } catch (\Exception $e) {
                $this->error("Failed to process {$domain->name}: {$e->getMessage()}");
                Log::error("Auto-renewal failed", [
                    'domain_id' => $domain->id,
                    'domain_name' => $domain->name,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $this->newLine();
        $this->info("✓ Auto-renewal processing complete");
        $this->table(
            ['Status', 'Count'],
            [
                ['Processed', $processed],
                ['Skipped', $skipped],
                ['Failed', $failed],
            ]
        );

        Log::info("ProcessAutoRenewals completed", [
            'processed' => $processed,
            'skipped' => $skipped,
            'failed' => $failed,
        ]);

        return self::SUCCESS;
    }
}
