<?php

namespace App\Services\Registrar;

use App\Exceptions\RegistrarException;
use Illuminate\Support\Facades\Cache;

/**
 * Mock Registrar for testing and development.
 * 
 * Features:
 * - Realistic fake data for all operations
 * - Configurable delays to simulate network latency
 * - Configurable failure rates for testing error handling
 * - State management (stores domain data in cache)
 * - Operation history tracking
 * - Comprehensive validation
 */
class MockRegistrar extends AbstractRegistrar
{
    /**
     * Simulate delays in API responses.
     */
    protected bool $simulateDelays = false;

    /**
     * Default delay in milliseconds.
     */
    protected int $defaultDelayMs = 100;

    /**
     * Failure rate (0-100).
     */
    protected int $failureRate = 0;

    /**
     * Available TLDs and their pricing.
     */
    protected array $availableTlds = [];

    /**
     * Unavailable domain patterns.
     */
    protected array $unavailablePatterns = [];

    /**
     * Cache key prefix for state storage.
     */
    protected string $statePrefix = 'mock_registrar:';

    /**
     * Cache TTL for domain state (in seconds).
     */
    protected int $stateTtl = 3600;

    /**
     * Track operation history.
     */
    protected bool $trackHistory = true;

    /**
     * Initialize mock registrar.
     */
    protected function initialize(): void
    {
        $this->simulateDelays = $this->config['simulate_delays'] ?? false;
        $this->defaultDelayMs = $this->config['default_delay_ms'] ?? 100;
        $this->failureRate = $this->config['failure_rate'] ?? 0;
        $this->trackHistory = $this->config['track_history'] ?? true;
        $this->stateTtl = $this->config['state_ttl'] ?? 3600;

        // Default available TLDs with pricing
        $this->availableTlds = $this->config['available_tlds'] ?? [
            'com' => ['register' => 1200, 'renew' => 1200, 'transfer' => 1200],
            'net' => ['register' => 1400, 'renew' => 1400, 'transfer' => 1400],
            'org' => ['register' => 1500, 'renew' => 1500, 'transfer' => 1500],
            'io' => ['register' => 3500, 'renew' => 3500, 'transfer' => 3500],
            'app' => ['register' => 1800, 'renew' => 1800, 'transfer' => 1800],
        ];

        // Default patterns for unavailable domains
        $this->unavailablePatterns = $this->config['unavailable_patterns'] ?? [
            'taken.com',
            'unavailable',
            'registered',
        ];
    }

    /**
     * Check domain availability.
     */
    public function checkAvailability(string $domain): bool
    {
        return $this->executeApiCall('checkAvailability', function () use ($domain) {
            $this->validateDomain($domain);
            $this->simulateDelay();
            $this->simulateFailure();

            // Check if TLD is supported
            $tld = $this->extractTld($domain);
            if (!isset($this->availableTlds[$tld])) {
                throw RegistrarException::invalidData(
                    $this->name,
                    "TLD '.{$tld}' is not supported",
                    ['domain' => $domain, 'supported_tlds' => array_keys($this->availableTlds)]
                );
            }

            // Check unavailable patterns
            foreach ($this->unavailablePatterns as $pattern) {
                if (str_contains($domain, $pattern)) {
                    return false;
                }
            }

            // Check if domain is already registered in mock state
            if ($this->getDomainState($domain)) {
                return false;
            }

            // For testing: domains containing these keywords are always available
            $alwaysAvailableKeywords = ['test', 'mock', 'demo', 'unique', 'example-', 'custom', 'lock', 'unlock'];
            foreach ($alwaysAvailableKeywords as $keyword) {
                if (str_contains($domain, $keyword)) {
                    return true;
                }
            }

            // Randomize availability for other domains (deterministic based on domain name)
            $seed = crc32($domain) % 100;
            return $seed < 70;
        }, ['domain' => $domain]);
    }

