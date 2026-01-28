<?php

namespace App\Services\Registrar;

use App\Exceptions\RegistrarException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * ResellerClub/LogicBoxes Registrar Integration
 * 
 * Implements the RegistrarInterface for ResellerClub/LogicBoxes HTTP API.
 * 
 * API Documentation: https://manage.resellerclub.com/kb/answer/744
 * 
 * Features:
 * - Domain registration and management
 * - Contact management (WHOIS)
 * - Nameserver management
 * - DNS record management
 * - Domain transfer operations
 * - Domain lock/unlock
 */
class ResellerClubRegistrar extends AbstractRegistrar
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
     * ResellerClub order statuses.
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
     * Initialize ResellerClub registrar.
     */
    protected function initialize(): void
    {
        $this->authUserId = $this->credentials['auth_userid'] ?? '';
        $this->apiKey = $this->credentials['api_key'] ?? '';
        $this->testMode = $this->config['test_mode'] ?? false;
        $this->defaultNameservers = $this->config['default_nameservers'] ?? [
            'ns1.resellerclub.com',
            'ns2.resellerclub.com',
        ];

        // Use test API URL if in test mode
        if ($this->testMode && !isset($this->config['api_url'])) {
            $this->apiUrl = 'https://test.httpapi.com/api';
        }

        if (empty($this->authUserId) || empty($this->apiKey)) {
            throw new RegistrarException(
                message: 'ResellerClub credentials not configured',
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

                    // ResellerClub returns an object with TLD as key
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

            if (isset($response['status']) && $response['status'] === 'error') {
                throw $this->createExceptionFromResponse($response);
            }

            // Get domain info to confirm registration
            $domainInfo = $this->getOrderDetails($response['entityid'] ?? $response['orderid']);

            return $this->successResponse(
                data: [
                    'domain' => $data['domain'],
                    'order_id' => $response['entityid'] ?? $response['orderid'],
                    'status' => $this->mapOrderStatus($domainInfo['currentstatus'] ?? 'Active'),
                    'expiry_date' => $this->parseDate($domainInfo['endtime'] ?? null),
                    'auto_renew' => $domainInfo['autorenew'] ?? false,
                    'nameservers' => $nameservers,
                ],
                message: 'Domain registered successfully',
                registrarResponse: $response
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

            // Get domain order ID
            $orderId = $this->getDomainOrderId($domain);

            // Renew domain
            $response = $this->makeRequest('/domains/renew.json', 'POST', [
                'order-id' => $orderId,
                'years' => $years,
                'exp-date' => time(), // Current expiry timestamp
                'invoice-option' => 'NoInvoice',
            ]);

            if (isset($response['status']) && $response['status'] === 'error') {
                throw $this->createExceptionFromResponse($response);
            }

            // Get updated domain info
            $domainInfo = $this->getOrderDetails($orderId);

            return $this->successResponse(
                data: [
                    'domain' => $domain,
                    'order_id' => $orderId,
                    'years_renewed' => $years,
                    'new_expiry_date' => $this->parseDate($domainInfo['endtime'] ?? null),
                ],
                message: 'Domain renewed successfully',
                registrarResponse: $response
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

            if (empty($authCode)) {
                throw RegistrarException::invalidData(
                    $this->name,
                    'Authorization code is required for transfer',
                    ['domain' => $domain]
                );
            }

            // For ResellerClub, we need contact IDs for transfer
            // Using default/existing customer contacts
            $contactId = $this->getDefaultContactId();

            $response = $this->makeRequest('/domains/transfer.json', 'POST', [
                'domain-name' => $this->extractDomainName($domain),
                'auth-code' => $authCode,
                'ns' => $this->defaultNameservers,
                'customer-id' => $this->authUserId,
                'reg-contact-id' => $contactId,
                'admin-contact-id' => $contactId,
                'tech-contact-id' => $contactId,
                'billing-contact-id' => $contactId,
                'invoice-option' => 'NoInvoice',
            ]);

            if (isset($response['status']) && $response['status'] === 'error') {
                throw $this->createExceptionFromResponse($response);
            }

            return $this->successResponse(
                data: [
                    'domain' => $domain,
                    'transfer_id' => $response['entityid'] ?? $response['orderid'],
                    'status' => 'pending',
                    'initiated_at' => now()->toIso8601String(),
                ],
                message: 'Domain transfer initiated',
                registrarResponse: $response
            );
        }, ['domain' => $domain, 'auth_code' => '***']);
    }

    /**
     * Update domain nameservers.
     */
    public function updateNameservers(string $domain, array $nameservers): array
    {
        return $this->executeApiCall('updateNameservers', function () use ($domain, $nameservers) {
            $this->validateDomain($domain);
            $this->validateNameservers($nameservers);

            $orderId = $this->getDomainOrderId($domain);

            $response = $this->makeRequest('/domains/modify-ns.json', 'POST', [
                'order-id' => $orderId,
                'ns' => $nameservers,
            ]);

            if (isset($response['status']) && $response['status'] === 'error') {
                throw $this->createExceptionFromResponse($response);
            }

            // Clear domain info cache
            $this->clearCache("domain_info_{$domain}");

            return $this->successResponse(
                data: [
                    'domain' => $domain,
                    'nameservers' => $nameservers,
                    'updated_at' => now()->toIso8601String(),
                ],
                message: 'Nameservers updated successfully',
                registrarResponse: $response
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

            $orderId = $this->getDomainOrderId($domain);
            $domainInfo = $this->getOrderDetails($orderId);

            // Get contact IDs from domain info
            $contactIds = [
                'registrant' => $domainInfo['registrantcontactid'] ?? null,
                'admin' => $domainInfo['admincontactid'] ?? null,
                'tech' => $domainInfo['techcontactid'] ?? null,
                'billing' => $domainInfo['billingcontactid'] ?? null,
            ];

            // Fetch contact details for each contact ID
            $contacts = [];
            foreach ($contactIds as $type => $contactId) {
                if ($contactId) {
                    $contacts[$type] = $this->getContactDetails($contactId);
                }
            }

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

            $orderId = $this->getDomainOrderId($domain);

            // Create or update contacts and get IDs
            $contactIds = $this->prepareContactIds($contacts);

            // Update domain contacts
            $updateData = ['order-id' => $orderId];
            
            if (isset($contactIds['registrant'])) {
                $updateData['reg-contact-id'] = $contactIds['registrant'];
            }
            if (isset($contactIds['admin'])) {
                $updateData['admin-contact-id'] = $contactIds['admin'];
            }
            if (isset($contactIds['tech'])) {
                $updateData['tech-contact-id'] = $contactIds['tech'];
            }
            if (isset($contactIds['billing'])) {
                $updateData['billing-contact-id'] = $contactIds['billing'];
            }

            $response = $this->makeRequest('/domains/modify-contact.json', 'POST', $updateData);

            if (isset($response['status']) && $response['status'] === 'error') {
                throw $this->createExceptionFromResponse($response);
            }

            // Clear domain info cache
            $this->clearCache("domain_info_{$domain}");

            return $this->successResponse(
                data: [
                    'domain' => $domain,
                    'updated_at' => now()->toIso8601String(),
                ],
                message: 'Contacts updated successfully',
                registrarResponse: $response
            );
        }, ['domain' => $domain, 'contacts' => array_keys($contacts)]);
    }

    /**
     * Get DNS records.
     */
    public function getDnsRecords(string $domain): array
    {
        return $this->executeApiCall('getDnsRecords', function () use ($domain) {
            $this->validateDomain($domain);

            $domainName = $this->extractDomainName($domain);

            $response = $this->makeRequest('/dns/manage/search-records.json', 'GET', [
                'domain-name' => $domainName,
                'type' => 'all',
                'no-of-records' => 500,
                'page-no' => 1,
            ]);

            if (isset($response['status']) && $response['status'] === 'error') {
                throw $this->createExceptionFromResponse($response);
            }

            // Parse and normalize DNS records
            $records = $this->parseDnsRecords($response);

            return $this->successResponse(
                data: ['records' => $records],
                message: 'DNS records retrieved successfully',
                registrarResponse: $response
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

            $domainName = $this->extractDomainName($domain);

            // ResellerClub requires deleting all records and re-adding them
            // First, get existing records
            $existingRecords = $this->getDnsRecords($domain);
            
            // Delete all existing records
            foreach ($existingRecords['data']['records'] ?? [] as $record) {
                if (isset($record['id'])) {
                    $this->makeRequest('/dns/manage/delete-record.json', 'POST', [
                        'domain-name' => $domainName,
                        'record-id' => $record['id'],
                    ]);
                }
            }

            // Add new records
            foreach ($records as $record) {
                $this->addDnsRecord($domainName, $record);
            }

            return $this->successResponse(
                data: [
                    'domain' => $domain,
                    'records' => $records,
                    'updated_at' => now()->toIso8601String(),
                ],
                message: 'DNS records updated successfully'
            );
        }, ['domain' => $domain, 'record_count' => count($records)]);
    }

    /**
     * Get domain information.
     */
    public function getInfo(string $domain): array
    {
        return $this->executeApiCall('getInfo', function () use ($domain) {
            $this->validateDomain($domain);

            return $this->cacheOrExecute(
                "domain_info_{$domain}",
                300,
                function () use ($domain) {
                    $orderId = $this->getDomainOrderId($domain);
                    $domainInfo = $this->getOrderDetails($orderId);

                    return $this->successResponse(
                        data: [
                            'domain' => $domain,
                            'order_id' => $orderId,
                            'status' => $this->mapOrderStatus($domainInfo['currentstatus'] ?? 'Unknown'),
                            'created_at' => $this->parseDate($domainInfo['creationtime'] ?? null),
                            'updated_at' => $this->parseDate($domainInfo['modificationtime'] ?? null),
                            'expiry_date' => $this->parseDate($domainInfo['endtime'] ?? null),
                            'auto_renew' => filter_var($domainInfo['autorenew'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'locked' => filter_var($domainInfo['customerlocked'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'nameservers' => $domainInfo['nameservers'] ?? [],
                            'privacy_protected' => filter_var($domainInfo['isprivacyprotected'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        ],
                        message: 'Domain information retrieved successfully',
                        registrarResponse: $domainInfo
                    );
                }
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

            $orderId = $this->getDomainOrderId($domain);

            $response = $this->makeRequest('/domains/enable-theft-protection.json', 'POST', [
                'order-id' => $orderId,
            ]);

            if (isset($response['status']) && $response['status'] === 'error') {
                throw $this->createExceptionFromResponse($response);
            }

            // Clear domain info cache
            $this->clearCache("domain_info_{$domain}");

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

            $orderId = $this->getDomainOrderId($domain);

            $response = $this->makeRequest('/domains/disable-theft-protection.json', 'POST', [
                'order-id' => $orderId,
            ]);

            if (isset($response['status']) && $response['status'] === 'error') {
                throw $this->createExceptionFromResponse($response);
            }

            // Clear domain info cache
            $this->clearCache("domain_info_{$domain}");

            return true;
        }, ['domain' => $domain]);
    }

    /**
     * Test API connection and credentials.
     */
    public function testConnection(): bool
    {
        try {
            // Test by getting customer details
            $response = $this->makeRequest('/customers/details.json', 'GET', [
                'customer-id' => $this->authUserId,
            ]);

            return isset($response['customerid']);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Make HTTP request to ResellerClub API.
     */
    protected function makeRequest(string $endpoint, string $method = 'GET', array $data = []): mixed
    {
        // Add authentication to all requests
        $data['auth-userid'] = $this->authUserId;
        $data['api-key'] = $this->apiKey;

        $url = rtrim($this->apiUrl, '/') . $endpoint;

        try {
            $response = match (strtoupper($method)) {
                'GET' => Http::timeout($this->timeout)
                    ->get($url, $data),
                'POST' => Http::timeout($this->timeout)
                    ->asForm()
                    ->post($url, $data),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            if ($response->failed()) {
                throw RegistrarException::connectionFailed(
                    $this->name,
                    "HTTP {$response->status()}: {$response->body()}",
                    new RequestException($response)
                );
            }

            $result = $response->json();

            // Check for authentication errors
            if (is_array($result) && isset($result['status']) && $result['status'] === 'error') {
                $errorMessage = $result['message'] ?? 'Unknown error';
                $errorCode = $result['error'] ?? null;

                // Check for authentication errors
                if (str_contains(strtolower($errorMessage), 'authentication') 
                    || str_contains(strtolower($errorMessage), 'invalid api key')) {
                    throw RegistrarException::authenticationFailed(
                        $this->name,
                        $errorMessage,
                        $errorCode
                    );
                }
            }

            return $result;

        } catch (ConnectionException $e) {
            throw RegistrarException::connectionFailed(
                $this->name,
                'Failed to connect to ResellerClub API: ' . $e->getMessage(),
                $e
            );
        } catch (RequestException $e) {
            throw RegistrarException::timeout(
                $this->name,
                $endpoint,
                $this->timeout
            );
        }
    }

    /**
     * Extract domain name without TLD.
     */
    protected function extractDomainName(string $domain): string
    {
        $parts = explode('.', $domain);
        array_pop($parts); // Remove TLD
        return implode('.', $parts);
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
     * Get domain order ID by domain name.
     */
    protected function getDomainOrderId(string $domain): string
    {
        return $this->cacheOrExecute(
            "order_id_{$domain}",
            300,
            function () use ($domain) {
                $response = $this->makeRequest('/domains/orderid.json', 'GET', [
                    'domain-name' => $domain,
                ]);

                if (isset($response['status']) && $response['status'] === 'error') {
                    throw RegistrarException::domainNotFound($this->name, $domain);
                }

                return (string) $response;
            }
        );
    }

    /**
     * Get order details.
     */
    protected function getOrderDetails(string $orderId): array
    {
        $response = $this->makeRequest('/domains/details.json', 'GET', [
            'order-id' => $orderId,
            'options' => 'All',
        ]);

        if (isset($response['status']) && $response['status'] === 'error') {
            throw $this->createExceptionFromResponse($response);
        }

        return $response;
    }

    /**
     * Get contact details by contact ID.
     */
    protected function getContactDetails(string $contactId): array
    {
        $response = $this->makeRequest('/contacts/details.json', 'GET', [
            'contact-id' => $contactId,
        ]);

        if (isset($response['status']) && $response['status'] === 'error') {
            return [];
        }

        // Normalize contact data
        return [
            'name' => $response['name'] ?? '',
            'company' => $response['company'] ?? '',
            'email' => $response['emailaddr'] ?? '',
            'phone' => $response['telnocc'] . '.' . ($response['telno'] ?? ''),
            'address' => $response['address1'] ?? '',
            'address2' => $response['address2'] ?? '',
            'city' => $response['city'] ?? '',
            'state' => $response['state'] ?? '',
            'zip' => $response['zip'] ?? '',
            'country' => $response['country'] ?? '',
        ];
    }

    /**
     * Prepare contact IDs from contact data.
     */
    protected function prepareContactIds(array $contacts): array
    {
        $contactIds = [];

        foreach ($contacts as $type => $contactData) {
            // For simplicity, we'll use the default customer contact
            // In production, you might want to create/update contacts
            $contactIds[$type] = $this->getDefaultContactId();
        }

        return $contactIds;
    }

    /**
     * Get default contact ID for the customer.
     */
    protected function getDefaultContactId(): string
    {
        return $this->cacheOrExecute(
            'default_contact_id',
            3600,
            function () {
                $response = $this->makeRequest('/contacts/default.json', 'GET', [
                    'customer-id' => $this->authUserId,
                    'type' => 'Contact',
                ]);

                if (isset($response['status']) && $response['status'] === 'error') {
                    throw RegistrarException::invalidData(
                        $this->name,
                        'No default contact found for customer',
                        ['customer_id' => $this->authUserId]
                    );
                }

                return (string) ($response['contactid'] ?? $response['entityid'] ?? $response);
            }
        );
    }

    /**
     * Add a DNS record.
     */
    protected function addDnsRecord(string $domainName, array $record): array
    {
        $data = [
            'domain-name' => $domainName,
            'record-type' => $record['type'],
            'host' => $record['name'],
            'value' => $record['value'],
            'ttl' => $record['ttl'] ?? 3600,
        ];

        // Add priority for MX records
        if ($record['type'] === 'MX' && isset($record['priority'])) {
            $data['priority'] = $record['priority'];
        }

        return $this->makeRequest('/dns/manage/add-record.json', 'POST', $data);
    }

    /**
     * Parse DNS records from ResellerClub response.
     */
    protected function parseDnsRecords(array $response): array
    {
        $records = [];

        foreach ($response as $key => $value) {
            if (is_numeric($key) && is_array($value)) {
                $records[] = [
                    'id' => $value['recid'] ?? null,
                    'type' => $value['type'] ?? '',
                    'name' => $value['host'] ?? '',
                    'value' => $value['value'] ?? '',
                    'ttl' => $value['ttl'] ?? 3600,
                    'priority' => $value['priority'] ?? null,
                ];
            }
        }

        return $records;
    }

    /**
     * Map ResellerClub order status to standard status.
     */
    protected function mapOrderStatus(string $status): string
    {
        return $this->orderStatuses[$status] ?? strtolower($status);
    }

    /**
     * Parse date from ResellerClub timestamp.
     */
    protected function parseDate(?int $timestamp): ?string
    {
        if (!$timestamp) {
            return null;
        }

        return \Carbon\Carbon::createFromTimestamp($timestamp)->toIso8601String();
    }

    /**
     * Create exception from ResellerClub error response.
     */
    protected function createExceptionFromResponse(array $response): RegistrarException
    {
        $message = $response['message'] ?? $response['error'] ?? 'Unknown error';
        $errorCode = $response['error'] ?? null;

        return new RegistrarException(
            message: $message,
            registrarName: $this->name,
            registrarErrorCode: $errorCode,
            registrarResponse: $response,
            errorDetails: ['type' => 'api_error']
        );
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
     * Validate nameservers.
     */
    protected function validateNameservers(array $nameservers): void
    {
        $count = count($nameservers);
        
        if ($count < 2 || $count > 13) {
            throw RegistrarException::invalidData(
                $this->name,
                'Must provide between 2 and 13 nameservers',
                ['provided' => $count]
            );
        }

        foreach ($nameservers as $ns) {
            if (!is_string($ns) || !preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/i', $ns)) {
                throw RegistrarException::invalidData(
                    $this->name,
                    'Invalid nameserver format',
                    ['nameserver' => $ns]
                );
            }
        }
    }

    /**
     * Validate DNS records.
     */
    protected function validateDnsRecords(array $records): void
    {
        $validTypes = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SRV'];

        foreach ($records as $index => $record) {
            if (!isset($record['type'], $record['name'], $record['value'])) {
                throw RegistrarException::invalidData(
                    $this->name,
                    'DNS record must have type, name, and value',
                    ['index' => $index]
                );
            }

            if (!in_array($record['type'], $validTypes)) {
                throw RegistrarException::invalidData(
                    $this->name,
                    "Invalid DNS record type: {$record['type']}",
                    ['valid_types' => $validTypes]
                );
            }

            if ($record['type'] === 'MX' && !isset($record['priority'])) {
                throw RegistrarException::invalidData(
                    $this->name,
                    'MX records must have a priority',
                    ['record' => $record]
                );
            }
        }
    }
}
