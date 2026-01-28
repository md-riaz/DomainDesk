# Registrar Interface & Abstraction Layer

## Overview

This document describes the Registrar Interface Contract and abstraction layer implemented in Phase 3.1 of the DomainDesk project. This is the foundation for all registrar integrations and provides a standardized way to interact with different domain registrars.

## Architecture

The registrar system consists of four main components:

1. **RegistrarInterface** - Contract defining all required methods
2. **AbstractRegistrar** - Base class with common functionality
3. **RegistrarFactory** - Factory for creating registrar instances
4. **RegistrarException** - Custom exception for registrar errors

## Components

### 1. RegistrarInterface

Located at: `app/Contracts/RegistrarInterface.php`

Defines the contract that all registrar implementations must follow:

#### Required Methods

- `checkAvailability(string $domain): bool` - Check if domain is available
- `register(array $data): array` - Register a new domain
- `renew(string $domain, int $years): array` - Renew an existing domain
- `transfer(string $domain, string $authCode): array` - Transfer domain from another registrar
- `updateNameservers(string $domain, array $nameservers): array` - Update nameservers
- `getContacts(string $domain): array` - Get domain contact information
- `updateContacts(string $domain, array $contacts): array` - Update domain contacts
- `getDnsRecords(string $domain): array` - Get DNS records (if supported)
- `updateDnsRecords(string $domain, array $records): array` - Update DNS records
- `getInfo(string $domain): array` - Get domain information
- `lock(string $domain): bool` - Lock domain (prevent transfers)
- `unlock(string $domain): bool` - Unlock domain (allow transfers)
- `getName(): string` - Get registrar name
- `testConnection(): bool` - Test API credentials

### 2. AbstractRegistrar

Located at: `app/Services/Registrar/AbstractRegistrar.php`

Base class providing common functionality for all registrar implementations.

#### Key Features

**Logging**: All API calls are automatically logged with:
- Registrar name
- Method called
- Parameters (with sensitive data redacted)
- Execution duration
- Success/failure status

**Rate Limiting**: Built-in rate limiting to prevent API throttling:
```php
'rate_limit' => [
    'max_attempts' => 60,
    'decay_minutes' => 1,
]
```

**Caching**: Helper methods for caching API responses:
```php
$result = $this->cacheOrExecute('cache_key', 300, function() {
    return $this->makeApiCall();
});
```

**Error Handling**: Automatic exception handling and standardization

**Response Standardization**: All responses follow the same format

**Validation**: Built-in validators for:
- Domain name format
- Required parameters
- Data sanitization

#### Protected Helper Methods

- `executeApiCall()` - Execute API calls with logging and error handling
- `checkRateLimit()` - Enforce rate limiting
- `sanitizeLogParams()` - Remove sensitive data from logs
- `createResponse()` - Create standardized response
- `successResponse()` - Create success response
- `errorResponse()` - Create error response
- `cacheOrExecute()` - Cache helper
- `validateDomain()` - Validate domain format
- `validateRequired()` - Validate required parameters

### 3. RegistrarFactory

Located at: `app/Services/Registrar/RegistrarFactory.php`

Factory pattern for creating and caching registrar instances.

#### Usage Examples

**Get registrar by ID:**
```php
$registrar = RegistrarFactory::make(1);
```

**Get registrar by slug:**
```php
$registrar = RegistrarFactory::make('resellerclub');
```

**Get default registrar:**
```php
$registrar = RegistrarFactory::default();
```

**Get all active registrars:**
```php
$registrars = RegistrarFactory::all();
```

**Clear cache:**
```php
// Clear specific registrar
RegistrarFactory::clearCache('resellerclub');

// Clear all
RegistrarFactory::clearCache();
```

**Check if registrar exists:**
```php
if (RegistrarFactory::exists('resellerclub')) {
    // Registrar exists
}
```

#### Features

