<?php

namespace App\Services\Registrar;

use App\Exceptions\RegistrarException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * BTCL Domain Registrar Integration
 * 
 * Implements the RegistrarInterface for BTCL HTTP API following Domain Reseller API-V7.
 * 
 * Features:
 * - Domain registration and management
 * - Contact management (WHOIS)
 * - Nameserver management
 * - DNS record management
 * - Domain transfer operations
 * - Domain lock/unlock
 */
class BTCLRegistrar extends AbstractRegistrar
{
    /**
     * API auth user ID.
     */
    protected string $authUserId;

    /**
     * API key.
     */
    protected string $apiKey;

    /**
     * Test mode flag.
     */
    protected bool $testMode = false;

    /**
     * Default nameservers.
     */
    protected array $defaultNameservers = [];

    /**
     * BTCL order statuses.
     */
    protected array $orderStatuses = [
        'Active' => 'active',
        'Suspended' => 'suspended',
        'Pending' => 'pending',
        'Cancelled' => 'cancelled',
        'Expired' => 'expired',
        'Deleted' => 'deleted',
    ];

    /**
     * Initialize BTCL registrar.
     */
    protected function initialize(): void
    {
        $this->authUserId = $this->credentials['auth_userid'] ?? '';
        $this->apiKey = $this->credentials['api_key'] ?? '';
        $this->testMode = $this->config['test_mode'] ?? false;
        $this->defaultNameservers = $this->config['default_nameservers'] ?? [
            'ns1.btcl.com.bd',
            'ns2.btcl.com.bd',
        ];

        // Use test API URL if in test mode
        if ($this->testMode && !isset($this->config['api_url'])) {
            $this->apiUrl = 'https://test.btcldomains.com/api';
        }

        if (empty($this->authUserId) || empty($this->apiKey)) {
            throw new RegistrarException(
                message: 'BTCL credentials not configured',
                registrarName: $this->name,
                errorDetails: ['missing_credentials' => true]
            );
        }
    }

    /**
     * Check domain availability.
     */
    public function checkAvailability(string $domain): bool
    {
        return $this->executeApiCall('checkAvailability', function () use ($domain) {
            $this->validateDomain($domain);

            // Use caching for availability checks
            return $this->cacheOrExecute(
                "availability_{$domain}",
                30,
                function () use ($domain) {
                    $response = $this->makeRequest('/domains/available.json', 'GET', [
                        'domain-name' => $this->extractDomainName($domain),
                        'tlds' => [$this->extractTld($domain)],
                    ]);

                    // BTCL returns an object with TLD as key (following API-V7 standard)
                    $tld = $this->extractTld($domain);
                    
                    if (!isset($response[$tld])) {
                        throw RegistrarException::invalidData(
                            $this->name,
                            'Unexpected API response format',
                            ['response' => $response]
                        );
                    }

                    return $response[$tld]['status'] === 'available';
                }
            );
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

            // Create or get customer contact IDs
            $contactIds = $this->prepareContactIds($data['contacts']);

            // Prepare nameservers
            $nameservers = $data['nameservers'] ?? $this->defaultNameservers;
            $this->validateNameservers($nameservers);

            // Register domain
            $response = $this->makeRequest('/domains/register.json', 'POST', [
                'domain-name' => $this->extractDomainName($data['domain']),
                'years' => $data['years'],
                'ns' => $nameservers,
                'customer-id' => $this->authUserId,
                'reg-contact-id' => $contactIds['registrant'],
                'admin-contact-id' => $contactIds['admin'] ?? $contactIds['registrant'],
                'tech-contact-id' => $contactIds['tech'] ?? $contactIds['registrant'],
                'billing-contact-id' => $contactIds['billing'] ?? $contactIds['registrant'],
                'invoice-option' => 'NoInvoice',
                'protect-privacy' => $data['whois_privacy'] ?? false,
            ]);

            return $this->standardizeResponse([
                'success' => true,
                'domain' => $data['domain'],
                'order_id' => $response['entityid'] ?? null,
                'expiry_date' => $response['endtime'] ?? null,
                'status' => 'active',
                'message' => 'Domain registered successfully',
            ]);
        }, ['domain' => $data['domain'] ?? null]);
    }

