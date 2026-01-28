<?php

namespace App\Services\Registrar;

use App\Contracts\RegistrarInterface;
use App\Exceptions\RegistrarException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

abstract class AbstractRegistrar implements RegistrarInterface
{
    /**
     * Registrar configuration.
     */
    protected array $config;

    /**
     * Registrar credentials.
     */
    protected array $credentials;

    /**
     * Registrar name.
     */
    protected string $name;

    /**
     * API base URL.
     */
    protected string $apiUrl;

    /**
     * Request timeout in seconds.
     */
    protected int $timeout = 30;

    /**
     * Rate limit key prefix.
     */
    protected string $rateLimitPrefix;

    /**
     * Enable logging of API calls.
     */
    protected bool $enableLogging = true;

    /**
     * Create a new registrar instance.
     *
     * @param array $config Registrar configuration
     * @param array $credentials API credentials
     */
    public function __construct(array $config, array $credentials)
    {
        $this->config = $config;
        $this->credentials = $credentials;
        $this->name = $config['name'] ?? 'Unknown';
        $this->apiUrl = $config['api_url'] ?? '';
        $this->timeout = $config['timeout'] ?? 30;
        $this->enableLogging = $config['enable_logging'] ?? true;
        $this->rateLimitPrefix = 'registrar:' . strtolower($this->name);

        $this->initialize();
    }

    /**
     * Initialize registrar-specific settings.
     */
    protected function initialize(): void
    {
        // Override in child classes for custom initialization
    }

    /**
     * Get the registrar name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Execute an API call with logging and error handling.
     *
     * @param string $method API method name
     * @param callable $callback The API call to execute
     * @param array $params Parameters for logging
     * @return mixed
     * @throws RegistrarException
     */
    protected function executeApiCall(string $method, callable $callback, array $params = []): mixed
    {
        $startTime = microtime(true);
        $logContext = [
            'registrar' => $this->name,
            'method' => $method,
            'params' => $this->sanitizeLogParams($params),
        ];

        // Check rate limit
        $this->checkRateLimit($method);

        try {
            if ($this->enableLogging) {
                Log::info("Registrar API call started", $logContext);
            }

            $result = $callback();

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($this->enableLogging) {
                Log::info("Registrar API call completed", array_merge($logContext, [
                    'duration_ms' => $duration,
                    'success' => true,
                ]));
            }

            return $result;
        } catch (RegistrarException $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error("Registrar API call failed", array_merge($logContext, [
                'duration_ms' => $duration,
                'error' => $e->getMessage(),
                'error_code' => $e->getRegistrarErrorCode(),
                'error_details' => $e->getErrorDetails(),
            ]));

            throw $e;
        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error("Registrar API call exception", array_merge($logContext, [
                'duration_ms' => $duration,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]));

            throw new RegistrarException(
                message: "Registrar API error: {$e->getMessage()}",
                registrarName: $this->name,
                errorDetails: ['original_exception' => get_class($e)],
                previous: $e
            );
        }
    }

    /**
     * Check rate limit for API calls.
     *
     * @param string $method API method
     * @throws RegistrarException
     */
    protected function checkRateLimit(string $method): void
    {
        $key = $this->rateLimitPrefix . ':' . $method;
        $maxAttempts = $this->config['rate_limit']['max_attempts'] ?? 60;
        $decayMinutes = $this->config['rate_limit']['decay_minutes'] ?? 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);
            
            throw RegistrarException::rateLimitExceeded($this->name, $retryAfter);
        }

        RateLimiter::hit($key, $decayMinutes * 60);
    }

    /**
     * Sanitize parameters for logging (remove sensitive data).
     *
     * @param array $params Parameters to sanitize
     * @return array
     */
    protected function sanitizeLogParams(array $params): array
    {
        $sensitiveKeys = [
            'password',
            'api_key',
            'api_secret',
            'auth_code',
            'token',
            'secret',
            'credential',
        ];

        $sanitized = $params;

        array_walk_recursive($sanitized, function (&$value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $value = '***REDACTED***';
            }
        });

        return $sanitized;
    }

    /**
     * Create a standardized response.
     *
     * @param bool $success Success status
     * @param mixed $data Response data
     * @param string $message Response message
     * @param array $errors Error messages
     * @param mixed $registrarResponse Raw registrar response
     * @return array
     */
    protected function createResponse(
        bool $success,
        mixed $data = null,
        string $message = '',
        array $errors = [],
        mixed $registrarResponse = null
    ): array {
        return [
            'success' => $success,
            'data' => $data,
            'message' => $message,
            'errors' => $errors,
            'registrar_response' => $registrarResponse,
            'registrar' => $this->name,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Create a success response.
     *
     * @param mixed $data Response data
     * @param string $message Success message
     * @param mixed $registrarResponse Raw registrar response
     * @return array
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Operation completed successfully',
        mixed $registrarResponse = null
    ): array {
        return $this->createResponse(
            success: true,
            data: $data,
            message: $message,
            registrarResponse: $registrarResponse
        );
    }

    /**
     * Create an error response.
     *
     * @param string $message Error message
     * @param array $errors Error details
     * @param mixed $registrarResponse Raw registrar response
     * @return array
     */
    protected function errorResponse(
        string $message,
        array $errors = [],
        mixed $registrarResponse = null
    ): array {
        return $this->createResponse(
            success: false,
            message: $message,
            errors: $errors,
            registrarResponse: $registrarResponse
        );
    }

    /**
     * Get cached data or execute callback and cache result.
     *
     * @param string $key Cache key
     * @param int $ttl Time to live in seconds
     * @param callable $callback Callback to execute if cache miss
     * @return mixed
     */
    protected function cacheOrExecute(string $key, int $ttl, callable $callback): mixed
    {
        $cacheKey = $this->rateLimitPrefix . ':cache:' . $key;

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Clear cached data for a specific key.
     *
     * @param string $key Cache key
     */
    protected function clearCache(string $key): void
    {
        $cacheKey = $this->rateLimitPrefix . ':cache:' . $key;
        Cache::forget($cacheKey);
    }

    /**
     * Validate domain name format.
     *
     * @param string $domain Domain name to validate
     * @throws RegistrarException
     */
    protected function validateDomain(string $domain): void
    {
        if (empty($domain)) {
            throw RegistrarException::invalidData(
                $this->name,
                'Domain name cannot be empty'
            );
        }

        // Domain must contain at least one dot (require TLD)
        if (!str_contains($domain, '.')) {
            throw RegistrarException::invalidData(
                $this->name,
                'Domain must contain a TLD',
                ['domain' => $domain]
            );
        }

        if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/i', $domain)) {
            throw RegistrarException::invalidData(
                $this->name,
                'Invalid domain name format',
                ['domain' => $domain]
            );
        }
    }

    /**
     * Validate required parameters.
     *
     * @param array $data Data to validate
     * @param array $required Required keys
     * @throws RegistrarException
     */
    protected function validateRequired(array $data, array $required): void
    {
        $missing = [];

        foreach ($required as $key) {
            if (!array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            throw RegistrarException::invalidData(
                $this->name,
                'Missing required parameters: ' . implode(', ', $missing),
                ['missing_fields' => $missing]
            );
        }
    }

    /**
     * Test API connection and credentials.
     */
    abstract public function testConnection(): bool;

    /**
     * Make HTTP request to registrar API.
     * Override this in child classes for registrar-specific implementations.
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array $data Request data
     * @return mixed
     */
    abstract protected function makeRequest(string $endpoint, string $method = 'GET', array $data = []): mixed;
}
