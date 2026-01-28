<?php

namespace Tests\Unit\Services\Registrar;

use App\Exceptions\RegistrarException;
use App\Services\Registrar\AbstractRegistrar;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AbstractRegistrarTest extends TestCase
{
    protected TestableRegistrar $registrar;

    protected function setUp(): void
    {
        parent::setUp();

        $config = [
            'name' => 'Test Registrar',
            'slug' => 'test',
            'timeout' => 30,
            'enable_logging' => true,
            'rate_limit' => [
                'max_attempts' => 5,
                'decay_minutes' => 1,
            ],
        ];

        $credentials = [
            'api_key' => 'test_key',
            'api_secret' => 'test_secret',
        ];

        $this->registrar = new TestableRegistrar($config, $credentials);
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('registrar:test registrar:test_method');
        Cache::flush();
        parent::tearDown();
    }

    /**
     * Test registrar initializes with correct config.
     */
    public function test_initializes_with_config(): void
    {
        $this->assertEquals('Test Registrar', $this->registrar->getName());
    }

    /**
     * Test creates success response.
     */
    public function test_creates_success_response(): void
    {
        $response = $this->registrar->publicSuccessResponse(
            data: ['test' => 'value'],
            message: 'Success'
        );

        $this->assertTrue($response['success']);
        $this->assertEquals(['test' => 'value'], $response['data']);
        $this->assertEquals('Success', $response['message']);
        $this->assertEquals('Test Registrar', $response['registrar']);
        $this->assertEmpty($response['errors']);
    }

    /**
     * Test creates error response.
     */
    public function test_creates_error_response(): void
    {
        $response = $this->registrar->publicErrorResponse(
            message: 'Error occurred',
            errors: ['field' => 'invalid']
        );

        $this->assertFalse($response['success']);
        $this->assertEquals('Error occurred', $response['message']);
        $this->assertEquals(['field' => 'invalid'], $response['errors']);
    }

    /**
     * Test validates domain format.
     */
    public function test_validates_valid_domain(): void
    {
        $this->registrar->publicValidateDomain('example.com');
        $this->registrar->publicValidateDomain('sub.example.com');
        $this->registrar->publicValidateDomain('test-domain.co.uk');

        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test throws exception for invalid domain format.
     */
    public function test_throws_exception_for_invalid_domain(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Invalid domain name format');

        $this->registrar->publicValidateDomain('invalid domain.com');
    }

    /**
     * Test throws exception for empty domain.
     */
    public function test_throws_exception_for_empty_domain(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('cannot be empty');

        $this->registrar->publicValidateDomain('');
    }

    /**
     * Test validates required parameters.
     */
    public function test_validates_required_parameters(): void
    {
        $data = ['domain' => 'example.com', 'years' => 1];
        
        $this->registrar->publicValidateRequired($data, ['domain', 'years']);

        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test throws exception for missing required parameters.
     */
    public function test_throws_exception_for_missing_required_parameters(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Missing required parameters');

        $this->registrar->publicValidateRequired(
            ['domain' => 'example.com'],
            ['domain', 'years', 'contacts']
        );
    }

    /**
     * Test sanitizes sensitive parameters in logs.
     */
    public function test_sanitizes_sensitive_parameters(): void
    {
        $params = [
            'domain' => 'example.com',
            'api_key' => 'secret123',
            'password' => 'mypassword',
            'auth_code' => 'EPP-CODE',
            'normal_field' => 'visible',
        ];

        $sanitized = $this->registrar->publicSanitizeLogParams($params);

        $this->assertEquals('example.com', $sanitized['domain']);
        $this->assertEquals('***REDACTED***', $sanitized['api_key']);
        $this->assertEquals('***REDACTED***', $sanitized['password']);
        $this->assertEquals('***REDACTED***', $sanitized['auth_code']);
        $this->assertEquals('visible', $sanitized['normal_field']);
    }

    /**
     * Test rate limiting works.
     */
    public function test_rate_limiting_enforced(): void
    {
        // Make 5 successful calls (the limit)
        for ($i = 0; $i < 5; $i++) {
            $this->registrar->testMethod();
        }

        // 6th call should fail
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Rate limit exceeded');

        $this->registrar->testMethod();
    }

    /**
     * Test caching works correctly.
     */
    public function test_caching_works(): void
    {
        $callCount = 0;
        
        $callback = function () use (&$callCount) {
            $callCount++;
            return 'result';
        };

        // First call executes callback
        $result1 = $this->registrar->publicCacheOrExecute('test_key', 60, $callback);
        $this->assertEquals(1, $callCount);
        $this->assertEquals('result', $result1);

        // Second call uses cache
        $result2 = $this->registrar->publicCacheOrExecute('test_key', 60, $callback);
        $this->assertEquals(1, $callCount); // Still 1, not called again
        $this->assertEquals('result', $result2);
    }

    /**
     * Test clearing cache.
     */
    public function test_clearing_cache(): void
    {
        $callCount = 0;
        
        $callback = function () use (&$callCount) {
            $callCount++;
            return 'result';
        };

        $this->registrar->publicCacheOrExecute('test_key', 60, $callback);
        $this->assertEquals(1, $callCount);

        $this->registrar->publicClearCache('test_key');

        // After clearing, callback is executed again
        $this->registrar->publicCacheOrExecute('test_key', 60, $callback);
        $this->assertEquals(2, $callCount);
    }

    /**
     * Test API call execution with logging.
     */
    public function test_api_call_execution_logs_success(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'started') &&
                       $context['method'] === 'test_method';
            });

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'completed') &&
                       isset($context['duration_ms']);
            });

        $this->registrar->testMethod();
    }

    /**
     * Test API call execution logs errors.
     */
    public function test_api_call_execution_logs_errors(): void
    {
        Log::shouldReceive('info')->once(); // started
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'failed') &&
                       isset($context['error']);
            });

        $this->expectException(RegistrarException::class);

        $this->registrar->testMethodWithError();
    }
}

