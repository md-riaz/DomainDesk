<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class RegistrarException extends Exception
{
    /**
     * Registrar name where the error occurred.
     */
    protected string $registrarName;

    /**
     * Registrar-specific error code.
     */
    protected ?string $registrarErrorCode;

    /**
     * Raw registrar response.
     */
    protected mixed $registrarResponse;

    /**
     * Additional error details.
     */
    protected array $errorDetails;

    /**
     * Create a new registrar exception.
     *
     * @param string $message Error message
     * @param string $registrarName Name of the registrar
     * @param string|null $registrarErrorCode Registrar's error code
     * @param mixed $registrarResponse Raw registrar response
     * @param array $errorDetails Additional error details
     * @param int $code Exception code
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = "",
        string $registrarName = "",
        ?string $registrarErrorCode = null,
        mixed $registrarResponse = null,
        array $errorDetails = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->registrarName = $registrarName;
        $this->registrarErrorCode = $registrarErrorCode;
        $this->registrarResponse = $registrarResponse;
        $this->errorDetails = $errorDetails;
    }

    /**
     * Get the registrar name.
     */
    public function getRegistrarName(): string
    {
        return $this->registrarName;
    }

    /**
     * Get the registrar error code.
     */
    public function getRegistrarErrorCode(): ?string
    {
        return $this->registrarErrorCode;
    }

    /**
     * Get the raw registrar response.
     */
    public function getRegistrarResponse(): mixed
    {
        return $this->registrarResponse;
    }

    /**
     * Get additional error details.
     */
    public function getErrorDetails(): array
    {
        return $this->errorDetails;
    }

    /**
     * Convert exception to array for logging.
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'registrar' => $this->registrarName,
            'error_code' => $this->registrarErrorCode,
            'error_details' => $this->errorDetails,
            'registrar_response' => $this->registrarResponse,
            'exception_code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }

    /**
     * Create exception for API connection failure.
     */
    public static function connectionFailed(
        string $registrarName,
        string $message = 'Failed to connect to registrar API',
        ?Throwable $previous = null
    ): self {
        return new self(
            message: $message,
            registrarName: $registrarName,
            errorDetails: ['type' => 'connection_error'],
            previous: $previous
        );
    }

    /**
     * Create exception for authentication failure.
     */
    public static function authenticationFailed(
        string $registrarName,
        string $message = 'Authentication failed with registrar',
        ?string $errorCode = null
    ): self {
        return new self(
            message: $message,
            registrarName: $registrarName,
            registrarErrorCode: $errorCode,
            errorDetails: ['type' => 'authentication_error']
        );
    }

    /**
     * Create exception for rate limit exceeded.
     */
    public static function rateLimitExceeded(
        string $registrarName,
        int $retryAfter = 0
    ): self {
        return new self(
            message: 'Rate limit exceeded for registrar API',
            registrarName: $registrarName,
            errorDetails: [
                'type' => 'rate_limit_error',
                'retry_after' => $retryAfter,
            ]
        );
    }

    /**
     * Create exception for domain not found.
     */
    public static function domainNotFound(
        string $registrarName,
        string $domain
    ): self {
        return new self(
            message: "Domain not found: {$domain}",
            registrarName: $registrarName,
            errorDetails: [
                'type' => 'domain_not_found',
                'domain' => $domain,
            ]
        );
    }

    /**
     * Create exception for invalid data.
     */
    public static function invalidData(
        string $registrarName,
        string $message,
        array $errors = []
    ): self {
        return new self(
            message: $message,
            registrarName: $registrarName,
            errorDetails: [
                'type' => 'validation_error',
                'validation_errors' => $errors,
            ]
        );
    }

    /**
     * Create exception for operation timeout.
     */
    public static function timeout(
        string $registrarName,
        string $operation,
        int $timeoutSeconds = 0
    ): self {
        return new self(
            message: "Operation timed out: {$operation}",
            registrarName: $registrarName,
            errorDetails: [
                'type' => 'timeout_error',
                'operation' => $operation,
                'timeout' => $timeoutSeconds,
            ]
        );
    }
}
