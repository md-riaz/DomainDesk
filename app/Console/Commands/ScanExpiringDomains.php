<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Notifications\DomainExpiryAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScanExpiringDomains extends Command
{
    protected $signature = 'domains:scan-expiring';

    protected $description = 'Scan for expiring domains and queue notifications';

    public function handle(): int
    {
        $this->info('Scanning for expiring domains...');

        $expiryIntervals = [30, 15, 7, 1];
        $totalQueued = 0;

        foreach ($expiryIntervals as $days) {
            $domains = Domain::active()
                ->where('expires_at', '>=', now()->addDays($days)->startOfDay())
                ->where('expires_at', '<=', now()->addDays($days)->endOfDay())
                ->with(['client', 'partner'])
                ->get();

            foreach ($domains as $domain) {
                if ($domain->client) {
                    $domain->client->notify(new DomainExpiryAlert($domain, $days));
                    $totalQueued++;
                    
                    Log::info("Queued expiry alert for domain {$domain->name} (expires in {$days} days)", [
                        'domain_id' => $domain->id,
                        'client_id' => $domain->client_id,
                        'expires_at' => $domain->expires_at,
                    ]);
                }
            }

            $this->line("✓ Found {$domains->count()} domain(s) expiring in {$days} days");
        }

        $this->info("✓ Scan complete. Queued {$totalQueued} notification(s)");
        
        Log::info("ScanExpiringDomains completed", ['total_queued' => $totalQueued]);

        return self::SUCCESS;
    }
}