- **Singleton Pattern**: Each registrar is instantiated once and cached
- **Automatic Resolution**: Resolves registrar class from database configuration
- **Validation**: Validates registrar is active and class exists
- **Configuration Merging**: Merges default and registrar-specific configuration

### 4. RegistrarException

Located at: `app/Exceptions/RegistrarException.php`

Custom exception for registrar-related errors.

#### Factory Methods

```php
// Connection failure
throw RegistrarException::connectionFailed('ResellerClub');

// Authentication failure
throw RegistrarException::authenticationFailed('ResellerClub', 'Invalid API key', 'AUTH_001');

// Rate limit exceeded
throw RegistrarException::rateLimitExceeded('ResellerClub', 60);

// Domain not found
throw RegistrarException::domainNotFound('ResellerClub', 'example.com');

// Invalid data
throw RegistrarException::invalidData('ResellerClub', 'Validation failed', ['domain' => 'required']);

// Timeout
throw RegistrarException::timeout('ResellerClub', 'register', 30);
```

#### Properties

- `registrarName` - Name of the registrar
- `registrarErrorCode` - Registrar-specific error code
- `registrarResponse` - Raw API response
- `errorDetails` - Additional error details

## Standard Response Format

All registrar methods (except boolean returns) use this format:

```php
[
    'success' => true|false,           // Operation success status
    'data' => mixed,                   // Response data
    'message' => 'Success message',    // Human-readable message
    'errors' => [],                    // Error details (if any)
    'registrar_response' => mixed,     // Raw registrar response
    'registrar' => 'Registrar Name',   // Registrar name
    'timestamp' => '2024-01-28T...',   // ISO 8601 timestamp
]
```

## Configuration

Configuration file: `config/registrar.php`

### Default Configuration

```php
'defaults' => [
    'timeout' => 30,
    'enable_logging' => true,
    'rate_limit' => [
        'max_attempts' => 60,
        'decay_minutes' => 1,
    ],
    'cache_ttl' => 300,
    'retry' => [
        'enabled' => true,
        'max_attempts' => 3,
        'delay' => 1000,
    ],
]
```

### Registrar-Specific Configuration

```php
'registrars' => [
    'resellerclub' => [
        'api_url' => env('RESELLERCLUB_API_URL'),
        'test_mode' => env('RESELLERCLUB_TEST_MODE', false),
        'timeout' => 45,
    ],
]
```

### Feature Support Matrix

Define which features each registrar supports:

```php
'features' => [
    'resellerclub' => [
        'dns_management' => true,
        'whois_privacy' => true,
        'domain_forwarding' => true,
        'dnssec' => false,
        'auto_renew' => true,
    ],
]
```

## MockRegistrar

Located at: `app/Services/Registrar/MockRegistrar.php`

A fully functional mock registrar for testing and development.

### Features

- Returns fake responses without making API calls
- Configurable delays to simulate network latency
- Configurable failure rate for testing error handling
- Follows all interface requirements
- Perfect for testing without API credentials

### Usage

```php
// In tests
$registrar = RegistrarFactory::make('mock');

// Check availability
$available = $registrar->checkAvailability('example.com');

// Register domain
$result = $registrar->register([
    'domain' => 'example.com',
    'years' => 1,
    'contacts' => [
        'registrant' => ['name' => 'John Doe'],
    ],
]);
```

### Configuration

```php
'mock' => [
    'simulate_delays' => false,
    'default_delay_ms' => 100,
    'failure_rate' => 0, // 0-100 percentage
]
```

## Creating a New Registrar

### Step 1: Create Registrar Class

```php
namespace App\Services\Registrar;

use App\Exceptions\RegistrarException;

class MyRegistrar extends AbstractRegistrar
{
    protected function initialize(): void
    {
        // Custom initialization
    }

    public function checkAvailability(string $domain): bool
    {
        return $this->executeApiCall('checkAvailability', function () use ($domain) {
            $this->validateDomain($domain);
            
            $response = $this->makeRequest('/domains/available', 'GET', [
                'domain' => $domain,
            ]);
            
            return $response['available'] ?? false;
        }, ['domain' => $domain]);
    }

    // Implement other interface methods...

    protected function makeRequest(string $endpoint, string $method = 'GET', array $data = []): mixed
    {
        // Implement HTTP client for registrar API
    }

    public function testConnection(): bool
    {
        // Test API credentials
    }
}
```

