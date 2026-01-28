# Registrar Sync Service - Quick Start Guide

## Overview

The Registrar Sync Service keeps your domain data synchronized with registrar APIs automatically. This guide will help you get started quickly.

## Installation

The service is already installed. Just run migrations if not done yet:

```bash
php artisan migrate
```

## Quick Commands

### Sync a Single Domain

```bash
# Sync one domain
php artisan domain:sync example.com

# Force sync (ignore recent sync)
php artisan domain:sync example.com --force
```

### Sync Multiple Domains

```bash
# Sync domains that need syncing (default: 100)
php artisan domain:sync

# Sync with custom limit
php artisan domain:sync --limit=500

# Sync for specific partner
php artisan domain:sync --partner=1
```

### Status-Only Sync (Fast)

```bash
# Sync domains expiring within 30 days
php artisan domain:sync-status

# Sync domains expiring within 60 days
php artisan domain:sync-status --days=60

# Sync all active domains
php artisan domain:sync-status --all --limit=200
```

### TLD Price Sync

```bash
# Sync prices from one registrar
php artisan tld:sync-prices mock

# Sync prices from all registrars
php artisan tld:sync-prices
```

## Automatic Syncing

The service runs automatically via Laravel scheduler:

**Schedule:**
- **2:00 AM daily** - Status sync for expiring domains
- **3:00 AM Sunday** - Full sync of all domains
- **4:00 AM daily** - TLD price sync

**Enable scheduler:**
```bash
# Add to crontab
* * * * * cd /path/to/domaindesk && php artisan schedule:run >> /dev/null 2>&1
```

## Programmatic Usage

### Sync Service

```php
use App\Services\RegistrarSyncService;
use App\Models\Domain;

$syncService = app(RegistrarSyncService::class);
$domain = Domain::find(1);

// Sync single domain
$result = $syncService->syncDomain($domain);

// Sync batch
$domains = Domain::whereNotNull('registrar_id')->limit(100)->get();
$result = $syncService->syncDomains($domains);

// Get domains needing sync
$domains = $syncService->getDomainsNeedingSync(100);

// Get expiring domains
$expiring = $syncService->getExpiringDomains(30);
```

### Queue Processing

```php
use App\Jobs\SyncDomainJob;

// Queue single domain
SyncDomainJob::dispatch($domain);

// Queue with delay
SyncDomainJob::dispatch($domain)->delay(now()->addMinutes(5));

// Batch queue
foreach ($domains as $domain) {
    SyncDomainJob::dispatch($domain);
}
```

## Configuration

Edit `.env` to customize:

```env
# Minimum hours between syncs
DOMAIN_SYNC_INTERVAL_HOURS=6

# Maximum domains per batch
DOMAIN_SYNC_BATCH_SIZE=100

# Priority sync threshold (days until expiry)
DOMAIN_PRIORITY_SYNC_DAYS=30

# Timeout per sync (seconds)
DOMAIN_SYNC_TIMEOUT=120

# Number of retry attempts
DOMAIN_SYNC_RETRIES=3

# Delay between retries (seconds)
DOMAIN_SYNC_RETRY_DELAY=300

# Price change alert threshold (percentage)
DOMAIN_PRICE_CHANGE_THRESHOLD=10

# Auto-queue threshold
DOMAIN_AUTO_QUEUE_THRESHOLD=50
```

## Monitoring

### Check Sync Status

```php
// Check if domain needs syncing
$domain->needsSync(); // true/false

// Check last sync time
$domain->last_synced_at; // Carbon datetime

// View sync metadata
$domain->sync_metadata; // array
```

### View Statistics

```php
$syncService = app(RegistrarSyncService::class);
$syncService->syncDomains($domains);
$stats = $syncService->getStats();

// Returns:
// [
//     'total' => 100,
//     'synced' => 95,
//     'failed' => 2,
//     'skipped' => 3,
//     'changes' => 12,
// ]
```

### View Audit Logs

```php
use App\Models\AuditLog;

// View sync changes for a domain
$logs = AuditLog::where('auditable_type', Domain::class)
    ->where('auditable_id', $domain->id)
    ->where('action', 'sync_update')
    ->latest()
    ->get();

foreach ($logs as $log) {
    $field = $log->metadata['field'];
    $old = $log->metadata['old_value'];
    $new = $log->metadata['new_value'];
    echo "$field changed from $old to $new\n";
}
```

## Common Issues

### Domain Not Syncing

**Problem:** Domain not being synced despite being eligible.

**Solutions:**
1. Check registrar assignment: `$domain->registrar_id`
2. Check last sync: `$domain->last_synced_at`
3. Force sync: `php artisan domain:sync example.com --force`
4. Check logs: `tail -f storage/logs/laravel.log`

### API Rate Limits

**Problem:** Hitting registrar API rate limits.

**Solutions:**
1. Increase sync interval: `DOMAIN_SYNC_INTERVAL_HOURS=12`
2. Reduce batch sizes: `DOMAIN_SYNC_BATCH_SIZE=50`
3. Adjust scheduled task limits
4. Use queue for async processing

### Failed Syncs

**Problem:** Syncs failing with errors.

**Solutions:**
1. Check registrar credentials
2. Verify registrar API is operational
3. Check domain exists at registrar
4. Review error logs
5. Retry manually with force flag

## Best Practices

1. **Use Status Sync for Frequent Checks**
   - Lightweight operation
   - Good for expiring domains
   - Less API usage

2. **Schedule Full Syncs Weekly**
   - Comprehensive data update
   - Catches nameserver changes
   - Updates contact information

3. **Queue Large Operations**
   - Use SyncDomainJob for 50+ domains
   - Prevents timeouts
   - Better resource management

4. **Monitor Price Changes**
   - Check logs for significant changes
   - Update customer pricing accordingly
   - Notify partners of increases

5. **Priority Sync Expiring Domains**
   - Ensure critical domains are current
   - Enable accurate renewal reminders
   - Prevent service disruptions

## Performance Tips

1. **Batch by Registrar**
   ```php
   $domains->groupBy('registrar_id')->each(function($registrarDomains) {
       $syncService->syncDomains($registrarDomains);
   });
   ```

2. **Use Progress Callbacks**
   ```php
   $syncService->syncDomains($domains, false, function($domain, $result) {
       echo "Synced: {$domain->name}\n";
   });
   ```

3. **Limit Query Results**
   ```php
   // Only active domains with registrars
   $domains = Domain::whereNotNull('registrar_id')
       ->where('status', 'active')
       ->limit(100)
       ->get();
   ```

4. **Use Queue Workers**
   ```bash
   # Start queue workers
   php artisan queue:work --queue=domain-sync --tries=3
   ```

## Need Help?

- See full documentation: `PHASE_3.4_REGISTRAR_SYNC_SUMMARY.md`
- Check test examples: `tests/Feature/Services/RegistrarSyncServiceTest.php`
- Review command help: `php artisan help domain:sync`
