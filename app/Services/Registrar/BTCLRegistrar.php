<?php

namespace App\Services\Registrar;

use App\Exceptions\RegistrarException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * BTCL Domain Registrar Integration
 * 
 * Implements the RegistrarInterface for BTCL Domain API.
 * 
 * API Documentation: BTCL Domain Reseller API
 * Base URL: https://141.lyre.us/rsdom/
 * 
 * Features:
 * - Domain availability check
 * - Domain reservation (20 min window)
 * - Domain registration (.bd domains)
 * - Domain renewal
 * - Nameserver management
 * - Domain information retrieval
 * - Account balance check
 * - Transaction history
 */
class BTCLRegistrar extends AbstractRegistrar
{
    /**
     * API username for Basic Auth.
     */
    protected string $username;

    /**
     * API password for Basic Auth.
     */
    protected string $password;

    /**
     * Default nameservers.
     */
    protected array $defaultNameservers = [];

    /**
     * BTCL response codes.
     */
    protected array $responseCodes = [
        2000 => 'Operation successful',
        2001 => 'Domain already registered',
        2004 => 'Domain already reserved by you',
        2005 => 'Domain reserved by others',
        4000 => 'Domain contains forbidden keyword',
        4001 => 'Domain contains reserved word',
        4002 => 'Invalid domain format',
        4003 => 'Domain length invalid',
        4004 => 'Domain not found',
        4005 => 'Domain already expired – restore required',
        4006 => 'Invalid registration/renewal years',
        4007 => 'Domain contains uncensored word',
        4008 => 'Invalid NS record',
        4009 => 'Unauthorized domain access',
        4010 => 'Domain is parked, cannot update NS record',
        4011 => 'Domain not active',
        4012 => 'Invalid JSON',
        4013 => 'Domain not allowed to renew',
        4014 => 'Rate not found',
        4015 => 'Invalid API key/secret (authentication fail)',
        4016 => 'Account inactive',
        4020 => 'Insufficient credit',
        4030 => 'Reseller not allowed for this domain/category',
        5000 => 'Internal server error',
    ];