    /**
     * Register a new domain.
     */
    public function register(array $data): array
    {
        return $this->executeApiCall('register', function () use ($data) {
            $this->validateRequired($data, ['domain', 'years', 'contacts']);
            $this->validateDomain($data['domain']);
            $this->validateYears($data['years']);
            $this->validateContacts($data['contacts']);
            
            // Validate nameservers if provided
            if (isset($data['nameservers'])) {
                $this->validateNameservers($data['nameservers']);
            }
            
            $this->simulateDelay();
            $this->simulateFailure();

            // Check if domain is available
            if (!$this->checkAvailability($data['domain'])) {
                throw RegistrarException::invalidData(
                    $this->name,
                    'Domain is not available for registration',
                    ['domain' => $data['domain']]
                );
            }

            $orderId = 'MOCK-' . strtoupper(uniqid());
            $expiryDate = now()->addYears($data['years']);
            $nameservers = $data['nameservers'] ?? ['ns1.example.com', 'ns2.example.com'];

            // Store domain state
            $domainState = [
                'domain' => $data['domain'],
                'status' => 'active',
                'order_id' => $orderId,
                'registered_at' => now()->toIso8601String(),
                'expiry_date' => $expiryDate->toIso8601String(),
                'auto_renew' => $data['auto_renew'] ?? false,
                'locked' => true,
                'nameservers' => $nameservers,
                'contacts' => $data['contacts'],
            ];
            
            $this->saveDomainState($data['domain'], $domainState);
            $this->logOperation('register', $data['domain'], $domainState);

            return $this->successResponse(
                data: [
                    'domain' => $data['domain'],
                    'order_id' => $orderId,
                    'status' => 'active',
                    'expiry_date' => $expiryDate->toIso8601String(),
                    'auto_renew' => $data['auto_renew'] ?? false,
                    'nameservers' => $nameservers,
                ],
                message: 'Domain registered successfully'
            );
        }, $data);
    }

    /**
     * Renew a domain.
     */
    public function renew(string $domain, int $years): array
    {
        return $this->executeApiCall('renew', function () use ($domain, $years) {
            $this->validateDomain($domain);
            $this->validateYears($years);
            $this->simulateDelay();
            $this->simulateFailure();

            // Check if domain exists
            $state = $this->getDomainState($domain);
            if (!$state) {
                throw RegistrarException::domainNotFound($this->name, $domain);
            }

            $orderId = 'MOCK-RENEW-' . strtoupper(uniqid());
            $currentExpiry = \Carbon\Carbon::parse($state['expiry_date']);
            $newExpiry = $currentExpiry->addYears($years);

            // Update domain state
            $state['expiry_date'] = $newExpiry->toIso8601String();
            $state['renewed_at'] = now()->toIso8601String();
            $this->saveDomainState($domain, $state);
            $this->logOperation('renew', $domain, ['years' => $years, 'new_expiry' => $newExpiry->toIso8601String()]);

            return $this->successResponse(
                data: [
                    'domain' => $domain,
                    'order_id' => $orderId,
                    'years_renewed' => $years,
                    'previous_expiry_date' => $currentExpiry->toIso8601String(),
                    'new_expiry_date' => $newExpiry->toIso8601String(),
                ],
                message: 'Domain renewed successfully'
            );
        }, ['domain' => $domain, 'years' => $years]);
    }

    /**
     * Transfer a domain.
     */
    public function transfer(string $domain, string $authCode): array
    {
        return $this->executeApiCall('transfer', function () use ($domain, $authCode) {
            $this->validateDomain($domain);
            $this->simulateDelay();
            $this->simulateFailure();

            if (empty($authCode)) {
                throw RegistrarException::invalidData(
                    $this->name,
                    'Authorization code is required for transfer',
                    ['domain' => $domain]
                );
            }

            $transferId = 'MOCK-TRANSFER-' . strtoupper(uniqid());
            $estimatedCompletion = now()->addDays(5);

            // Create transfer state (pending)
            $transferState = [
                'domain' => $domain,
                'transfer_id' => $transferId,
                'status' => 'pending',
                'initiated_at' => now()->toIso8601String(),
                'estimated_completion' => $estimatedCompletion->toIso8601String(),
                'auth_code' => $authCode,
            ];

            $this->saveTransferState($domain, $transferState);
            $this->logOperation('transfer', $domain, $transferState);

            return $this->successResponse(
                data: [
                    'domain' => $domain,
                    'transfer_id' => $transferId,
                    'status' => 'pending',
                    'initiated_at' => now()->toIso8601String(),
                    'estimated_completion' => $estimatedCompletion->toIso8601String(),
                ],
                message: 'Domain transfer initiated'
            );
        }, ['domain' => $domain, 'auth_code' => '***']);
    }

