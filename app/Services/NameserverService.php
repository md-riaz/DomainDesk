<?php

namespace App\Services;

use App\Exceptions\RegistrarException;
use App\Models\AuditLog;
use App\Models\Domain;
use App\Models\DomainNameserver;
use App\Services\Registrar\RegistrarFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class NameserverService
{
    const MIN_NAMESERVERS = 2;
    const MAX_NAMESERVERS = 4;

    /**
     * Update nameservers for a domain
     *
     * @param Domain $domain
     * @param array $nameservers Array of nameserver hostnames (2-4 items)
     * @param int|null $userId User performing the update
     * @return array ['success' => bool, 'message' => string, 'nameservers' => array]
     * @throws ValidationException
     */
    public function updateNameservers(Domain $domain, array $nameservers, ?int $userId = null): array
    {
        // Validate nameservers
        $this->validateNameservers($nameservers);

        // Normalize nameservers (trim, lowercase)
        $nameservers = $this->normalizeNameservers($nameservers);

        try {
            return DB::transaction(function () use ($domain, $nameservers, $userId) {
                // Step 1: Update via registrar
                $registrar = RegistrarFactory::make($domain->registrar_id);
                
                try {
                    $result = $registrar->updateNameservers($domain->name, $nameservers);
                    
                    if (!($result['success'] ?? false)) {
                        throw new RegistrarException(
                            $result['message'] ?? 'Failed to update nameservers at registrar'
                        );
                    }
                } catch (RegistrarException $e) {
                    Log::error('Nameserver update failed at registrar', [
                        'domain' => $domain->name,
                        'nameservers' => $nameservers,
                        'error' => $e->getMessage(),
                    ]);
                    
                    return [
                        'success' => false,
                        'message' => 'Failed to update nameservers: ' . $e->getMessage(),
                        'nameservers' => [],
                    ];
                }

                // Step 2: Update database records
                $this->updateDatabaseNameservers($domain, $nameservers);

                // Step 3: Create audit log
                AuditLog::create([
                    'partner_id' => $domain->partner_id,
                    'user_id' => $userId,
                    'auditable_type' => Domain::class,
                    'auditable_id' => $domain->id,
                    'action' => 'nameservers_updated',
                    'old_values' => ['nameservers' => $domain->nameservers->pluck('nameserver')->toArray()],
                    'new_values' => ['nameservers' => $nameservers],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                Log::info('Nameservers updated successfully', [
                    'domain' => $domain->name,
                    'nameservers' => $nameservers,
                ]);

                return [
                    'success' => true,
                    'message' => 'Nameservers updated successfully. Changes may take 24-48 hours to propagate.',
                    'nameservers' => $nameservers,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to update nameservers', [
                'domain' => $domain->name,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get current nameservers for a domain
     *
     * @param Domain $domain
     * @return array
     */
    public function getNameservers(Domain $domain): array
    {
        return $domain->nameservers()
            ->orderBy('order')
            ->pluck('nameserver')
            ->toArray();
    }

    /**
     * Get default nameservers from partner or registrar
     *
     * @param Domain $domain
     * @return array
     */
    public function getDefaultNameservers(Domain $domain): array
    {
        // Try to get from partner settings first
        $partner = $domain->partner;
        if ($partner && isset($partner->settings['default_nameservers'])) {
            $defaults = $partner->settings['default_nameservers'];
            if (is_array($defaults) && count($defaults) >= self::MIN_NAMESERVERS) {
                return array_slice($defaults, 0, self::MAX_NAMESERVERS);
            }
        }

        // Fallback to registrar defaults
        $registrar = $domain->registrar;
        if ($registrar) {
            return [
                'ns1.' . strtolower($registrar->name) . '.com',
                'ns2.' . strtolower($registrar->name) . '.com',
            ];
        }

        // Ultimate fallback
        return [
            'ns1.example.com',
            'ns2.example.com',
        ];
    }

    /**
     * Sync nameservers from registrar
     *
     * @param Domain $domain
     * @return array
     */
    public function syncNameservers(Domain $domain): array
    {
        try {
            $registrar = RegistrarFactory::make($domain->registrar_id);
            $result = $registrar->getInfo($domain->name);

            if (!($result['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch nameservers from registrar',
                ];
            }

            $nameservers = $result['nameservers'] ?? [];
            
            if (empty($nameservers)) {
                return [
                    'success' => false,
                    'message' => 'No nameservers returned from registrar',
                ];
            }

            // Update database
            $this->updateDatabaseNameservers($domain, $nameservers);

            return [
                'success' => true,
                'message' => 'Nameservers synchronized successfully',
                'nameservers' => $nameservers,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to sync nameservers', [
                'domain' => $domain->name,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to sync nameservers: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validate nameservers array
     *
     * @param array $nameservers
     * @throws ValidationException
     */
    protected function validateNameservers(array $nameservers): void
    {
        // Check count
        if (count($nameservers) < self::MIN_NAMESERVERS) {
            throw ValidationException::withMessages([
                'nameservers' => 'At least ' . self::MIN_NAMESERVERS . ' nameservers are required.',
            ]);
        }

        if (count($nameservers) > self::MAX_NAMESERVERS) {
            throw ValidationException::withMessages([
                'nameservers' => 'Maximum ' . self::MAX_NAMESERVERS . ' nameservers are allowed.',
            ]);
        }

        // Validate each nameserver
        foreach ($nameservers as $index => $nameserver) {
            if (empty($nameserver)) {
                throw ValidationException::withMessages([
                    "nameservers.{$index}" => 'Nameserver cannot be empty.',
                ]);
            }

            if (!$this->isValidHostname($nameserver)) {
                throw ValidationException::withMessages([
                    "nameservers.{$index}" => 'Invalid nameserver format: ' . $nameserver,
                ]);
            }
        }

        // Check for duplicates
        if (count($nameservers) !== count(array_unique($nameservers))) {
            throw ValidationException::withMessages([
                'nameservers' => 'Duplicate nameservers are not allowed.',
            ]);
        }
    }

    /**
     * Validate hostname format
     *
     * @param string $hostname
     * @return bool
     */
    protected function isValidHostname(string $hostname): bool
    {
        // Hostname validation: must be valid DNS hostname
        // Pattern: lowercase letters, numbers, dots, hyphens
        // Cannot start/end with hyphen or dot
        // Each label max 63 chars, total max 253 chars
        
        if (strlen($hostname) > 253) {
            return false;
        }

        $pattern = '/^(?!-)[a-z0-9-]{1,63}(?<!-)(\\.(?!-)[a-z0-9-]{1,63}(?<!-))*$/i';
        
        if (!preg_match($pattern, $hostname)) {
            return false;
        }

        // Must have at least one dot (FQDN)
        if (strpos($hostname, '.') === false) {
            return false;
        }

        return true;
    }

    /**
     * Normalize nameservers (trim, lowercase)
     *
     * @param array $nameservers
     * @return array
     */
    protected function normalizeNameservers(array $nameservers): array
    {
        return array_map(function ($ns) {
            return strtolower(trim($ns));
        }, $nameservers);
    }

    /**
     * Update nameserver records in database
     *
     * @param Domain $domain
     * @param array $nameservers
     */
    protected function updateDatabaseNameservers(Domain $domain, array $nameservers): void
    {
        // Delete existing nameservers
        $domain->nameservers()->delete();

        // Create new nameserver records
        foreach ($nameservers as $index => $nameserver) {
            DomainNameserver::create([
                'domain_id' => $domain->id,
                'nameserver' => $nameserver,
                'order' => $index + 1,
            ]);
        }

        // Refresh the relationship
        $domain->load('nameservers');
    }
}
