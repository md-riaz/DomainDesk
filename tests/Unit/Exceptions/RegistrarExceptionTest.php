<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\RegistrarException;
use Tests\TestCase;

class RegistrarExceptionTest extends TestCase
{
    /**
     * Test basic exception creation.
     */
    public function test_creates_exception_with_message(): void
    {
        $exception = new RegistrarException(
            message: 'Test error',
            registrarName: 'TestRegistrar'
        );

        $this->assertEquals('Test error', $exception->getMessage());
        $this->assertEquals('TestRegistrar', $exception->getRegistrarName());
    }

    /**
     * Test exception with error code.
     */
    public function test_stores_registrar_error_code(): void
    {
        $exception = new RegistrarException(
            message: 'Test error',
            registrarName: 'TestRegistrar',
            registrarErrorCode: 'ERR_123'
        );

        $this->assertEquals('ERR_123', $exception->getRegistrarErrorCode());
    }

    /**
     * Test exception with registrar response.
     */
    public function test_stores_registrar_response(): void
    {
        $response = ['status' => 'error', 'data' => 'test'];
        
        $exception = new RegistrarException(
            message: 'Test error',
            registrarName: 'TestRegistrar',
            registrarResponse: $response
        );

        $this->assertEquals($response, $exception->getRegistrarResponse());
    }

    /**
     * Test exception with error details.
     */
    public function test_stores_error_details(): void
    {
        $details = ['field' => 'domain', 'issue' => 'invalid'];
        
        $exception = new RegistrarException(
            message: 'Test error',
            registrarName: 'TestRegistrar',
            errorDetails: $details
        );

        $this->assertEquals($details, $exception->getErrorDetails());
    }

    /**
     * Test toArray method.
     */
    public function test_converts_to_array(): void
    {
        $exception = new RegistrarException(
            message: 'Test error',
            registrarName: 'TestRegistrar',
            registrarErrorCode: 'ERR_123',
            errorDetails: ['type' => 'test']
        );

        $array = $exception->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('Test error', $array['message']);
        $this->assertEquals('TestRegistrar', $array['registrar']);
        $this->assertEquals('ERR_123', $array['error_code']);
        $this->assertEquals(['type' => 'test'], $array['error_details']);
        $this->assertArrayHasKey('file', $array);
        $this->assertArrayHasKey('line', $array);
    }

    /**
     * Test connectionFailed factory method.
     */
    public function test_connection_failed_factory(): void
    {
        $exception = RegistrarException::connectionFailed('TestRegistrar');

        $this->assertStringContainsString('connect', strtolower($exception->getMessage()));
        $this->assertEquals('TestRegistrar', $exception->getRegistrarName());
        $this->assertEquals('connection_error', $exception->getErrorDetails()['type']);
    }

    /**
     * Test authenticationFailed factory method.
     */
    public function test_authentication_failed_factory(): void
    {
        $exception = RegistrarException::authenticationFailed(
            'TestRegistrar',
            'Invalid credentials',
            'AUTH_001'
        );

        $this->assertEquals('Invalid credentials', $exception->getMessage());
        $this->assertEquals('TestRegistrar', $exception->getRegistrarName());
        $this->assertEquals('AUTH_001', $exception->getRegistrarErrorCode());
        $this->assertEquals('authentication_error', $exception->getErrorDetails()['type']);
    }

    /**
     * Test rateLimitExceeded factory method.
     */
    public function test_rate_limit_exceeded_factory(): void
    {
        $exception = RegistrarException::rateLimitExceeded('TestRegistrar', 60);

        $this->assertStringContainsString('rate limit', strtolower($exception->getMessage()));
        $this->assertEquals('TestRegistrar', $exception->getRegistrarName());
        $this->assertEquals('rate_limit_error', $exception->getErrorDetails()['type']);
        $this->assertEquals(60, $exception->getErrorDetails()['retry_after']);
    }

    /**
     * Test domainNotFound factory method.
     */
    public function test_domain_not_found_factory(): void
    {
        $exception = RegistrarException::domainNotFound('TestRegistrar', 'example.com');

        $this->assertStringContainsString('example.com', $exception->getMessage());
        $this->assertEquals('TestRegistrar', $exception->getRegistrarName());
        $this->assertEquals('domain_not_found', $exception->getErrorDetails()['type']);
        $this->assertEquals('example.com', $exception->getErrorDetails()['domain']);
    }

    /**
     * Test invalidData factory method.
     */
    public function test_invalid_data_factory(): void
    {
        $errors = ['domain' => 'required', 'years' => 'must be integer'];
        $exception = RegistrarException::invalidData(
            'TestRegistrar',
            'Validation failed',
            $errors
        );

        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals('TestRegistrar', $exception->getRegistrarName());
        $this->assertEquals('validation_error', $exception->getErrorDetails()['type']);
        $this->assertEquals($errors, $exception->getErrorDetails()['validation_errors']);
    }

    /**
     * Test timeout factory method.
     */
    public function test_timeout_factory(): void
    {
        $exception = RegistrarException::timeout('TestRegistrar', 'register', 30);

        $this->assertStringContainsString('register', $exception->getMessage());
        $this->assertEquals('TestRegistrar', $exception->getRegistrarName());
        $this->assertEquals('timeout_error', $exception->getErrorDetails()['type']);
        $this->assertEquals('register', $exception->getErrorDetails()['operation']);
        $this->assertEquals(30, $exception->getErrorDetails()['timeout']);
    }

    /**
     * Test exception with previous exception.
     */
    public function test_stores_previous_exception(): void
    {
        $previous = new \Exception('Previous error');
        $exception = new RegistrarException(
            message: 'Test error',
            registrarName: 'TestRegistrar',
            previous: $previous
        );

        $this->assertSame($previous, $exception->getPrevious());
    }
}