    /**
     * Update nameservers.
     */
    public function updateNameservers(string $domain, array $nameservers): array
    {
        return $this->executeApiCall('updateNameservers', function () use ($domain, $nameservers) {
            $this->validateDomain($domain);
            $this->validateNameservers($nameservers);
            $this->simulateDelay();
            $this->simulateFailure();

            // Check if domain exists
            $state = $this->getDomainState($domain);
            if (!$state) {
                throw RegistrarException::domainNotFound($this->name, $domain);
            }

            // Update nameservers in state
            $state['nameservers'] = $nameservers;
            $state['nameservers_updated_at'] = now()->toIso8601String();
            $this->saveDomainState($domain, $state);
            $this->logOperation('updateNameservers', $domain, ['nameservers' => $nameservers]);

            return $this->successResponse(
                data: [
                    'domain' => $domain,
                    'nameservers' => $nameservers,
                    'updated_at' => now()->toIso8601String(),
                ],
                message: 'Nameservers updated successfully'
            );
        }, ['domain' => $domain, 'nameservers' => $nameservers]);
    }

    /**
     * Get domain contacts.
     */
    public function getContacts(string $domain): array
    {
        return $this->executeApiCall('getContacts', function () use ($domain) {
            $this->validateDomain($domain);
            $this->simulateDelay();
            $this->simulateFailure();

            // Try to get from state, otherwise return mock data
            $state = $this->getDomainState($domain);
            $contacts = $state['contacts'] ?? [
                'registrant' => $this->getMockContact('registrant'),
                'admin' => $this->getMockContact('admin'),
                'tech' => $this->getMockContact('tech'),
                'billing' => $this->getMockContact('billing'),
            ];

            return $this->successResponse(
                data: $contacts,
                message: 'Contacts retrieved successfully'
            );
        }, ['domain' => $domain]);
    }

    /**
     * Update domain contacts.
     */
    public function updateContacts(string $domain, array $contacts): array
    {
        return $this->executeApiCall('updateContacts', function () use ($domain, $contacts) {
            $this->validateDomain($domain);
            $this->validateContacts($contacts);
            $this->simulateDelay();
            $this->simulateFailure();

            // Check if domain exists
            $state = $this->getDomainState($domain);
            if (!$state) {
                throw RegistrarException::domainNotFound($this->name, $domain);
            }

            // Update contacts in state
            $state['contacts'] = array_merge($state['contacts'] ?? [], $contacts);
            $state['contacts_updated_at'] = now()->toIso8601String();
            $this->saveDomainState($domain, $state);
            $this->logOperation('updateContacts', $domain, ['contact_types' => array_keys($contacts)]);

            return $this->successResponse(
                data: [
                    'domain' => $domain,
                    'contacts' => $state['contacts'],
                    'updated_at' => now()->toIso8601String(),
                ],
                message: 'Contacts updated successfully'
            );
        }, ['domain' => $domain, 'contacts' => $this->sanitizeContactsForLog($contacts)]);
    }

    /**
     * Get DNS records.
     */
    public function getDnsRecords(string $domain): array
    {
        return $this->executeApiCall('getDnsRecords', function () use ($domain) {
            $this->validateDomain($domain);
            $this->simulateDelay();
            $this->simulateFailure();

            // Try to get from state, otherwise return mock data
            $state = $this->getDomainState($domain);
            $records = $state['dns_records'] ?? [
                ['type' => 'A', 'name' => '@', 'value' => '192.0.2.1', 'ttl' => 3600],
                ['type' => 'A', 'name' => 'www', 'value' => '192.0.2.1', 'ttl' => 3600],
                ['type' => 'MX', 'name' => '@', 'value' => 'mail.example.com', 'priority' => 10, 'ttl' => 3600],
                ['type' => 'TXT', 'name' => '@', 'value' => 'v=spf1 include:_spf.example.com ~all', 'ttl' => 3600],
            ];

            return $this->successResponse(
                data: ['records' => $records],
                message: 'DNS records retrieved successfully'
            );
        }, ['domain' => $domain]);
    }

