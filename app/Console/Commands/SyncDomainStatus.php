<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\RegistrarSyncService;
use Illuminate\Console\Command;

class SyncDomainStatus extends Command
{
    protected $signature = 'domain:sync-status 
                            {--days=30 : Sync domains expiring within this many days}
                            {--all : Sync all active domains}
                            {--limit=100 : Maximum number of domains to sync}';

    protected $description = 'Sync domain status from registrar (lightweight operation)';

    public function handle(RegistrarSyncService $syncService): int
    {
        $days = (int) $this->option('days');
        $all = $this->option('all');
        $limit = (int) $this->option('limit');

        if ($all) {
            $domains = Domain::whereNotNull('registrar_id')
                ->whereIn('status', ['active', 'grace_period', 'redemption'])
                ->limit($limit)
                ->get();
            
            $this->info("Syncing status for {$domains->count()} active domains...");
        } else {
            $domains = $syncService->getExpiringDomains($days);
            
            if ($domains->count() > $limit) {
                $domains = $domains->take($limit);
            }

            $this->info("Syncing status for {$domains->count()} domains expiring within {$days} days...");
        }

        if ($domains->isEmpty()) {
            $this->info("No domains to sync.");
            return self::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar($domains->count());
        $progressBar->start();

        $stats = [
            'total' => $domains->count(),
            'changed' => 0,
            'unchanged' => 0,
            'failed' => 0,
        ];

        foreach ($domains as $domain) {
            $result = $syncService->syncDomainStatus($domain);

            if ($result['success']) {
                if ($result['changed'] ?? false) {
                    $stats['changed']++;
                } else {
                    $stats['unchanged']++;
                }
            } else {
                $stats['failed']++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Status sync completed!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total', $stats['total']],
                ['Changed', $stats['changed']],
                ['Unchanged', $stats['unchanged']],
                ['Failed', $stats['failed']],
            ]
        );

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