### Step 2: Add to Database

```php
Registrar::create([
    'name' => 'My Registrar',
    'slug' => 'my-registrar',
    'api_class' => MyRegistrar::class,
    'credentials' => [
        'api_key' => 'your-key',
        'api_secret' => 'your-secret',
    ],
    'is_active' => true,
]);
```

### Step 3: Add Configuration

In `config/registrar.php`:

```php
'registrars' => [
    'my-registrar' => [
        'api_url' => env('MY_REGISTRAR_API_URL'),
        'timeout' => 45,
    ],
]
```

### Step 4: Use the Registrar

```php
$registrar = RegistrarFactory::make('my-registrar');
$result = $registrar->checkAvailability('example.com');
```

## Testing

### Running Tests

```bash
# All registrar tests
php artisan test --filter=Registrar

# Specific test suites
php artisan test --filter=RegistrarInterfaceTest
php artisan test --filter=RegistrarExceptionTest
php artisan test --filter=AbstractRegistrarTest
php artisan test --filter=RegistrarFactoryTest
php artisan test --filter=MockRegistrarTest
```

### Test Coverage

- **RegistrarInterfaceTest**: Validates interface contract
- **RegistrarExceptionTest**: Tests exception factory methods
- **AbstractRegistrarTest**: Tests base class functionality
- **RegistrarFactoryTest**: Tests factory pattern
- **MockRegistrarTest**: Tests mock implementation

## Best Practices

### 1. Always Use Factory

```php
// ✅ Good
$registrar = RegistrarFactory::make('resellerclub');

// ❌ Bad
$registrar = new ResellerClubRegistrar($config, $credentials);
```

### 2. Handle Exceptions

```php
try {
    $result = $registrar->register($data);
} catch (RegistrarException $e) {
    Log::error('Registration failed', [
        'registrar' => $e->getRegistrarName(),
        'error' => $e->getMessage(),
        'details' => $e->getErrorDetails(),
    ]);
}
```

### 3. Use Caching

```php
// Cache domain info for 5 minutes
$info = $this->cacheOrExecute(
    "domain_info_{$domain}",
    300,
    fn() => $this->getInfo($domain)
);
```

### 4. Validate Input

```php
// Validate required parameters
$this->validateRequired($data, ['domain', 'years', 'contacts']);

// Validate domain format
$this->validateDomain($domain);
```

### 5. Return Standardized Responses

```php
return $this->successResponse(
    data: ['domain' => $domain, 'status' => 'active'],
    message: 'Domain registered successfully'
);
```

## Security Considerations

1. **Sensitive Data**: Credentials are automatically redacted from logs
2. **Rate Limiting**: Built-in protection against API abuse
3. **Validation**: All inputs are validated before API calls
4. **Exception Handling**: Errors don't expose sensitive information
5. **Audit Trail**: All operations are logged

## Future Enhancements

Phase 3.1 provides the foundation. Future phases will add:

- **Phase 3.2**: ResellerClub/LogicBoxes Integration
- **Phase 3.3**: Registrar Sync Service
- Additional registrar integrations (Namecheap, GoDaddy, etc.)

Note: MockRegistrar was implemented in Phase 3.1 as part of the initial foundation.

## Support

For issues or questions:
1. Check logs in `storage/logs/laravel.log`
2. Review test files for usage examples
3. Consult this documentation
4. Check registrar-specific API documentation

## Changelog

- **2026-01-28**: Initial implementation (Phase 3.1)
  - RegistrarInterface created
  - AbstractRegistrar base class
  - RegistrarFactory implementation
  - RegistrarException with factory methods
  - MockRegistrar for testing
  - Comprehensive test suite
  - Configuration system