    /**
     * Renew a domain.
     */
    public function renew(string $domain, int $years): array
    {
        return $this->executeApiCall('renew', function () use ($domain, $years) {
            $this->validateDomain($domain);
            $this->validateYears($years);

            $orderId = $this->getOrderId($domain);

            $response = $this->makeRequest('/domains/renew.json', 'POST', [
                'order-id' => $orderId,
                'years' => $years,
                'exp-date' => time(), // Current timestamp
                'invoice-option' => 'NoInvoice',
            ]);

            return $this->standardizeResponse([
                'success' => true,
                'domain' => $domain,
                'order_id' => $orderId,
                'years' => $years,
                'expiry_date' => $response['endtime'] ?? null,
                'message' => 'Domain renewed successfully',
            ]);
        }, ['domain' => $domain, 'years' => $years]);
    }

    /**
     * Transfer a domain.
     */
    public function transfer(string $domain, string $authCode): array
    {
        return $this->executeApiCall('transfer', function () use ($domain, $authCode) {
            $this->validateDomain($domain);

            $response = $this->makeRequest('/domains/transfer.json', 'POST', [
                'domain-name' => $this->extractDomainName($domain),
                'auth-code' => $authCode,
                'ns' => $this->defaultNameservers,
                'customer-id' => $this->authUserId,
                'invoice-option' => 'NoInvoice',
            ]);

            return $this->standardizeResponse([
                'success' => true,
                'domain' => $domain,
                'order_id' => $response['entityid'] ?? null,
                'status' => 'pending',
                'message' => 'Transfer initiated successfully',
            ]);
        }, ['domain' => $domain]);
    }

    /**
     * Get transfer status.
     */
    public function getTransferStatus(string $domain): array
    {
        return $this->executeApiCall('getTransferStatus', function () use ($domain) {
            $this->validateDomain($domain);

            $orderId = $this->getOrderId($domain);

            $response = $this->makeRequest("/domains/orderid.json", 'GET', [
                'order-id' => $orderId,
                'options' => ['All'],
            ]);

            $status = $response['currentstatus'] ?? 'unknown';

            return $this->standardizeResponse([
                'success' => true,
                'domain' => $domain,
                'status' => $this->mapOrderStatus($status),
                'message' => "Transfer status: {$status}",
            ]);
        }, ['domain' => $domain]);
    }

    /**
     * Cancel a transfer.
     */
    public function cancelTransfer(string $domain): array
    {
        return $this->executeApiCall('cancelTransfer', function () use ($domain) {
            $this->validateDomain($domain);

            $orderId = $this->getOrderId($domain);

            $response = $this->makeRequest('/domains/cancel-transfer.json', 'POST', [
                'order-id' => $orderId,
            ]);

            return $this->standardizeResponse([
                'success' => true,
                'domain' => $domain,
                'message' => 'Transfer cancelled successfully',
            ]);
        }, ['domain' => $domain]);
    }

    /**
     * Get auth code for transfer out.
     */
    public function getAuthCode(string $domain): array
    {
        return $this->executeApiCall('getAuthCode', function () use ($domain) {
            $this->validateDomain($domain);

            $orderId = $this->getOrderId($domain);

            $response = $this->makeRequest('/domains/details.json', 'GET', [
                'order-id' => $orderId,
                'options' => ['OrderDetails'],
            ]);

            $authCode = $response['domsecret'] ?? null;

            return $this->standardizeResponse([
                'success' => true,
                'domain' => $domain,
                'auth_code' => $authCode,
                'message' => 'Auth code retrieved successfully',
            ]);
        }, ['domain' => $domain]);
    }

    /**
     * Update nameservers.
     */
    public function updateNameservers(string $domain, array $nameservers): array
    {
        return $this->executeApiCall('updateNameservers', function () use ($domain, $nameservers) {
            $this->validateDomain($domain);
            $this->validateNameservers($nameservers);

            $orderId = $this->getOrderId($domain);

            $response = $this->makeRequest('/domains/modify-ns.json', 'POST', [
                'order-id' => $orderId,
                'ns' => $nameservers,
            ]);

            return $this->standardizeResponse([
                'success' => true,
                'domain' => $domain,
                'nameservers' => $nameservers,
                'message' => 'Nameservers updated successfully',
            ]);
        }, ['domain' => $domain, 'nameservers' => $nameservers]);
    }

