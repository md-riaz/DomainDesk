<?php

namespace App\Services\Registrar;

use App\Exceptions\RegistrarException;

/**
 * Mock Registrar for testing purposes.
 * Returns fake responses without making actual API calls.
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
     * Initialize mock registrar.
     */
    protected function initialize(): void
    {
        $this->simulateDelays = $this->config['simulate_delays'] ?? false;
        $this->defaultDelayMs = $this->config['default_delay_ms'] ?? 100;
        $this->failureRate = $this->config['failure_rate'] ?? 0;
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

            // Mock logic: domains ending in "taken.com" are not available
            return !str_ends_with($domain, 'taken.com');
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
            $this->simulateDelay();
            $this->simulateFailure();

            return $this->successResponse(
                data: [
                    'domain' => $data['domain'],
                    'order_id' => 'MOCK-' . time(),
                    'status' => 'active',
                    'expiry_date' => now()->addYears($data['years'])->toIso8601String(),
                    'auto_renew' => $data['auto_renew'] ?? false,
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
            $this->simulateDelay();
            $this->simulateFailure();

            return $this->successResponse(
                data: [
                    'domain' => $domain,
                    'order_id' => 'MOCK-RENEW-' . time(),
                    'years_renewed' => $years,
                    'new_expiry_date' => now()->addYears($years)->toIso8601String(),
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

            return $this->successResponse(
                data: [
                    'domain' => $domain,
                    'transfer_id' => 'MOCK-TRANSFER-' . time(),
                    'status' => 'pending',
                    'estimated_completion' => now()->addDays(5)->toIso8601String(),
                ],
                message: 'Domain transfer initiated'
            );
        }, ['domain' => $domain, 'auth_code' => $authCode]);
    }

    /**
     * Update nameservers.
     */
    public function updateNameservers(string $domain, array $nameservers): array
    {
        return $this->executeApiCall('updateNameservers', function () use ($domain, $nameservers) {
            $this->validateDomain($domain);
            $this->simulateDelay();
            $this->simulateFailure();

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

            return $this->successResponse(
                data: [
                    'registrant' => $this->getMockContact('registrant'),
                    'admin' => $this->getMockContact('admin'),
                    'tech' => $this->getMockContact('tech'),
                    'billing' => $this->getMockContact('billing'),
                ],
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
            $this->simulateDelay();
            $this->simulateFailure();

            return $this->successResponse(
                data: [
                    'domain' => $domain,
                    'contacts' => $contacts,
                    'updated_at' => now()->toIso8601String(),
                ],
                message: 'Contacts updated successfully'
            );
        }, ['domain' => $domain, 'contacts' => $contacts]);
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

            return $this->successResponse(
                data: [
                    'records' => [
                        ['type' => 'A', 'name' => '@', 'value' => '192.0.2.1', 'ttl' => 3600],
                        ['type' => 'MX', 'name' => '@', 'value' => 'mail.example.com', 'priority' => 10, 'ttl' => 3600],
                    ],
                ],
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
            $this->simulateDelay();
            $this->simulateFailure();

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
        if ($this->failureRate > 0 && rand(1, 100) <= $this->failureRate) {
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
}
