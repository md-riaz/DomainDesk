<?php

namespace App\Services;

use App\Contracts\RegistrarInterface;
use App\Enums\DomainStatus;
use App\Enums\PriceAction;
use App\Exceptions\RegistrarException;
use App\Models\AuditLog;
use App\Models\Domain;
use App\Models\DomainContact;
use App\Models\DomainNameserver;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\TldPrice;
use App\Services\Registrar\RegistrarFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistrarSyncService
{
    protected RegistrarFactory $registrarFactory;
    protected array $syncStats = [
        'total' => 0,
        'synced' => 0,
        'failed' => 0,
        'skipped' => 0,
        'changes' => 0,
    ];

    public function __construct(RegistrarFactory $registrarFactory)
    {
        $this->registrarFactory = $registrarFactory;
    }

    /**
     * Sync a single domain with its registrar.
     */
    public function syncDomain(Domain $domain, bool $force = false): array
    {
        $this->syncStats['total']++;

        // Skip if recently synced (unless forced)
        if (!$force && !$domain->needsSync($this->getMinSyncHours())) {
            $this->syncStats['skipped']++;
            Log::debug("Skipping domain sync (recently synced)", [
                'domain' => $domain->name,
                'last_synced_at' => $domain->last_synced_at,
            ]);
            return [
                'success' => true,
                'skipped' => true,
                'reason' => 'Recently synced',
            ];
        }

        if (!$domain->registrar_id) {
            $this->syncStats['skipped']++;
            return [
                'success' => true,
                'skipped' => true,
                'reason' => 'No registrar assigned',
            ];
        }

        try {
            $registrar = $this->registrarFactory->make($domain->registrar);
            $info = $registrar->getInfo($domain->name);

            $changes = $this->detectChanges($domain, $info);

            if (!empty($changes)) {
                $this->applyChanges($domain, $info, $changes);
                $this->syncStats['changes']++;
            }

            $domain->markAsSynced([
                'synced_at' => now()->toIso8601String(),
                'changes_detected' => count($changes),
                'registrar_status' => $info['status'] ?? null,
            ]);

            $this->syncStats['synced']++;

            Log::info("Domain synced successfully", [
                'domain' => $domain->name,
                'changes' => $changes,
            ]);

            return [
                'success' => true,
                'changes' => $changes,
                'info' => $info,
            ];

        } catch (RegistrarException $e) {
            $this->syncStats['failed']++;
            
            Log::error("Failed to sync domain", [
                'domain' => $domain->name,
                'error' => $e->getMessage(),
            ]);

            $domain->update([
                'sync_metadata' => [
                    'last_sync_attempt' => now()->toIso8601String(),
                    'last_sync_error' => $e->getMessage(),
                ],
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync multiple domains in batch.
     */
    public function syncDomains(Collection $domains, bool $force = false, ?callable $progressCallback = null): array
    {
        $this->resetStats();
        $results = [];

        foreach ($domains as $domain) {
            $result = $this->syncDomain($domain, $force);
            $results[$domain->name] = $result;

            if ($progressCallback) {
                $progressCallback($domain, $result);
            }
        }

        return [
            'stats' => $this->syncStats,
            'results' => $results,
        ];
    }

    /**
     * Sync domain status only (lighter operation).
     */
    public function syncDomainStatus(Domain $domain): array
    {
        try {
            $registrar = $this->registrarFactory->make($domain->registrar);
            $info = $registrar->getInfo($domain->name);

            $oldStatus = $domain->status;
            $newStatus = $this->mapRegistrarStatus($info['status'] ?? 'active');

            if ($oldStatus !== $newStatus) {
                $domain->update(['status' => $newStatus]);

                $this->logChange($domain, 'status', $oldStatus->value, $newStatus->value);

                return [
                    'success' => true,
                    'changed' => true,
                    'old_status' => $oldStatus->value,
                    'new_status' => $newStatus->value,
                ];
            }

            return [
                'success' => true,
                'changed' => false,
                'status' => $oldStatus->value,
            ];

        } catch (RegistrarException $e) {
            Log::error("Failed to sync domain status", [
                'domain' => $domain->name,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync TLD prices from registrar.
     */
    public function syncTldPrices(Registrar $registrar, ?callable $progressCallback = null): array
    {
        try {
            $registrarInstance = $this->registrarFactory->make($registrar);
            
            if (!method_exists($registrarInstance, 'getTldPricing')) {
                return [
                    'success' => false,
                    'error' => 'Registrar does not support price syncing',
                ];
            }

            $tlds = Tld::where('registrar_id', $registrar->id)->where('is_active', true)->get();
            $stats = [
                'total' => $tlds->count(),
                'synced' => 0,
                'updated' => 0,
                'new' => 0,
                'errors' => 0,
            ];

            foreach ($tlds as $tld) {
                try {
                    $pricing = $registrarInstance->getTldPricing($tld->extension);

                    foreach (PriceAction::cases() as $action) {
                        for ($years = $tld->min_years; $years <= $tld->max_years; $years++) {
                            $price = $pricing[$action->value][$years] ?? null;

                            if ($price !== null) {
                                $result = $this->updateTldPrice($tld, $action, $years, $price);
                                
                                if ($result['created']) {
                                    $stats['new']++;
                                } elseif ($result['updated']) {
                                    $stats['updated']++;
                                }
                            }
                        }
                    }

                    $stats['synced']++;

                    if ($progressCallback) {
                        $progressCallback($tld, ['success' => true]);
                    }

                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error("Failed to sync TLD prices", [
                        'tld' => $tld->extension,
                        'error' => $e->getMessage(),
                    ]);

                    if ($progressCallback) {
                        $progressCallback($tld, ['success' => false, 'error' => $e->getMessage()]);
                    }
                }
            }

            $registrar->updateLastSync();

            return [
                'success' => true,
                'stats' => $stats,
            ];

        } catch (\Exception $e) {
            Log::error("Failed to sync registrar prices", [
                'registrar' => $registrar->name,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Detect changes between local and registrar data.
     */
    protected function detectChanges(Domain $domain, array $info): array
    {
        $changes = [];

        // Check status
        $newStatus = $this->mapRegistrarStatus($info['status'] ?? 'active');
        if ($domain->status !== $newStatus) {
            $changes['status'] = [
                'old' => $domain->status->value,
                'new' => $newStatus->value,
            ];
        }

        // Check expiry date
        if (isset($info['expiry_date'])) {
            $newExpiry = $info['expiry_date'];
            $oldExpiry = $domain->expires_at?->format('Y-m-d');
            
            if ($oldExpiry !== $newExpiry) {
                $changes['expires_at'] = [
                    'old' => $oldExpiry,
                    'new' => $newExpiry,
                ];
            }
        }

        // Check nameservers
        if (isset($info['nameservers']) && is_array($info['nameservers'])) {
            $currentNs = $domain->nameservers->pluck('nameserver')->toArray();
            $registrarNs = array_filter($info['nameservers']);
            
            sort($currentNs);
            sort($registrarNs);
            
            if ($currentNs !== $registrarNs) {
                $changes['nameservers'] = [
                    'old' => $currentNs,
                    'new' => $registrarNs,
                ];
            }
        }

        return $changes;
    }

    /**
     * Apply changes to domain.
     */
    protected function applyChanges(Domain $domain, array $info, array $changes): void
    {
        DB::transaction(function () use ($domain, $info, $changes) {
            // Update status
            if (isset($changes['status'])) {
                $domain->update(['status' => $changes['status']['new']]);
                $this->logChange($domain, 'status', $changes['status']['old'], $changes['status']['new']);
            }

            // Update expiry date
            if (isset($changes['expires_at'])) {
                $domain->update(['expires_at' => $changes['expires_at']['new']]);
                $this->logChange($domain, 'expires_at', $changes['expires_at']['old'], $changes['expires_at']['new']);
            }

            // Update nameservers
            if (isset($changes['nameservers'])) {
                $domain->nameservers()->delete();
                
                foreach ($info['nameservers'] as $index => $ns) {
                    if (!empty($ns)) {
                        DomainNameserver::create([
                            'domain_id' => $domain->id,
                            'nameserver' => $ns,
                            'order' => $index + 1,
                        ]);
                    }
                }

                $this->logChange(
                    $domain,
                    'nameservers',
                    json_encode($changes['nameservers']['old']),
                    json_encode($changes['nameservers']['new'])
                );
            }

            // Sync contacts if available
            if (isset($info['contacts']) && is_array($info['contacts'])) {
                $this->syncContacts($domain, $info['contacts']);
            }
        });
    }

    /**
     * Sync domain contacts.
     */
    protected function syncContacts(Domain $domain, array $contacts): void
    {
        foreach ($contacts as $type => $contactData) {
            $existing = $domain->contacts()->where('type', $type)->first();

            $data = [
                'domain_id' => $domain->id,
                'type' => $type,
                'name' => $contactData['name'] ?? '',
                'organization' => $contactData['organization'] ?? null,
                'email' => $contactData['email'] ?? '',
                'phone' => $contactData['phone'] ?? null,
                'address' => $contactData['address'] ?? null,
                'city' => $contactData['city'] ?? null,
                'state' => $contactData['state'] ?? null,
                'postal_code' => $contactData['postal_code'] ?? null,
                'country' => $contactData['country'] ?? null,
            ];

            if ($existing) {
                $existing->update($data);
            } else {
                DomainContact::create($data);
            }
        }
    }

    /**
     * Update or create TLD price.
     */
    protected function updateTldPrice(Tld $tld, PriceAction $action, int $years, float $price): array
    {
        $currentPrice = $tld->prices()
            ->where('action', $action->value)
            ->where('years', $years)
            ->where('effective_date', '<=', now()->toDateString())
            ->orderBy('effective_date', 'desc')
            ->first();

        $result = ['created' => false, 'updated' => false];

        // Only create new price if it's different from current
        if (!$currentPrice || abs($currentPrice->price - $price) > 0.01) {
            $newPrice = TldPrice::create([
                'tld_id' => $tld->id,
                'action' => $action->value,
                'years' => $years,
                'price' => $price,
                'effective_date' => now()->toDateString(),
            ]);

            if ($currentPrice) {
                $result['updated'] = true;
                $changePercent = $newPrice->getPriceChange();

                // Log significant price changes (> 10%)
                if ($changePercent && abs($changePercent) > 10) {
                    Log::warning("Significant TLD price change detected", [
                        'tld' => $tld->extension,
                        'action' => $action->value,
                        'years' => $years,
                        'old_price' => $currentPrice->price,
                        'new_price' => $price,
                        'change_percent' => round($changePercent, 2),
                    ]);
                }
            } else {
                $result['created'] = true;
            }
        }

        return $result;
    }

    /**
     * Map registrar status to DomainStatus enum.
     */
    protected function mapRegistrarStatus(string $status): DomainStatus
    {
        return match (strtolower($status)) {
            'active', 'ok' => DomainStatus::Active,
            'expired' => DomainStatus::Expired,
            'grace', 'grace_period', 'autorenewperiod' => DomainStatus::GracePeriod,
            'redemption', 'redemptionperiod', 'pendingdelete' => DomainStatus::Redemption,
            'suspended', 'locked', 'hold' => DomainStatus::Suspended,
            'transferred', 'transferred_out' => DomainStatus::TransferredOut,
            'pending', 'pending_registration' => DomainStatus::PendingRegistration,
            default => DomainStatus::Active,
        };
    }

    /**
     * Log a change to audit log.
     */
    protected function logChange(Domain $domain, string $field, $oldValue, $newValue): void
    {
        AuditLog::create([
            'partner_id' => $domain->partner_id,
            'user_id' => null, // System sync
            'auditable_type' => Domain::class,
            'auditable_id' => $domain->id,
            'action' => 'sync_update',
            'metadata' => [
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'synced_from' => 'registrar',
            ],
        ]);
    }

    /**
     * Get minimum hours between syncs based on domain expiry.
     */
    protected function getMinSyncHours(): int
    {
        return config('domain.sync_interval_hours', 6);
    }

    /**
     * Get sync statistics.
     */
    public function getStats(): array
    {
        return $this->syncStats;
    }

    /**
     * Reset sync statistics.
     */
    protected function resetStats(): void
    {
        $this->syncStats = [
            'total' => 0,
            'synced' => 0,
            'failed' => 0,
            'skipped' => 0,
            'changes' => 0,
        ];
    }

    /**
     * Get domains that need syncing.
     */
    public function getDomainsNeedingSync(int $limit = 100): Collection
    {
        return Domain::whereNotNull('registrar_id')
            ->whereIn('status', [
                DomainStatus::Active,
                DomainStatus::GracePeriod,
                DomainStatus::Redemption,
            ])
            ->where(function ($query) {
                $query->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<=', now()->subHours($this->getMinSyncHours()));
            })
            ->orderByRaw('
                CASE 
                    WHEN expires_at <= ? THEN 1
                    WHEN expires_at <= ? THEN 2
                    WHEN last_synced_at IS NULL THEN 3
                    ELSE 4
                END
            ', [
                now()->addDays(30)->toDateString(),
                now()->addDays(60)->toDateString(),
            ])
            ->orderBy('last_synced_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get domains expiring soon.
     */
    public function getExpiringDomains(int $days = 30): Collection
    {
        return Domain::expiring($days)
            ->whereNotNull('registrar_id')
            ->orderBy('expires_at', 'asc')
            ->get();
    }
}