    /**
     * Get domain contacts.
     */
    public function getContacts(string $domain): array
    {
        return $this->executeApiCall('getContacts', function () use ($domain) {
            $this->validateDomain($domain);

            $orderId = $this->getOrderId($domain);

            $response = $this->makeRequest('/domains/details.json', 'GET', [
                'order-id' => $orderId,
                'options' => ['ContactIds'],
            ]);

            return $this->standardizeResponse([
                'success' => true,
                'domain' => $domain,
                'contacts' => $this->parseContacts($response),
            ]);
        }, ['domain' => $domain]);
    }

    /**
     * Update domain contacts.
     */
    public function updateContacts(string $domain, array $contacts): array
    {
        return $this->executeApiCall('updateContacts', function () use ($domain, $contacts) {
            $this->validateDomain($domain);

            $orderId = $this->getOrderId($domain);
            $contactIds = $this->prepareContactIds($contacts);

            $response = $this->makeRequest('/domains/modify-contact.json', 'POST', [
                'order-id' => $orderId,
                'reg-contact-id' => $contactIds['registrant'] ?? null,
                'admin-contact-id' => $contactIds['admin'] ?? null,
                'tech-contact-id' => $contactIds['tech'] ?? null,
                'billing-contact-id' => $contactIds['billing'] ?? null,
            ]);

            return $this->standardizeResponse([
                'success' => true,
                'domain' => $domain,
                'message' => 'Contacts updated successfully',
            ]);
        }, ['domain' => $domain]);
    }

    /**
     * Get DNS records.
     */
    public function getDnsRecords(string $domain): array
    {
        return $this->executeApiCall('getDnsRecords', function () use ($domain) {
            $this->validateDomain($domain);

            $orderId = $this->getOrderId($domain);

            $response = $this->makeRequest('/dns/manage/search-records.json', 'GET', [
                'domain-name' => $domain,
                'type' => 'all',
            ]);

            return $this->standardizeResponse([
                'success' => true,
                'domain' => $domain,
                'records' => $response ?? [],
            ]);
        }, ['domain' => $domain]);
    }

    /**
     * Update DNS records.
     */
    public function updateDnsRecords(string $domain, array $records): array
    {
        return $this->executeApiCall('updateDnsRecords', function () use ($domain, $records) {
            $this->validateDomain($domain);

            // BTCL API requires individual API calls for each record type
            foreach ($records as $record) {
                $this->makeRequest('/dns/manage/add-record.json', 'POST', [
                    'domain-name' => $domain,
                    'type' => $record['type'],
                    'host' => $record['host'] ?? '@',
                    'value' => $record['value'],
                    'ttl' => $record['ttl'] ?? 3600,
                ]);
            }

            return $this->standardizeResponse([
                'success' => true,
                'domain' => $domain,
                'message' => 'DNS records updated successfully',
            ]);
        }, ['domain' => $domain, 'records' => $records]);
    }

    /**
     * Get domain information.
     */
    public function getInfo(string $domain): array
    {
        return $this->executeApiCall('getInfo', function () use ($domain) {
            $this->validateDomain($domain);

            $orderId = $this->getOrderId($domain);

            $response = $this->makeRequest('/domains/details.json', 'GET', [
                'order-id' => $orderId,
                'options' => ['All'],
            ]);

            return $this->standardizeResponse([
                'success' => true,
                'domain' => $domain,
                'status' => $this->mapOrderStatus($response['currentstatus'] ?? 'unknown'),
                'expiry_date' => $response['endtime'] ?? null,
                'is_locked' => $response['orderstatus']['transferlock'] ?? false,
                'auto_renew' => $response['autorenew'] ?? false,
                'nameservers' => $response['ns'] ?? [],
            ]);
        }, ['domain' => $domain]);
    }