    /**
     * Update DNS records.
     */
    public function updateDnsRecords(string $domain, array $records): array
    {
        return $this->executeApiCall('updateDnsRecords', function () use ($domain, $records) {
            $this->validateDomain($domain);
            $this->validateDnsRecords($records);
            $this->simulateDelay();
            $this->simulateFailure();

            // Check if domain exists
            $state = $this->getDomainState($domain);
            if (!$state) {
                throw RegistrarException::domainNotFound($this->name, $domain);
            }

            // Update DNS records in state
            $state['dns_records'] = $records;
            $state['dns_updated_at'] = now()->toIso8601String();
            $this->saveDomainState($domain, $state);
            $this->logOperation('updateDnsRecords', $domain, ['record_count' => count($records)]);

            return $this->successResponse(
                data: [
                    'domain' => $domain,
                    'records' => $records,
                    'updated_at' => now()->toIso8601String(),
                ],
                message: 'DNS records updated successfully'
            );
        }, ['domain' => $domain, 'records' => $records]);
    }

    /**
     * Get domain information.
     */
    public function getInfo(string $domain): array
    {
        return $this->executeApiCall('getInfo', function () use ($domain) {
            $this->validateDomain($domain);
            $this->simulateDelay();
            $this->simulateFailure();

            // Try to get from state, otherwise return mock data
            $state = $this->getDomainState($domain);
            
            if ($state) {
                return $this->successResponse(
                    data: $state,
                    message: 'Domain information retrieved successfully'
                );
            }

            // Return mock data for unregistered domains
            return $this->successResponse(
                data: [
                    'domain' => $domain,
                    'status' => 'active',
                    'created_at' => now()->subYears(2)->toIso8601String(),
                    'updated_at' => now()->subMonths(3)->toIso8601String(),
                    'expiry_date' => now()->addYear()->toIso8601String(),
                    'auto_renew' => true,
                    'locked' => true,
                    'nameservers' => ['ns1.example.com', 'ns2.example.com'],
                ],
                message: 'Domain information retrieved successfully'
            );
        }, ['domain' => $domain]);
    }

    /**
     * Lock a domain.
     */
    public function lock(string $domain): bool
    {
        return $this->executeApiCall('lock', function () use ($domain) {
            $this->validateDomain($domain);
            $this->simulateDelay();
            $this->simulateFailure();

            // Update lock status in state
            $state = $this->getDomainState($domain);
            if ($state) {
                $state['locked'] = true;
                $state['locked_at'] = now()->toIso8601String();
                $this->saveDomainState($domain, $state);
            }
            
            $this->logOperation('lock', $domain, ['locked' => true]);

            return true;
        }, ['domain' => $domain]);
    }

    /**
     * Unlock a domain.
     */
    public function unlock(string $domain): bool
    {
        return $this->executeApiCall('unlock', function () use ($domain) {
            $this->validateDomain($domain);
            $this->simulateDelay();
            $this->simulateFailure();

            // Update lock status in state
            $state = $this->getDomainState($domain);
            if ($state) {
                $state['locked'] = false;
                $state['unlocked_at'] = now()->toIso8601String();
                $this->saveDomainState($domain, $state);
            }
            
            $this->logOperation('unlock', $domain, ['locked' => false]);

            return true;
        }, ['domain' => $domain]);
    }

    /**
     * Test connection.
     */
    public function testConnection(): bool
    {
        $this->simulateDelay();
        return true;
    }

    /**
     * Make HTTP request (mock implementation).
     */
    protected function makeRequest(string $endpoint, string $method = 'GET', array $data = []): mixed
    {
        $this->simulateDelay();
        
        return [
            'status' => 'success',
            'endpoint' => $endpoint,
            'method' => $method,
            'data' => $data,
        ];
    }

