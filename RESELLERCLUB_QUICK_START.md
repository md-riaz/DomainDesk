# ResellerClub Quick Start Guide

## Installation

### 1. Add Credentials to .env
```env
RESELLERCLUB_API_URL=https://test.httpapi.com/api
RESELLERCLUB_TEST_MODE=true
RESELLERCLUB_AUTH_USERID=your-reseller-id
RESELLERCLUB_API_KEY=your-api-key
```

### 2. Create Registrar Record
```php
use App\Models\Registrar;
use App\Services\Registrar\ResellerClubRegistrar;

Registrar::create([
    'name' => 'ResellerClub',
    'slug' => 'resellerclub',
    'api_class' => ResellerClubRegistrar::class,
    'credentials' => [
        'auth_userid' => env('RESELLERCLUB_AUTH_USERID'),
        'api_key' => env('RESELLERCLUB_API_KEY'),
    ],
    'is_active' => true,
]);
```

## Quick Examples

### Check Availability
```php
use App\Services\Registrar\RegistrarFactory;

$registrar = RegistrarFactory::make('resellerclub');
$available = $registrar->checkAvailability('example.com'); // true/false
```

### Register Domain
```php
$result = $registrar->register([
    'domain' => 'example.com',
    'years' => 1,
    'contacts' => [
        'registrant' => [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1.5555551234',
            'address' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'zip' => '12345',
            'country' => 'US',
        ],
    ],
]);

// Returns:
// [
//     'success' => true,
//     'data' => [
//         'domain' => 'example.com',
//         'order_id' => 123456789,
//         'status' => 'active',
//         'expiry_date' => '2025-01-28T...',
//     ],
// ]
```

### Renew Domain
```php
$result = $registrar->renew('example.com', 2); // Renew for 2 years
```

### Update Nameservers
```php
$result = $registrar->updateNameservers('example.com', [
    'ns1.newhost.com',
    'ns2.newhost.com',
]);
```

### Get Domain Info
```php
$result = $registrar->getInfo('example.com');
echo $result['data']['status']; // 'active'
echo $result['data']['expiry_date']; // '2025-01-28T...'
echo $result['data']['locked']; // true/false
```

### Lock/Unlock Domain
```php
$registrar->lock('example.com'); // Enable theft protection
$registrar->unlock('example.com'); // Disable theft protection
```

### DNS Management
```php
// Get DNS records
$result = $registrar->getDnsRecords('example.com');
$records = $result['data']['records'];

// Update DNS records
$registrar->updateDnsRecords('example.com', [
    ['type' => 'A', 'name' => '@', 'value' => '192.0.2.1', 'ttl' => 3600],
    ['type' => 'A', 'name' => 'www', 'value' => '192.0.2.1', 'ttl' => 3600],
    ['type' => 'MX', 'name' => '@', 'value' => 'mail.example.com', 'priority' => 10, 'ttl' => 3600],
]);
```

## Error Handling

```php
use App\Exceptions\RegistrarException;

try {
    $result = $registrar->register($data);
} catch (RegistrarException $e) {
    Log::error('Registration failed', [
        'registrar' => $e->getRegistrarName(),
        'error' => $e->getMessage(),
        'code' => $e->getRegistrarErrorCode(),
    ]);
}
```

## Common Operations

| Operation | Method | Parameters |
|-----------|--------|------------|
| Check Availability | `checkAvailability($domain)` | domain |
| Register | `register($data)` | domain, years, contacts, nameservers |
| Renew | `renew($domain, $years)` | domain, years |
| Transfer | `transfer($domain, $authCode)` | domain, authCode |
| Update NS | `updateNameservers($domain, $ns)` | domain, nameservers array |
| Get Info | `getInfo($domain)` | domain |
| Lock | `lock($domain)` | domain |
| Unlock | `unlock($domain)` | domain |

## Testing

All operations are fully tested with mocked HTTP responses:

```bash
php artisan test --filter=ResellerClubRegistrarTest
```

## Documentation

See `PHASE_3.3_RESELLERCLUB_INTEGRATION.md` for complete documentation.

## Support

- API Docs: https://manage.resellerclub.com/kb/answer/744
- Configuration: `config/registrar.php`
- Logs: `storage/logs/laravel.log`
