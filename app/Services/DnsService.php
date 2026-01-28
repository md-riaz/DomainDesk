<?php

namespace App\Services;

use App\Enums\DnsRecordType;
use App\Exceptions\RegistrarException;
use App\Models\AuditLog;
use App\Models\Domain;
use App\Models\DomainDnsRecord;
use App\Services\Registrar\RegistrarFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DnsService
{
    const DEFAULT_TTL = 3600;
    const MIN_TTL = 60;
    const MAX_TTL = 86400;

    /**
     * Get DNS records for a domain
     *
     * @param Domain $domain
     * @param DnsRecordType|null $type Filter by record type
     * @return array
     */
    public function getDnsRecords(Domain $domain, ?DnsRecordType $type = null): array
    {
        $query = $domain->dnsRecords()->orderBy('type')->orderBy('name');

        if ($type) {
            $query->where('type', $type);
        }

        return $query->get()->toArray();
    }

    /**
     * Add a new DNS record
     *
     * @param Domain $domain
     * @param array $data ['type', 'name', 'value', 'ttl', 'priority', 'weight', 'port']
     * @param int|null $userId
     * @return array ['success' => bool, 'message' => string, 'record' => ?DomainDnsRecord]
     * @throws ValidationException
     */
    public function addDnsRecord(Domain $domain, array $data, ?int $userId = null): array
    {
        // Validate record data
        $this->validateDnsRecord($data);

        // Check if DNS management is supported
        if (!$this->isDnsManagementSupported($domain)) {
            return [
                'success' => false,
                'message' => 'DNS management is not supported for this domain.',
                'record' => null,
            ];
        }

        try {
            return DB::transaction(function () use ($domain, $data, $userId) {
                // Prepare record data
                $recordData = $this->prepareRecordData($data);

                // Step 1: Add via registrar (if supported)
                if ($this->shouldSyncWithRegistrar($domain)) {
                    try {
                        $result = $this->addRecordToRegistrar($domain, $recordData);
                        
                        if (!$result['success']) {
                            return [
                                'success' => false,
                                'message' => 'Failed to add DNS record at registrar: ' . ($result['message'] ?? 'Unknown error'),
                                'record' => null,
                            ];
                        }
                    } catch (RegistrarException $e) {
                        Log::error('Failed to add DNS record at registrar', [
                            'domain' => $domain->name,
                            'record' => $recordData,
                            'error' => $e->getMessage(),
                        ]);
                        
                        return [
                            'success' => false,
                            'message' => 'Failed to add DNS record: ' . $e->getMessage(),
                            'record' => null,
                        ];
                    }
                }

                // Step 2: Add to database
                $record = DomainDnsRecord::create([
                    'domain_id' => $domain->id,
                    'type' => $recordData['type'],
                    'name' => $recordData['name'],
                    'value' => $recordData['value'],
                    'ttl' => $recordData['ttl'],
                    'priority' => $recordData['priority'] ?? null,
                ]);

                // Step 3: Create audit log
                AuditLog::create([
                    'partner_id' => $domain->partner_id,
                    'user_id' => $userId,
                    'auditable_type' => DomainDnsRecord::class,
                    'auditable_id' => $record->id,
                    'action' => 'dns_record_created',
                    'old_values' => null,
                    'new_values' => $record->toArray(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                Log::info('DNS record added successfully', [
                    'domain' => $domain->name,
                    'record_type' => $recordData['type']->value,
                    'record_name' => $recordData['name'],
                ]);

                return [
                    'success' => true,
                    'message' => 'DNS record added successfully. Changes may take up to ' . ($recordData['ttl'] / 60) . ' minutes to propagate.',
                    'record' => $record,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to add DNS record', [
                'domain' => $domain->name,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update an existing DNS record
     *
     * @param DomainDnsRecord $record
     * @param array $data
     * @param int|null $userId
     * @return array
     */
    public function updateDnsRecord(DomainDnsRecord $record, array $data, ?int $userId = null): array
    {
        // Validate record data
        $this->validateDnsRecord($data);

        $domain = $record->domain;

        if (!$this->isDnsManagementSupported($domain)) {
            return [
                'success' => false,
                'message' => 'DNS management is not supported for this domain.',
            ];
        }

        try {
            return DB::transaction(function () use ($record, $data, $userId, $domain) {
                $oldValues = $record->toArray();
                $recordData = $this->prepareRecordData($data);

                // Step 1: Update via registrar
                if ($this->shouldSyncWithRegistrar($domain)) {
                    try {
                        // Delete old record and add new one
                        $this->deleteRecordFromRegistrar($domain, $record);
                        $result = $this->addRecordToRegistrar($domain, $recordData);
                        
                        if (!$result['success']) {
                            return [
                                'success' => false,
                                'message' => 'Failed to update DNS record at registrar',
                            ];
                        }
                    } catch (RegistrarException $e) {
                        Log::error('Failed to update DNS record at registrar', [
                            'domain' => $domain->name,
                            'error' => $e->getMessage(),
                        ]);
                        
                        return [
                            'success' => false,
                            'message' => 'Failed to update DNS record: ' . $e->getMessage(),
                        ];
                    }
                }

                // Step 2: Update database
                $record->update([
                    'type' => $recordData['type'],
                    'name' => $recordData['name'],
                    'value' => $recordData['value'],
                    'ttl' => $recordData['ttl'],
                    'priority' => $recordData['priority'] ?? null,
                ]);

                // Step 3: Create audit log
                AuditLog::create([
                    'partner_id' => $domain->partner_id,
                    'user_id' => $userId,
                    'auditable_type' => DomainDnsRecord::class,
                    'auditable_id' => $record->id,
                    'action' => 'dns_record_updated',
                    'old_values' => $oldValues,
                    'new_values' => $record->toArray(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                return [
                    'success' => true,
                    'message' => 'DNS record updated successfully.',
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to update DNS record', [
                'record_id' => $record->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete a DNS record
     *
     * @param DomainDnsRecord $record
     * @param int|null $userId
     * @return array
     */
    public function deleteDnsRecord(DomainDnsRecord $record, ?int $userId = null): array
    {
        $domain = $record->domain;

        if (!$this->isDnsManagementSupported($domain)) {
            return [
                'success' => false,
                'message' => 'DNS management is not supported for this domain.',
            ];
        }

        try {
            return DB::transaction(function () use ($record, $userId, $domain) {
                // Step 1: Delete from registrar
                if ($this->shouldSyncWithRegistrar($domain)) {
                    try {
                        $result = $this->deleteRecordFromRegistrar($domain, $record);
                        
                        if (!$result['success']) {
                            return [
                                'success' => false,
                                'message' => 'Failed to delete DNS record at registrar',
                            ];
                        }
                    } catch (RegistrarException $e) {
                        Log::error('Failed to delete DNS record at registrar', [
                            'domain' => $domain->name,
                            'error' => $e->getMessage(),
                        ]);
                        
                        return [
                            'success' => false,
                            'message' => 'Failed to delete DNS record: ' . $e->getMessage(),
                        ];
                    }
                }

                $recordData = $record->toArray();

                // Step 2: Create audit log before deletion
                AuditLog::create([
                    'partner_id' => $domain->partner_id,
                    'user_id' => $userId,
                    'auditable_type' => DomainDnsRecord::class,
                    'auditable_id' => $record->id,
                    'action' => 'dns_record_deleted',
                    'old_values' => $recordData,
                    'new_values' => null,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                // Step 3: Delete from database
                $record->delete();

                return [
                    'success' => true,
                    'message' => 'DNS record deleted successfully.',
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to delete DNS record', [
                'record_id' => $record->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync DNS records from registrar
     *
     * @param Domain $domain
     * @return array
     */
    public function syncDnsRecords(Domain $domain): array
    {
        if (!$this->isDnsManagementSupported($domain)) {
            return [
                'success' => false,
                'message' => 'DNS management is not supported for this domain.',
            ];
        }

        try {
            $registrar = RegistrarFactory::make($domain->registrar_id);
            $result = $registrar->getDnsRecords($domain->name);

            if (!($result['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch DNS records from registrar',
                ];
            }

            $records = $result['records'] ?? [];

            // Update database
            DB::transaction(function () use ($domain, $records) {
                // Clear existing records
                $domain->dnsRecords()->delete();

                // Add synced records
                foreach ($records as $recordData) {
                    DomainDnsRecord::create([
                        'domain_id' => $domain->id,
                        'type' => DnsRecordType::from($recordData['type']),
                        'name' => $recordData['name'],
                        'value' => $recordData['value'],
                        'ttl' => $recordData['ttl'] ?? self::DEFAULT_TTL,
                        'priority' => $recordData['priority'] ?? null,
                    ]);
                }
            });

            return [
                'success' => true,
                'message' => 'DNS records synchronized successfully',
                'count' => count($records),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to sync DNS records', [
                'domain' => $domain->name,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to sync DNS records: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check if DNS management is supported
     *
     * @param Domain $domain
     * @return bool
     */
    protected function isDnsManagementSupported(Domain $domain): bool
    {
        // Check if registrar supports DNS management
        $tld = $domain->registrar->tlds()
            ->where('extension', $this->extractTld($domain->name))
            ->first();

        return $tld && $tld->supports_dns;
    }

    /**
     * Check if should sync with registrar
     *
     * @param Domain $domain
     * @return bool
     */
    protected function shouldSyncWithRegistrar(Domain $domain): bool
    {
        // Only sync if DNS is managed by registrar (not using custom nameservers)
        $nameservers = $domain->nameservers->pluck('nameserver')->toArray();
        
        // If using registrar's nameservers, sync with registrar
        // This is a simplified check - in production you'd check against known registrar nameservers
        return !empty($nameservers);
    }

    /**
     * Validate DNS record data
     *
     * @param array $data
     * @throws ValidationException
     */
    protected function validateDnsRecord(array $data): void
    {
        $type = is_string($data['type'] ?? null) 
            ? DnsRecordType::from($data['type']) 
            : ($data['type'] ?? null);

        if (!$type instanceof DnsRecordType) {
            throw ValidationException::withMessages([
                'type' => 'Invalid DNS record type.',
            ]);
        }

        // Validate TTL
        $ttl = $data['ttl'] ?? self::DEFAULT_TTL;
        if ($ttl < self::MIN_TTL || $ttl > self::MAX_TTL) {
            throw ValidationException::withMessages([
                'ttl' => 'TTL must be between ' . self::MIN_TTL . ' and ' . self::MAX_TTL . ' seconds.',
            ]);
        }

        // Validate based on record type
        switch ($type) {
            case DnsRecordType::A:
                $this->validateARecord($data);
                break;
            case DnsRecordType::AAAA:
                $this->validateAAAARecord($data);
                break;
            case DnsRecordType::CNAME:
                $this->validateCNAMERecord($data);
                break;
            case DnsRecordType::MX:
                $this->validateMXRecord($data);
                break;
            case DnsRecordType::TXT:
                $this->validateTXTRecord($data);
                break;
            case DnsRecordType::NS:
                $this->validateNSRecord($data);
                break;
            case DnsRecordType::SRV:
                $this->validateSRVRecord($data);
                break;
        }
    }

    /**
     * Validate A record
     */
    protected function validateARecord(array $data): void
    {
        if (!filter_var($data['value'] ?? '', FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw ValidationException::withMessages([
                'value' => 'Invalid IPv4 address.',
            ]);
        }
    }

    /**
     * Validate AAAA record
     */
    protected function validateAAAARecord(array $data): void
    {
        if (!filter_var($data['value'] ?? '', FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw ValidationException::withMessages([
                'value' => 'Invalid IPv6 address.',
            ]);
        }
    }

    /**
     * Validate CNAME record
     */
    protected function validateCNAMERecord(array $data): void
    {
        if (!$this->isValidHostname($data['value'] ?? '')) {
            throw ValidationException::withMessages([
                'value' => 'Invalid hostname.',
            ]);
        }
    }

    /**
     * Validate MX record
     */
    protected function validateMXRecord(array $data): void
    {
        if (!isset($data['priority']) || !is_numeric($data['priority'])) {
            throw ValidationException::withMessages([
                'priority' => 'Priority is required for MX records.',
            ]);
        }

        $priority = (int) $data['priority'];
        if ($priority < 0 || $priority > 65535) {
            throw ValidationException::withMessages([
                'priority' => 'Priority must be between 0 and 65535.',
            ]);
        }

        if (!$this->isValidHostname($data['value'] ?? '')) {
            throw ValidationException::withMessages([
                'value' => 'Invalid mail server hostname.',
            ]);
        }
    }

    /**
     * Validate TXT record
     */
    protected function validateTXTRecord(array $data): void
    {
        $value = $data['value'] ?? '';
        if (strlen($value) > 255) {
            throw ValidationException::withMessages([
                'value' => 'TXT record value must not exceed 255 characters.',
            ]);
        }
    }

    /**
     * Validate NS record
     */
    protected function validateNSRecord(array $data): void
    {
        if (!$this->isValidHostname($data['value'] ?? '')) {
            throw ValidationException::withMessages([
                'value' => 'Invalid nameserver hostname.',
            ]);
        }
    }

    /**
     * Validate SRV record
     */
    protected function validateSRVRecord(array $data): void
    {
        // SRV format: priority weight port target
        // Value should be: "weight port target"
        // Priority is separate field
        
        if (!isset($data['priority']) || !is_numeric($data['priority'])) {
            throw ValidationException::withMessages([
                'priority' => 'Priority is required for SRV records.',
            ]);
        }

        $value = $data['value'] ?? '';
        $parts = explode(' ', $value);
        
        if (count($parts) !== 3) {
            throw ValidationException::withMessages([
                'value' => 'SRV record value must be in format: "weight port target"',
            ]);
        }

        [$weight, $port, $target] = $parts;

        if (!is_numeric($weight) || $weight < 0 || $weight > 65535) {
            throw ValidationException::withMessages([
                'value' => 'Invalid weight (must be 0-65535).',
            ]);
        }

        if (!is_numeric($port) || $port < 0 || $port > 65535) {
            throw ValidationException::withMessages([
                'value' => 'Invalid port (must be 0-65535).',
            ]);
        }

        if (!$this->isValidHostname($target)) {
            throw ValidationException::withMessages([
                'value' => 'Invalid target hostname.',
            ]);
        }
    }

    /**
     * Validate hostname
     */
    protected function isValidHostname(string $hostname): bool
    {
        if (strlen($hostname) > 253) {
            return false;
        }

        $pattern = '/^(?!-)[a-z0-9-]{1,63}(?<!-)(\\.(?!-)[a-z0-9-]{1,63}(?<!-))*$/i';
        
        return (bool) preg_match($pattern, $hostname);
    }

    /**
     * Prepare record data for storage
     */
    protected function prepareRecordData(array $data): array
    {
        $type = is_string($data['type'] ?? null) 
            ? DnsRecordType::from($data['type']) 
            : $data['type'];

        return [
            'type' => $type,
            'name' => trim($data['name'] ?? '@'),
            'value' => trim($data['value']),
            'ttl' => $data['ttl'] ?? self::DEFAULT_TTL,
            'priority' => $type->supportsPriority() ? ($data['priority'] ?? null) : null,
        ];
    }

    /**
     * Add record to registrar
     */
    protected function addRecordToRegistrar(Domain $domain, array $recordData): array
    {
        $registrar = RegistrarFactory::make($domain->registrar_id);
        
        return $registrar->updateDnsRecords($domain->name, [
            'action' => 'add',
            'records' => [$recordData],
        ]);
    }

    /**
     * Delete record from registrar
     */
    protected function deleteRecordFromRegistrar(Domain $domain, DomainDnsRecord $record): array
    {
        $registrar = RegistrarFactory::make($domain->registrar_id);
        
        return $registrar->updateDnsRecords($domain->name, [
            'action' => 'delete',
            'records' => [$record->toArray()],
        ]);
    }

    /**
     * Extract TLD from domain name
     */
    protected function extractTld(string $domain): string
    {
        $parts = explode('.', $domain);
        return '.' . end($parts);
    }
}