    /**
     * Simulate API delay.
     */
    protected function simulateDelay(): void
    {
        if ($this->simulateDelays) {
            usleep($this->defaultDelayMs * 1000);
        }
    }

    /**
     * Simulate random failures based on failure rate.
     */
    protected function simulateFailure(): void
    {
        if ($this->failureRate > 0 && random_int(1, 100) <= $this->failureRate) {
            throw RegistrarException::connectionFailed(
                $this->name,
                'Simulated failure for testing'
            );
        }
    }

    /**
     * Get mock contact data.
     */
    protected function getMockContact(string $type): array
    {
        return [
            'name' => 'John Doe',
            'company' => 'Example Corp',
            'email' => strtolower($type) . '@example.com',
            'phone' => '+1.5555551234',
            'address' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'zip' => '12345',
            'country' => 'US',
        ];
    }

    /**
     * Extract TLD from domain.
     */
    protected function extractTld(string $domain): string
    {
        $parts = explode('.', $domain);
        return end($parts);
    }

    /**
     * Get domain state from cache.
     */
    protected function getDomainState(string $domain): ?array
    {
        return Cache::get($this->statePrefix . 'domain:' . $domain);
    }

    /**
     * Save domain state to cache.
     */
    protected function saveDomainState(string $domain, array $state): void
    {
        Cache::put($this->statePrefix . 'domain:' . $domain, $state, $this->stateTtl);
    }

    /**
     * Get transfer state from cache.
     */
    protected function getTransferState(string $domain): ?array
    {
        return Cache::get($this->statePrefix . 'transfer:' . $domain);
    }

    /**
     * Save transfer state to cache.
     */
    protected function saveTransferState(string $domain, array $state): void
    {
        Cache::put($this->statePrefix . 'transfer:' . $domain, $state, $this->stateTtl);
    }

    /**
     * Log operation to history.
     */
    protected function logOperation(string $operation, string $domain, array $data): void
    {
        if (!$this->trackHistory) {
            return;
        }

        $historyLimit = $this->config['history_limit'] ?? 100;
        $history = Cache::get($this->statePrefix . 'history', []);
        $history[] = [
            'operation' => $operation,
            'domain' => $domain,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ];

        // Keep last N operations
        if (count($history) > $historyLimit) {
            $history = array_slice($history, -$historyLimit);
        }

        Cache::put($this->statePrefix . 'history', $history, $this->stateTtl);
    }

    /**
     * Get operation history.
     */
    public function getOperationHistory(?string $domain = null): array
    {
        $history = Cache::get($this->statePrefix . 'history', []);

        if ($domain) {
            return array_filter($history, fn($item) => $item['domain'] === $domain);
        }

        return $history;
    }

    /**
     * Clear all mock state.
     */
    public function clearState(): void
    {
        Cache::forget($this->statePrefix . 'history');
        
        // Clear all domain states (this is a simplified approach)
        // In production, you might want to track all keys or use cache tags
    }

    /**
     * Validate years parameter.
     */
    protected function validateYears(int $years): void
    {
        if ($years < 1 || $years > 10) {
            throw RegistrarException::invalidData(
                $this->name,
                'Years must be between 1 and 10',
                ['years' => $years]
            );
        }
    }

    /**
     * Validate nameservers (must be 2-4 nameservers).
     */
    protected function validateNameservers(array $nameservers): void
    {
        $count = count($nameservers);
        
        if ($count < 2 || $count > 4) {
            throw RegistrarException::invalidData(
                $this->name,
                'Must provide between 2 and 4 nameservers',
                ['provided' => $count, 'nameservers' => $nameservers]
            );
        }

        foreach ($nameservers as $ns) {
            if (!preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/i', $ns)) {
                throw RegistrarException::invalidData(
                    $this->name,
                    'Invalid nameserver format',
                    ['nameserver' => $ns]
                );
            }
        }
    }

