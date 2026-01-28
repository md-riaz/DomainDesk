<?php

namespace App\Contracts;

interface RegistrarInterface
{
    /**
     * Check if a domain is available for registration.
     *
     * @param string $domain The domain name to check
     * @return bool True if available, false if taken
     * @throws \App\Exceptions\RegistrarException
     */
    public function checkAvailability(string $domain): bool;

    /**
     * Register a new domain.
     *
     * @param array $data Domain registration data including:
     *                    - domain: string (required)
     *                    - years: int (required)
     *                    - contacts: array (registrant, admin, tech, billing)
     *                    - nameservers: array (optional)
     *                    - auto_renew: bool (optional)
     * @return array Standardized response
     * @throws \App\Exceptions\RegistrarException
     */
    public function register(array $data): array;

    /**
     * Renew a domain for specified years.
     *
     * @param string $domain The domain name to renew
     * @param int $years Number of years to renew
     * @return array Standardized response
     * @throws \App\Exceptions\RegistrarException
     */
    public function renew(string $domain, int $years): array;

    /**
     * Transfer a domain from another registrar.
     *
     * @param string $domain The domain name to transfer
     * @param string $authCode EPP/Auth code for transfer
     * @return array Standardized response
     * @throws \App\Exceptions\RegistrarException
     */
    public function transfer(string $domain, string $authCode): array;

    /**
     * Update domain nameservers.
     *
     * @param string $domain The domain name
     * @param array $nameservers Array of nameserver hostnames
     * @return array Standardized response
     * @throws \App\Exceptions\RegistrarException
     */
    public function updateNameservers(string $domain, array $nameservers): array;

    /**
     * Get domain contact information.
     *
     * @param string $domain The domain name
     * @return array Standardized response with contact data
     * @throws \App\Exceptions\RegistrarException
     */
    public function getContacts(string $domain): array;

    /**
     * Update domain contact information.
     *
     * @param string $domain The domain name
     * @param array $contacts Contact data (registrant, admin, tech, billing)
     * @return array Standardized response
     * @throws \App\Exceptions\RegistrarException
     */
    public function updateContacts(string $domain, array $contacts): array;

    /**
     * Get DNS records for a domain (if supported by registrar).
     *
     * @param string $domain The domain name
     * @return array Standardized response with DNS records
     * @throws \App\Exceptions\RegistrarException
     */
    public function getDnsRecords(string $domain): array;

    /**
     * Update DNS records for a domain (if supported by registrar).
     *
     * @param string $domain The domain name
     * @param array $records DNS record data
     * @return array Standardized response
     * @throws \App\Exceptions\RegistrarException
     */
    public function updateDnsRecords(string $domain, array $records): array;

    /**
     * Get domain information including status, expiry, lock status.
     *
     * @param string $domain The domain name
     * @return array Standardized response with domain info
     * @throws \App\Exceptions\RegistrarException
     */
    public function getInfo(string $domain): array;

    /**
     * Lock a domain to prevent transfers.
     *
     * @param string $domain The domain name
     * @return bool True if successfully locked
     * @throws \App\Exceptions\RegistrarException
     */
    public function lock(string $domain): bool;

    /**
     * Unlock a domain to allow transfers.
     *
     * @param string $domain The domain name
     * @return bool True if successfully unlocked
     * @throws \App\Exceptions\RegistrarException
     */
    public function unlock(string $domain): bool;

    /**
     * Get the registrar name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Test API connection and credentials.
     *
     * @return bool True if connection is successful
     * @throws \App\Exceptions\RegistrarException
     */
    public function testConnection(): bool;
}