    /**
     * Initialize BTCL registrar.
     */
    protected function initialize(): void
    {
        $this->username = $this->credentials['username'] ?? '';
        $this->password = $this->credentials['password'] ?? '';
        $this->defaultNameservers = $this->config['default_nameservers'] ?? [
            'ns1.btcl.com.bd',
            'ns2.btcl.com.bd',
        ];

        if (empty($this->username) || empty($this->password)) {
            throw new RegistrarException(
                message: 'BTCL credentials not configured. Please provide username and password.',
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
                    $response = $this->makeRequest('domain_availability.do', [
                        'domain' => $domain,
                    ]);

                    // BTCL returns responseCode 2000 for available, 2001 for registered
                    if (isset($response['responseCode']) || isset($response['response_code'])) {
                        $code = $response['responseCode'] ?? $response['response_code'];
                        return $code == 2000;
                    }

                    // Fallback to status check
                    return ($response['status'] ?? '') === 'success';
                }
            );
        }, ['domain' => $domain]);
    }

    /**
     * Reserve a domain (BTCL specific - required before registration).
     * Domain will be reserved for 20 minutes.
     */
    protected function reserveDomain(string $domain): array
    {
        $response = $this->makeRequest('reserve.do', [
            'domain' => $domain,
        ]);

        if (($response['status'] ?? '') !== 'success') {
            throw RegistrarException::apiError(
                $this->name,
                $response['message'] ?? 'Failed to reserve domain',
                ['response' => $response]
            );
        }

        return $response;
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

            // First, reserve the domain (BTCL requires this)
            try {
                $this->reserveDomain($data['domain']);
            } catch (\Exception $e) {
                // If already reserved by us, continue
                if (!str_contains($e->getMessage(), 'already reserved by you')) {
                    throw $e;
                }
            }

            // Extract contact information
            $registrant = $data['contacts']['registrant'] ?? $data['contacts']['admin'] ?? [];
            
            // Prepare nameservers
            $nameservers = $data['nameservers'] ?? $this->defaultNameservers;
            $this->validateNameservers($nameservers);

            // Prepare registration data
            $requestData = [
                'domain' => $data['domain'],
                'year' => $data['years'],
                'nameServers' => array_values($nameservers), // Ensure indexed array
                'fullName' => $registrant['name'] ?? $registrant['organization'] ?? '',
                'nid' => $registrant['nid'] ?? $registrant['passport'] ?? '',
                'email' => $registrant['email'] ?? '',
                'contactAddress' => $this->formatAddress($registrant),
                'contactNumber' => $this->formatPhone($registrant['phone'] ?? ''),
            ];

            // Register domain
            $response = $this->makeRequest('domain_buy.do', $requestData);

            return $this->standardizeResponse([
                'success' => true,
                'domain' => $data['domain'],
                'transaction_id' => $response['transactionId'] ?? null,
                'balance' => $response['currentBalance'] ?? null,
                'amount' => $response['billAmt'] ?? null,
                'status' => 'active',
                'message' => $response['message'] ?? 'Domain registered successfully',
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

            $response = $this->makeRequest('domain_renew.do', [
                'domain' => $domain,
                'year' => $years,
            ]);

            return $this->standardizeResponse([
                'success' => true,
                'domain' => $domain,
                'years' => $years,
                'transaction_id' => $response['transactionId'] ?? null,
                'balance' => $response['currentBalance'] ?? null,
                'amount' => $response['billAmt'] ?? null,
                'message' => $response['message'] ?? 'Domain renewed successfully',
            ]);
        }, ['domain' => $domain, 'years' => $years]);
    }

    /**
     * Transfer a domain - NOT SUPPORTED by BTCL.
     */
    public function transfer(string $domain, string $authCode): array
    {
        throw new RegistrarException(
            message: 'Domain transfers are not supported by BTCL registrar',
            registrarName: $this->name,
            errorDetails: ['feature' => 'transfer', 'supported' => false]
        );
    }

    /**
     * Get transfer status - NOT SUPPORTED by BTCL.
     */
    public function getTransferStatus(string $domain): array
    {
        throw new RegistrarException(
            message: 'Domain transfers are not supported by BTCL registrar',
            registrarName: $this->name,
            errorDetails: ['feature' => 'transfer', 'supported' => false]
        );
    }

    /**
     * Cancel a transfer - NOT SUPPORTED by BTCL.
     */
    public function cancelTransfer(string $domain): array
    {
        throw new RegistrarException(
            message: 'Domain transfers are not supported by BTCL registrar',
            registrarName: $this->name,
            errorDetails: ['feature' => 'transfer', 'supported' => false]
        );
    }

    /**
     * Get auth code - NOT SUPPORTED by BTCL.
     */
    public function getAuthCode(string $domain): array
    {
        throw new RegistrarException(
            message: 'Auth codes are not supported by BTCL registrar',
            registrarName: $this->name,
            errorDetails: ['feature' => 'auth_code', 'supported' => false]
        );
    }

    /**
     * Update nameservers.
     */
    public function updateNameservers(string $domain, array $nameservers): array
    {
        return $this->executeApiCall('updateNameservers', function () use ($domain, $nameservers) {
            $this->validateDomain($domain);
            $this->validateNameservers($nameservers);

            $response = $this->makeRequest('domain_update_ns.do', [
                'domain' => $domain,
                'nameServers' => array_values($nameservers), // Ensure indexed array
            ]);

            return $this->standardizeResponse([
                'success' => true,
                'domain' => $domain,
                'nameservers' => $nameservers,
                'message' => $response['message'] ?? 'Nameservers updated successfully',
            ]);
        }, ['domain' => $domain, 'nameservers' => $nameservers]);
    }

    /**
     * Get domain contacts - NOT FULLY SUPPORTED by BTCL.
     * Returns contact info from domain details.
     */
    public function getContacts(string $domain): array
    {
        return $this->executeApiCall('getContacts', function () use ($domain) {
            $this->validateDomain($domain);

            $response = $this->makeRequest('domain_info.do', [
                'domain' => $domain,
            ]);

            $contacts = [
                'registrant' => [
                    'name' => $response['clientFullName'] ?? '',
                    'nid' => $response['clientNid'] ?? '',
                    'email' => $response['clientEmail'] ?? '',
                    'address' => $response['clientContactAddress'] ?? '',
                    'phone' => $response['clientContactNumber'] ?? '',
                ],
            ];

            return $this->standardizeResponse([
                'success' => true,
                'domain' => $domain,
                'contacts' => $contacts,
            ]);
        }, ['domain' => $domain]);
    }

    /**
     * Update domain contacts - NOT SUPPORTED by BTCL.
     */
    public function updateContacts(string $domain, array $contacts): array
    {
        throw new RegistrarException(
            message: 'Contact updates are not supported by BTCL registrar',
            registrarName: $this->name,
            errorDetails: ['feature' => 'contact_update', 'supported' => false]
        );
    }

    /**
     * Get DNS records - NOT SUPPORTED by BTCL.
     */
    public function getDnsRecords(string $domain): array
    {
        throw new RegistrarException(
            message: 'DNS record management is not supported by BTCL registrar',
            registrarName: $this->name,
            errorDetails: ['feature' => 'dns_records', 'supported' => false]
        );
    }

    /**
     * Update DNS records - NOT SUPPORTED by BTCL.
     */
    public function updateDnsRecords(string $domain, array $records): array
    {
        throw new RegistrarException(
            message: 'DNS record management is not supported by BTCL registrar',
            registrarName: $this->name,
            errorDetails: ['feature' => 'dns_records', 'supported' => false]
        );
    }

    /**
     * Get domain information.
     */
    public function getInfo(string $domain): array
    {
        return $this->executeApiCall('getInfo', function () use ($domain) {
            $this->validateDomain($domain);

            $response = $this->makeRequest('domain_info.do', [
                'domain' => $domain,
            ]);

            $nameservers = [];
            if (!empty($response['primaryDns'])) {
                $nameservers[] = $response['primaryDns'];
            }
            if (!empty($response['secondaryDns'])) {
                $nameservers[] = $response['secondaryDns'];
            }
            if (!empty($response['tertiaryDns'])) {
                $nameservers[] = $response['tertiaryDns'];
            }

            return $this->standardizeResponse([
                'success' => true,
                'domain' => $domain,
                'status' => 'active',
                'activation_date' => $response['activationDate'] ?? null,
                'expiry_date' => $response['expiryDate'] ?? null,
                'nameservers' => $nameservers,
                'registrant' => [
                    'name' => $response['clientFullName'] ?? '',
                    'nid' => $response['clientNid'] ?? '',
                    'email' => $response['clientEmail'] ?? '',
                    'address' => $response['clientContactAddress'] ?? '',
                    'phone' => $response['clientContactNumber'] ?? '',
                ],
            ]);
        }, ['domain' => $domain]);
    }

    /**
     * Lock a domain - NOT SUPPORTED by BTCL.
     */
    public function lock(string $domain): bool
    {
        throw new RegistrarException(
            message: 'Domain locking is not supported by BTCL registrar',
            registrarName: $this->name,
            errorDetails: ['feature' => 'lock', 'supported' => false]
        );
    }

    /**
     * Unlock a domain - NOT SUPPORTED by BTCL.
     */
    public function unlock(string $domain): bool
    {
        throw new RegistrarException(
            message: 'Domain unlocking is not supported by BTCL registrar',
            registrarName: $this->name,
            errorDetails: ['feature' => 'unlock', 'supported' => false]
        );
    }

    /**
     * Test API connection.
     */
    public function testConnection(): bool
    {
        try {
            // Test with balance check endpoint
            $response = $this->makeRequest('balance.do', []);

            return isset($response['balance']) && isset($response['responseCode']) && $response['responseCode'] == 2000;
        } catch (\Exception $e) {
            $this->logError('Connection test failed', [
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Make HTTP request to BTCL API using Basic Auth.
     */
    protected function makeRequest(string $endpoint, array $params = []): array
    {
        $url = rtrim($this->apiUrl, '/') . '/' . ltrim($endpoint, '/');

        $this->logApiCall('POST', $endpoint, $params);

        try {
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $params);

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
            if (isset($data['status']) && $data['status'] === 'error') {
                $code = $data['responseCode'] ?? $data['response_code'] ?? 5000;
                $message = $data['message'] ?? $this->responseCodes[$code] ?? 'API error';
                
                throw RegistrarException::apiError(
                    $this->name,
                    $message,
                    ['error_data' => $data, 'response_code' => $code]
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
     * Get domain pricing.
     */
    public function getDomainRate(string $domain, int $years = 1): array
    {
        $response = $this->makeRequest('domain_rates.do', [
            'domain' => $domain,
            'year' => $years,
        ]);

        return [
            'domain' => $domain,
            'years' => $years,
            'registration_rate' => $response['regRate'] ?? 0,
            'renewal_rate' => $response['renewRate'] ?? 0,
        ];
    }

    /**
     * Get account balance.
     */
    public function getBalance(): array
    {
        $response = $this->makeRequest('balance.do', []);

        return [
            'balance' => $response['balance'] ?? 0,
            'currency' => 'BDT',
        ];
    }

    /**
     * Get billing transactions.
     */
    public function getTransactions(string $fromDate, string $toDate): array
    {
        $response = $this->makeRequest('billing_transactions.do', [
            'fromDate' => $fromDate, // Format: YYYY-MM-DD
            'toDate' => $toDate,     // Format: YYYY-MM-DD
        ]);

        return [
            'opening_balance' => $response['opening_balance'] ?? 0,
            'transactions' => $response['transactions'] ?? [],
        ];
    }

    /**
     * Format address from contact data.
     */
    protected function formatAddress(array $contact): string
    {
        $parts = [];
        
        if (!empty($contact['address'])) {
            $parts[] = $contact['address'];
        }
        if (!empty($contact['city'])) {
            $parts[] = $contact['city'];
        }
        if (!empty($contact['state'])) {
            $parts[] = $contact['state'];
        }
        if (!empty($contact['country'])) {
            $parts[] = $contact['country'];
        }
        if (!empty($contact['zip'])) {
            $parts[] = $contact['zip'];
        }

        return implode(', ', $parts) ?: ($contact['address'] ?? '');
    }

    /**
     * Format phone number for BTCL (requires +880 prefix, 14 length, no space).
     */
    protected function formatPhone(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If starts with 880, add +
        if (str_starts_with($phone, '880')) {
            return '+' . $phone;
        }
        
        // If starts with 0, replace with +880
        if (str_starts_with($phone, '0')) {
            return '+880' . substr($phone, 1);
        }
        
        // If 10-11 digits, assume Bangladesh number
        if (strlen($phone) >= 10 && strlen($phone) <= 11) {
            return '+880' . ltrim($phone, '0');
        }
        
        // Return as-is with + prefix if not already there
        return str_starts_with($phone, '+') ? $phone : '+' . $phone;
    }

    /**
     * Validate years (BTCL min: 2, max: 10 for registration, 1-10 for renewal).
     */
    protected function validateYears(int $years, bool $isRenewal = false): void
    {
        $min = $isRenewal ? 1 : 2;
        $max = 10;
        
        if ($years < $min || $years > $max) {
            throw new RegistrarException(
                message: "Invalid years: {$years}. Must be between {$min} and {$max}.",
                registrarName: $this->name,
                errorDetails: ['years' => $years, 'min' => $min, 'max' => $max]
            );
        }
    }
}