    /**
     * Validate contacts data.
     */
    protected function validateContacts(array $contacts): void
    {
        $requiredTypes = ['registrant'];
        $validTypes = ['registrant', 'admin', 'tech', 'billing'];

        foreach ($requiredTypes as $type) {
            if (!isset($contacts[$type])) {
                throw RegistrarException::invalidData(
                    $this->name,
                    "Missing required contact type: {$type}",
                    ['provided_types' => array_keys($contacts)]
                );
            }
        }

        foreach ($contacts as $type => $data) {
            if (!in_array($type, $validTypes)) {
                throw RegistrarException::invalidData(
                    $this->name,
                    "Invalid contact type: {$type}",
                    ['valid_types' => $validTypes]
                );
            }

            if (!is_array($data)) {
                throw RegistrarException::invalidData(
                    $this->name,
                    "Contact data must be an array for type: {$type}",
                    ['type' => $type]
                );
            }
        }
    }

    /**
     * Validate DNS records.
     */
    protected function validateDnsRecords(array $records): void
    {
        $validTypes = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SRV', 'CAA'];

        foreach ($records as $index => $record) {
            if (!isset($record['type'], $record['name'], $record['value'])) {
                throw RegistrarException::invalidData(
                    $this->name,
                    'DNS record must have type, name, and value',
                    ['index' => $index, 'record' => $record]
                );
            }

            if (!in_array($record['type'], $validTypes)) {
                throw RegistrarException::invalidData(
                    $this->name,
                    "Invalid DNS record type: {$record['type']}",
                    ['valid_types' => $validTypes]
                );
            }

            // Validate MX records have priority
            if ($record['type'] === 'MX' && !isset($record['priority'])) {
                throw RegistrarException::invalidData(
                    $this->name,
                    'MX records must have a priority',
                    ['record' => $record]
                );
            }
        }
    }

    /**
     * Sanitize contacts for logging (remove sensitive data).
     */
    protected function sanitizeContactsForLog(array $contacts): array
    {
        $sanitized = [];
        
        foreach ($contacts as $type => $data) {
            $sanitized[$type] = [
                'name' => $data['name'] ?? '***',
            ];
            
            // Safely sanitize email
            if (isset($data['email']) && str_contains($data['email'], '@')) {
                $parts = explode('@', $data['email']);
                $sanitized[$type]['email'] = '***@' . $parts[1];
            } else {
                $sanitized[$type]['email'] = '***';
            }
        }

        return $sanitized;
    }

    /**
     * Get transfer status.
     */
    public function getTransferStatus(string $domain): array
    {
        return $this->executeApiCall('getTransferStatus', function () use ($domain) {
            $this->validateDomain($domain);
            $this->simulateDelay();
            $this->simulateFailure();

            $transferState = $this->getTransferState($domain);
            if (!$transferState) {
                throw RegistrarException::domainNotFound($this->name, "Transfer not found for: {$domain}");
            }

            // Simulate transfer progress over time
            $initiatedAt = \Carbon\Carbon::parse($transferState['initiated_at']);
            $daysPassed = $initiatedAt->diffInDays(now());

            // Progress transfer status based on days passed
            if ($daysPassed >= 7) {
                $status = 'completed';
                $transferState['status'] = $status;
                $transferState['completed_at'] = now()->toIso8601String();
                
                // Create domain state when transfer completes
                $this->saveDomainState($domain, [
                    'domain' => $domain,
                    'status' => 'active',
                    'registered_at' => $transferState['initiated_at'],
                    'expiry_date' => now()->addYear()->toIso8601String(),
                    'locked' => true,
                    'auto_renew' => false,
                    'nameservers' => ['ns1.example.com', 'ns2.example.com'],
                ]);
                
                $this->deleteTransferState($domain);
            } elseif ($daysPassed >= 4) {
                $status = 'approved';
                $transferState['status'] = $status;
            } elseif ($daysPassed >= 2) {
                $status = 'in_progress';
                $transferState['status'] = $status;
            } else {
                $status = $transferState['status'];
            }

            $this->saveTransferState($domain, $transferState);

            return $this->successResponse(
                data: [
                    'domain' => $domain,
                    'status' => $status,
                    'transfer_id' => $transferState['transfer_id'] ?? null,
                    'initiated_at' => $transferState['initiated_at'] ?? null,
                    'completed_at' => $transferState['completed_at'] ?? null,
                    'estimated_completion' => $transferState['estimated_completion'] ?? null,
                    'message' => $this->getTransferStatusMessage($status),
                ],
                message: 'Transfer status retrieved'
            );
        }, ['domain' => $domain]);
    }

