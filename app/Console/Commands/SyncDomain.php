<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\RegistrarSyncService;
use Illuminate\Console\Command;

class SyncDomain extends Command
{
    protected $signature = 'domain:sync 
                            {domain? : The domain name to sync (omit to sync all)}
                            {--force : Force sync even if recently synced}
                            {--limit=100 : Maximum number of domains to sync (when syncing all)}
                            {--partner= : Filter by partner ID}';

    protected $description = 'Sync domain data with registrar';

    public function handle(RegistrarSyncService $syncService): int
    {
        $domainName = $this->argument('domain');
        $force = $this->option('force');

        if ($domainName) {
            return $this->syncSingleDomain($domainName, $force, $syncService);
        }

        return $this->syncMultipleDomains($force, $syncService);
    }

    protected function syncSingleDomain(string $domainName, bool $force, RegistrarSyncService $syncService): int
    {
        $this->info("Syncing domain: {$domainName}");

        $domain = Domain::where('name', $domainName)->first();

        if (!$domain) {
            $this->error("Domain not found: {$domainName}");
            return self::FAILURE;
        }

        $result = $syncService->syncDomain($domain, $force);

        if ($result['success']) {
            if ($result['skipped'] ?? false) {
                $this->warn("Domain skipped: {$result['reason']}");
                return self::SUCCESS;
            }

            $changesCount = count($result['changes'] ?? []);
            
            if ($changesCount > 0) {
                $this->info("Domain synced successfully with {$changesCount} changes:");
                foreach ($result['changes'] as $field => $change) {
                    $this->line("  - {$field}: {$change['old']} → {$change['new']}");
                }
            } else {
                $this->info("Domain synced successfully (no changes)");
            }

            return self::SUCCESS;
        }

        $this->error("Failed to sync domain: " . ($result['error'] ?? 'Unknown error'));
        return self::FAILURE;
    }

    protected function syncMultipleDomains(bool $force, RegistrarSyncService $syncService): int
    {
        $limit = (int) $this->option('limit');
        $partnerId = $this->option('partner');

        $query = $syncService->getDomainsNeedingSync($limit);

        if ($partnerId) {
            $query = Domain::where('partner_id', $partnerId)
                ->whereNotNull('registrar_id')
                ->limit($limit)
                ->get();
        }

        $domains = $query;

        if ($domains->isEmpty()) {
            $this->info("No domains need syncing.");
            return self::SUCCESS;
        }

        $this->info("Syncing {$domains->count()} domains...");

        $progressBar = $this->output->createProgressBar($domains->count());
        $progressBar->start();

        $results = $syncService->syncDomains($domains, $force, function ($domain, $result) use ($progressBar) {
            $progressBar->advance();
        });

        $progressBar->finish();
        $this->newLine(2);

        $stats = $results['stats'];

        $this->info("Sync completed!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total', $stats['total']],
                ['Synced', $stats['synced']],
                ['Changes', $stats['changes']],
                ['Skipped', $stats['skipped']],
                ['Failed', $stats['failed']],
            ]
        );

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
