<?php

namespace App\Services;

use App\Enums\PriceAction;
use App\Exceptions\RegistrarException;
use App\Models\Partner;
use App\Models\Tld;
use App\Services\Registrar\RegistrarFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DomainSearchService
{
    /**
     * Alternative TLDs to suggest when domain is taken
     */
    const SUGGESTION_TLDS = [
        'com', 'net', 'org', 'io', 'co', 'app', 'dev', 'tech', 'online', 'store'
    ];

    public function __construct(
        protected PricingService $pricingService
    ) {}

    /**
     * Get cache TTL from configuration or use default (30 seconds)
     */
    protected function getCacheTtl(): int
    {
        return config('domain.search.cache_ttl', 30);
    }

    /**
     * Get max bulk search limit from configuration or use default (20)
     */
    protected function getMaxBulkSearch(): int
    {
        return config('domain.search.max_bulk_search', 20);
    }

    /**
     * Parse domain input and extract domain name and TLD
     *
     * @param string $input Domain input (e.g., "example.com" or "example")
     * @return array{domain: string, sld: string, tld: string}|null
     */
    public function parseDomain(string $input): ?array
    {
        $input = strtolower(trim($input));
        
        // Remove http:// or https://
        $input = preg_replace('#^https?://#i', '', $input);
        
        // Remove www.
        $input = preg_replace('#^www\.#i', '', $input);
        
        // Remove trailing slash
        $input = rtrim($input, '/');
        
        // Validate basic format (alphanumeric, hyphens, dots)
        // Domain labels cannot start or end with hyphens
        if (!preg_match('/^[a-z0-9]+([a-z0-9-]*[a-z0-9]+)*(\.[a-z0-9]+([a-z0-9-]*[a-z0-9]+)*)+$/i', $input)) {
            return null;
        }
        
        // Must contain at least one dot for TLD
        if (!str_contains($input, '.')) {
            return null;
        }
        
        // Extract TLD (everything after last dot)
        $parts = explode('.', $input);
        $tld = array_pop($parts);
        $sld = implode('.', $parts);
        
        // SLD cannot be empty
        if (empty($sld)) {
            return null;
        }
        
        return [
            'domain' => $input,
            'sld' => $sld,
            'tld' => $tld,
        ];
    }

    /**
     * Validate domain format
     *
     * @param string $domain Domain name
     * @return bool
     */
    public function validateDomainFormat(string $domain): bool
    {
        return $this->parseDomain($domain) !== null;
    }

    /**
     * Check if TLD is supported
     *
     * @param string $tld TLD extension (without dot)
     * @return bool
     */
    public function isTldSupported(string $tld): bool
    {
        return Tld::where('extension', strtolower($tld))
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check domain availability
     *
     * @param string $domain Domain name to check
     * @param bool $useCache Whether to use cache (default: true)
     * @return array Result with availability, error info
     */
    public function checkAvailability(string $domain, bool $useCache = true): array
    {
        $parsed = $this->parseDomain($domain);
        
        if (!$parsed) {
            return [
                'domain' => $domain,
                'available' => false,
                'error' => 'Invalid domain format',
                'error_type' => 'validation',
            ];
        }

        // Check if TLD is supported
        $tld = Tld::where('extension', $parsed['tld'])
            ->where('is_active', true)
            ->with('registrar')
            ->first();

        if (!$tld) {
            return [
                'domain' => $parsed['domain'],
                'available' => false,
                'error' => 'TLD not supported',
                'error_type' => 'unsupported_tld',
                'tld' => $parsed['tld'],
            ];
        }

        // Check cache if enabled
        $cacheKey = 'domain:availability:' . $parsed['domain'];
        
        if ($useCache && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            $cached['cached'] = true;
            return $cached;
        }

        try {
            // Get registrar instance
            $registrar = RegistrarFactory::make($tld->registrar_id);
            
            // Check availability
            $available = $registrar->checkAvailability($parsed['domain']);
            
            $result = [
                'domain' => $parsed['domain'],
                'available' => $available,
                'tld_id' => $tld->id,
                'registrar' => $tld->registrar->name,
                'cached' => false,
            ];

            // Cache result
            Cache::put($cacheKey, $result, $this->getCacheTtl());

            return $result;

        } catch (RegistrarException $e) {
            Log::warning('Domain availability check failed', [
                'domain' => $parsed['domain'],
                'registrar' => $tld->registrar->name ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return [
                'domain' => $parsed['domain'],
                'available' => false,
                'error' => 'Failed to check availability: ' . $e->getMessage(),
                'error_type' => 'registrar_error',
                'registrar' => $tld->registrar->name ?? 'unknown',
            ];
        }
    }

    /**
     * Search domains and calculate pricing
     *
     * @param string|array $domains Domain(s) to search
     * @param Partner|int|null $partner Partner for pricing
     * @param int $years Number of years (default: 1)
     * @return array Search results with pricing
     */
    public function search(string|array $domains, Partner|int|null $partner = null, int $years = 1): array
    {
        // Convert to array if single domain
        $domains = is_array($domains) ? $domains : [$domains];
        
        // Limit bulk search
        $maxBulk = $this->getMaxBulkSearch();
        if (count($domains) > $maxBulk) {
            return [
                'success' => false,
                'error' => 'Too many domains. Maximum ' . $maxBulk . ' domains per search.',
                'results' => [],
            ];
        }

        $results = [];

        foreach ($domains as $domain) {
            $domain = trim($domain);
            
            if (empty($domain)) {
                continue;
            }

            // Check availability
            $availabilityResult = $this->checkAvailability($domain);
            
            // If available, calculate pricing
            if ($availabilityResult['available'] ?? false) {
                $price = $this->calculatePrice(
                    $availabilityResult['tld_id'],
                    $partner,
                    $years
                );

                $availabilityResult['price'] = $price;
                $availabilityResult['years'] = $years;
            }

            $results[] = $availabilityResult;
        }

        return [
            'success' => true,
            'results' => $results,
            'total' => count($results),
        ];
    }

    /**
     * Calculate pricing for a domain
     *
     * @param int $tldId TLD ID
     * @param Partner|int|null $partner Partner for pricing
     * @param int $years Number of years
     * @return array|null Pricing information
     */
    protected function calculatePrice(int $tldId, Partner|int|null $partner = null, int $years = 1): ?array
    {
        try {
            $tld = Tld::find($tldId);
            
            if (!$tld) {
                return null;
            }

            $price = $this->pricingService->calculateFinalPrice(
                $tld,
                $partner,
                PriceAction::REGISTER,
                $years
            );

            if ($price === null) {
                return null;
            }

            return [
                'register' => $price,
                'currency' => 'BDT',
                'years' => $years,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate domain price', [
                'tld_id' => $tldId,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Generate domain suggestions when original is taken
     *
     * @param string $domain Original domain
     * @param Partner|int|null $partner Partner for pricing
     * @param int $maxSuggestions Maximum suggestions (default: 10)
     * @return array Suggestions
     */
    public function getSuggestions(string $domain, Partner|int|null $partner = null, int $maxSuggestions = 10): array
    {
        $parsed = $this->parseDomain($domain);
        
        if (!$parsed) {
            return [];
        }

        $suggestions = [];
        $checked = [];

        // Try different TLDs
        foreach (self::SUGGESTION_TLDS as $tld) {
            if (count($suggestions) >= $maxSuggestions) {
                break;
            }

            $suggestedDomain = $parsed['sld'] . '.' . $tld;
            
            // Skip if already checked or same as original
            if (in_array($suggestedDomain, $checked) || $suggestedDomain === $parsed['domain']) {
                continue;
            }

            $checked[] = $suggestedDomain;

            $result = $this->checkAvailability($suggestedDomain);
            
            if ($result['available'] ?? false) {
                // Calculate pricing
                if (isset($result['tld_id'])) {
                    $price = $this->calculatePrice($result['tld_id'], $partner, 1);
                    $result['price'] = $price;
                    $result['years'] = 1;
                }
                
                $suggestions[] = $result;
            }
        }

        // Try variations with hyphens
        if (count($suggestions) < $maxSuggestions && !str_contains($parsed['sld'], '-')) {
            $variations = [
                'get-' . $parsed['sld'],
                'my-' . $parsed['sld'],
                $parsed['sld'] . '-app',
                $parsed['sld'] . '-site',
            ];

            foreach ($variations as $variation) {
                if (count($suggestions) >= $maxSuggestions) {
                    break;
                }

                $suggestedDomain = $variation . '.' . $parsed['tld'];
                
                if (in_array($suggestedDomain, $checked)) {
                    continue;
                }

                $checked[] = $suggestedDomain;

                $result = $this->checkAvailability($suggestedDomain);
                
                if ($result['available'] ?? false) {
                    if (isset($result['tld_id'])) {
                        $price = $this->calculatePrice($result['tld_id'], $partner, 1);
                        $result['price'] = $price;
                        $result['years'] = 1;
                    }
                    
                    $suggestions[] = $result;
                }
            }
        }

        return $suggestions;
    }

    /**
     * Parse bulk domain input (comma or newline separated)
     *
     * @param string $input Bulk domain input
     * @return array Array of domain names
     */
    public function parseBulkInput(string $input): array
    {
        // Split by comma or newline
        $domains = preg_split('/[\r\n,]+/', $input);
        
        // Clean up and filter
        $domains = array_map('trim', $domains);
        $domains = array_filter($domains, fn($d) => !empty($d));
        
        // Remove duplicates
        $domains = array_unique($domains);
        
        return array_values($domains);
    }

    /**
     * Get supported TLDs with pricing
     *
     * @param Partner|int|null $partner Partner for pricing
     * @return array Supported TLDs
     */
    public function getSupportedTlds(Partner|int|null $partner = null): array
    {
        $tlds = Tld::where('is_active', true)
            ->orderBy('extension')
            ->get();

        $result = [];

        foreach ($tlds as $tld) {
            $price = $this->calculatePrice($tld->id, $partner, 1);
            
            $result[] = [
                'extension' => $tld->extension,
                'price' => $price,
            ];
        }

        return $result;
    }
}