    /**
     * Cancel transfer.
     */
    public function cancelTransfer(string $domain): array
    {
        return $this->executeApiCall('cancelTransfer', function () use ($domain) {
            $this->validateDomain($domain);
            $this->simulateDelay();
            $this->simulateFailure();

            $transferState = $this->getTransferState($domain);
            if (!$transferState) {
                throw RegistrarException::domainNotFound($this->name, "Transfer not found for: {$domain}");
            }

            // Check if transfer can be cancelled
            $status = $transferState['status'] ?? 'pending';
            if (in_array($status, ['completed', 'cancelled', 'failed'])) {
                throw RegistrarException::operationFailed(
                    $this->name,
                    "Transfer cannot be cancelled in status: {$status}",
                    ['domain' => $domain, 'status' => $status]
                );
            }

            // Delete transfer state
            $this->deleteTransferState($domain);
            $this->logOperation('cancelTransfer', $domain, ['cancelled_at' => now()->toIso8601String()]);

            return $this->successResponse(
                data: [
                    'domain' => $domain,
                    'status' => 'cancelled',
                    'cancelled_at' => now()->toIso8601String(),
                ],
                message: 'Transfer cancelled successfully'
            );
        }, ['domain' => $domain]);
    }

    /**
     * Get auth code for transfer out.
     */
    public function getAuthCode(string $domain): array
    {
        return $this->executeApiCall('getAuthCode', function () use ($domain) {
            $this->validateDomain($domain);
            $this->simulateDelay();
            $this->simulateFailure();

            // Check if domain exists
            $state = $this->getDomainState($domain);
            if (!$state) {
                throw RegistrarException::domainNotFound($this->name, $domain);
            }

            // Generate mock auth code
            $authCode = 'MOCK-' . strtoupper(bin2hex(random_bytes(8)));

            // Store auth code in domain state
            $state['auth_code'] = $authCode;
            $state['auth_code_generated_at'] = now()->toIso8601String();
            $this->saveDomainState($domain, $state);

            $this->logOperation('getAuthCode', $domain, ['generated_at' => now()->toIso8601String()]);

            return $this->successResponse(
                data: [
                    'domain' => $domain,
                    'auth_code' => $authCode,
                    'generated_at' => now()->toIso8601String(),
                    'expires_at' => now()->addDays(30)->toIso8601String(),
                ],
                message: 'Authorization code generated'
            );
        }, ['domain' => $domain]);
    }

    /**
     * Get transfer state from cache.
     */
    protected function getTransferState(string $domain): ?array
    {
        $key = $this->statePrefix . 'transfer:' . $domain;
        return Cache::get($key);
    }

    /**
     * Save transfer state to cache.
     */
    protected function saveTransferState(string $domain, array $state): void
    {
        $key = $this->statePrefix . 'transfer:' . $domain;
        Cache::put($key, $state, $this->stateTtl);
    }

    /**
     * Delete transfer state from cache.
     */
    protected function deleteTransferState(string $domain): void
    {
        $key = $this->statePrefix . 'transfer:' . $domain;
        Cache::forget($key);
    }

    /**
     * Get transfer status message.
     */
    protected function getTransferStatusMessage(string $status): string
    {
        return match ($status) {
            'pending' => 'Transfer request submitted, waiting for approval',
            'in_progress' => 'Transfer is being processed',
            'approved' => 'Transfer approved, completing process',
            'completed' => 'Transfer completed successfully',
            'failed' => 'Transfer failed',
            'cancelled' => 'Transfer was cancelled',
            default => 'Transfer status unknown',
        };
    }
}