/**
 * Testable implementation of AbstractRegistrar for testing.
 */
class TestableRegistrar extends AbstractRegistrar
{
    public function checkAvailability(string $domain): bool
    {
        return true;
    }

    public function register(array $data): array
    {
        return $this->successResponse();
    }

    public function renew(string $domain, int $years): array
    {
        return $this->successResponse();
    }

    public function transfer(string $domain, string $authCode): array
    {
        return $this->successResponse();
    }

    public function updateNameservers(string $domain, array $nameservers): array
    {
        return $this->successResponse();
    }

    public function getContacts(string $domain): array
    {
        return $this->successResponse();
    }

    public function updateContacts(string $domain, array $contacts): array
    {
        return $this->successResponse();
    }

    public function getDnsRecords(string $domain): array
    {
        return $this->successResponse();
    }

    public function updateDnsRecords(string $domain, array $records): array
    {
        return $this->successResponse();
    }

    public function getInfo(string $domain): array
    {
        return $this->successResponse();
    }

    public function lock(string $domain): bool
    {
        return true;
    }

    public function unlock(string $domain): bool
    {
        return true;
    }

    public function getTransferStatus(string $domain): array
    {
        return $this->successResponse();
    }

    public function cancelTransfer(string $domain): array
    {
        return $this->successResponse();
    }

    public function getAuthCode(string $domain): array
    {
        return $this->successResponse(['auth_code' => 'TEST-AUTH-CODE']);
    }

    public function testConnection(): bool
    {
        return true;
    }

    protected function makeRequest(string $endpoint, string $method = 'GET', array $data = []): mixed
    {
        return ['status' => 'ok'];
    }

    // Public wrappers for testing protected methods
    public function publicSuccessResponse($data = null, $message = 'Success', $registrarResponse = null): array
    {
        return $this->successResponse($data, $message, $registrarResponse);
    }

    public function publicErrorResponse($message, $errors = [], $registrarResponse = null): array
    {
        return $this->errorResponse($message, $errors, $registrarResponse);
    }

    public function publicValidateDomain(string $domain): void
    {
        $this->validateDomain($domain);
    }

    public function publicValidateRequired(array $data, array $required): void
    {
        $this->validateRequired($data, $required);
    }

    public function publicSanitizeLogParams(array $params): array
    {
        return $this->sanitizeLogParams($params);
    }

    public function publicCacheOrExecute(string $key, int $ttl, callable $callback): mixed
    {
        return $this->cacheOrExecute($key, $ttl, $callback);
    }

    public function publicClearCache(string $key): void
    {
        $this->clearCache($key);
    }

    public function testMethod(): string
    {
        return $this->executeApiCall('test_method', fn() => 'success', ['test' => 'data']);
    }

    public function testMethodWithError(): void
    {
        $this->executeApiCall('test_method', function () {
            throw RegistrarException::connectionFailed($this->name);
        }, ['test' => 'data']);
    }
}
