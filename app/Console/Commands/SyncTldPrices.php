<?php

namespace App\Console\Commands;

use App\Models\Registrar;
use App\Services\RegistrarSyncService;
use Illuminate\Console\Command;

class SyncTldPrices extends Command
{
    protected $signature = 'tld:sync-prices 
                            {registrar? : The registrar slug to sync (omit to sync all active registrars)}
                            {--force : Force sync even if recently synced}';

    protected $description = 'Sync TLD pricing from registrar(s)';

    public function handle(RegistrarSyncService $syncService): int
    {
        $registrarSlug = $this->argument('registrar');

        if ($registrarSlug) {
            return $this->syncSingleRegistrar($registrarSlug, $syncService);
        }

        return $this->syncAllRegistrars($syncService);
    }

    protected function syncSingleRegistrar(string $slug, RegistrarSyncService $syncService): int
    {
        $registrar = Registrar::where('slug', $slug)->first();

        if (!$registrar) {
            $this->error("Registrar not found: {$slug}");
            return self::FAILURE;
        }

        if (!$registrar->is_active) {
            $this->error("Registrar is not active: {$slug}");
            return self::FAILURE;
        }

        $this->info("Syncing TLD prices for registrar: {$registrar->name}");

        $tldCount = $registrar->activeTlds()->count();
        
        if ($tldCount === 0) {
            $this->warn("No active TLDs found for this registrar.");
            return self::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar($tldCount);
        $progressBar->start();

        $result = $syncService->syncTldPrices($registrar, function ($tld, $result) use ($progressBar) {
            $progressBar->advance();
        });

        $progressBar->finish();
        $this->newLine(2);

        if ($result['success']) {
            $stats = $result['stats'];
            
            $this->info("Price sync completed for {$registrar->name}!");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['TLDs Processed', $stats['total']],
                    ['Successfully Synced', $stats['synced']],
                    ['New Prices', $stats['new']],
                    ['Updated Prices', $stats['updated']],
                    ['Errors', $stats['errors']],
                ]
            );

            return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
        }

        $this->error("Failed to sync prices: {$result['error']}");
        return self::FAILURE;
    }

    protected function syncAllRegistrars(RegistrarSyncService $syncService): int
    {
        $registrars = Registrar::active()->get();

        if ($registrars->isEmpty()) {
            $this->warn("No active registrars found.");
            return self::SUCCESS;
        }

        $this->info("Syncing TLD prices for {$registrars->count()} registrar(s)...");
        $this->newLine();

        $allSuccess = true;

        foreach ($registrars as $registrar) {
            $this->line("Processing {$registrar->name}...");

            $tldCount = $registrar->activeTlds()->count();
            
            if ($tldCount === 0) {
                $this->warn("  No active TLDs for {$registrar->name}. Skipping.");
                continue;
            }

            $result = $syncService->syncTldPrices($registrar);

            if ($result['success']) {
                $stats = $result['stats'];
                $this->info("  ✓ Synced {$stats['synced']}/{$stats['total']} TLDs ({$stats['new']} new, {$stats['updated']} updated)");
            } else {
                $this->error("  ✗ Failed: {$result['error']}");
                $allSuccess = false;
            }
        }

        $this->newLine();
        $this->info("All registrars processed!");

        return $allSuccess ? self::SUCCESS : self::FAILURE;
    }
}