    /**
     * Lock a domain.
     */
    public function lock(string $domain): bool
    {
        return $this->executeApiCall('lock', function () use ($domain) {
            $this->validateDomain($domain);

            $orderId = $this->getOrderId($domain);

            $this->makeRequest('/domains/enable-theft-protection.json', 'POST', [
                'order-id' => $orderId,
            ]);

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

            $orderId = $this->getOrderId($domain);

            $this->makeRequest('/domains/disable-theft-protection.json', 'POST', [
                'order-id' => $orderId,
            ]);

            return true;
        }, ['domain' => $domain]);
    }

    /**
     * Test API connection.
     */
    public function testConnection(): bool
    {
        try {
            // Test with balance check endpoint
            $response = $this->makeRequest('/billing/customer-balance.json', 'GET', [
                'customer-id' => $this->authUserId,
            ]);

            return isset($response['sellingcurrencybalance']) || isset($response['availablebalance']);
        } catch (\Exception $e) {
            $this->logError('Connection test failed', [
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Make HTTP request to BTCL API.
     */
    protected function makeRequest(string $endpoint, string $method = 'GET', array $params = []): array
    {
        $url = rtrim($this->apiUrl, '/') . $endpoint;

        // Add authentication parameters
        $params['auth-userid'] = $this->authUserId;
        $params['api-key'] = $this->apiKey;

        $this->logApiCall($method, $endpoint, $params);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->$method($url, $params);

            if (!$response->successful()) {
                throw RegistrarException::apiError(
                    $this->name,
                    "HTTP {$response->status()} error",
                    [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );
            }

            $data = $response->json();

            // Check for API error response
            if (isset($data['status']) && $data['status'] === 'ERROR') {
                throw RegistrarException::apiError(
                    $this->name,
                    $data['message'] ?? 'API error',
                    ['error_data' => $data]
                );
            }

            return $data;

        } catch (ConnectionException $e) {
            throw RegistrarException::connectionFailed($this->name, $e->getMessage());
        } catch (RequestException $e) {
            throw RegistrarException::apiError($this->name, $e->getMessage());
        }
    }

    /**
     * Get order ID for a domain.
     */
    protected function getOrderId(string $domain): string
    {
        // Try to get from cache first
        $cacheKey = "btcl_order_{$domain}";
        
        return $this->cacheOrExecute($cacheKey, 3600, function () use ($domain) {
            $response = $this->makeRequest('/domains/search.json', 'GET', [
                'domain-name' => $domain,
                'no-of-records' => 1,
            ]);

            if (empty($response)) {
                throw RegistrarException::domainNotFound($this->name, $domain);
            }

            $orderId = $response[0]['orderid'] ?? null;

            if (!$orderId) {
                throw RegistrarException::domainNotFound($this->name, $domain);
            }

            return (string) $orderId;
        });
    }

    /**
     * Prepare contact IDs from contact data.
     */
    protected function prepareContactIds(array $contacts): array
    {
        $contactIds = [];

        foreach (['registrant', 'admin', 'tech', 'billing'] as $type) {
            if (isset($contacts[$type])) {
                $contactIds[$type] = $this->createOrUpdateContact($contacts[$type]);
            }
        }

        return $contactIds;
    }

    /**
     * Create or update a contact and return contact ID.
     */
    protected function createOrUpdateContact(array $contactData): string
    {
        // Create new contact
        $response = $this->makeRequest('/contacts/add.json', 'POST', array_merge($contactData, [
            'customer-id' => $this->authUserId,
        ]));

        return (string) $response;
    }

    /**
     * Parse contacts from API response.
     */
    protected function parseContacts(array $response): array
    {
        return [
            'registrant' => $response['admincontact'] ?? [],
            'admin' => $response['admincontact'] ?? [],
            'tech' => $response['techcontact'] ?? [],
            'billing' => $response['billingcontact'] ?? [],
        ];
    }

    /**
     * Map BTCL order status to standard status.
     */
    protected function mapOrderStatus(string $status): string
    {
        return $this->orderStatuses[$status] ?? 'unknown';
    }
}
