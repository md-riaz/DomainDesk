# Partner Context Resolution System

## Overview

The Partner Context Resolution system provides white-label multi-tenancy support for DomainDesk. It resolves the partner from the request domain, loads partner-specific branding, pricing rules, and wallet context, and makes this information available throughout the application.

## Architecture

### Key Components

1. **PartnerContextService** - Core service that resolves and stores partner context
2. **PartnerContextMiddleware** - Middleware that resolves partner on each request
3. **Helper Functions** - Global helper functions for accessing partner context
4. **HasPartnerContext Trait** - Livewire component trait for partner access

## How It Works

### Domain Resolution

The system resolves partners based on the request domain:

```
partner1.example.com → Partner 1
panel.mybrand.com → Partner with custom domain "panel.mybrand.com"
localhost → Default partner (in development)
```

### Resolution Logic

1. Extract domain from request (`request()->getHost()`)
2. Look up partner by domain in `partner_domains` table
3. Only verified domains (`is_verified = true`) are considered
4. Only active partners (`is_active = true`, `status = 'active'`) are returned
5. Results are cached for 5 minutes per domain

### Fallback Strategy

For development environments:

```php
config(['partner.use_default_fallback' => true]); // In .env: PARTNER_USE_DEFAULT_FALLBACK=true
```

This will use the first active partner when no domain match is found.

## Usage

### In Controllers/Services

```php
// Get current partner
$partner = currentPartner();

// Get partner branding
$branding = partnerBranding();

// Get partner wallet
$wallet = partnerWallet();

// Get pricing service for current partner
$pricingService = partnerPricing();

// Check if partner context is available
if (hasPartner()) {
    // Partner context is available
}
```

### In Livewire Components

Add the `HasPartnerContext` trait:

```php
use App\Livewire\Concerns\HasPartnerContext;

class MyComponent extends Component
{
    use HasPartnerContext;

    public function someMethod()
    {
        $partner = $this->partner();
        $branding = $this->branding();
        $wallet = $this->wallet();
        $pricing = $this->pricing();
    }
}
```

### In Blade Templates

```blade
<!-- Display partner name -->
{{ currentPartner()?->name }}

<!-- Display partner logo -->
@if(partnerBranding()?->logo_path)
    <img src="{{ Storage::url(partnerBranding()->logo_path) }}" alt="Logo">
@endif

<!-- Use partner colors -->
<style>
    :root {
        --partner-primary: {{ partnerBranding()?->primary_color ?? '#3b82f6' }};
        --partner-secondary: {{ partnerBranding()?->secondary_color ?? '#8b5cf6' }};
    }
</style>

<!-- Display support email -->
@if(partnerBranding()?->support_email)
    <a href="mailto:{{ partnerBranding()->support_email }}">Contact Support</a>
@endif
```

## Middleware Configuration

### Route Groups

```php
// Partner context is applied to these routes
Route::middleware(['partner.context'])->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

// Admin routes skip partner context
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->group(function () {
    // No partner context here
});
```

### Middleware Behavior

- Runs before Livewire renders
- Skips admin routes (routes starting with `/admin`)
- Returns 404 if partner not found (configurable)
- Uses fallback partner if configured
- Stores partner context in singleton service (per request)

## Configuration

### config/partner.php

```php
return [
    // Default partner ID for local development
    'default_partner_id' => env('DEFAULT_PARTNER_ID'),

    // Use default partner as fallback when no domain match
    'use_default_fallback' => env('PARTNER_USE_DEFAULT_FALLBACK', env('APP_ENV') === 'local'),

    // Allow requests without partner context (useful for testing)
    'allow_missing_partner' => env('PARTNER_ALLOW_MISSING', false),
];
```

### Environment Variables

```env
# Production - strict mode
PARTNER_ALLOW_MISSING=false
PARTNER_USE_DEFAULT_FALLBACK=false

# Local Development - relaxed mode
PARTNER_ALLOW_MISSING=true
PARTNER_USE_DEFAULT_FALLBACK=true
DEFAULT_PARTNER_ID=1

# Testing - allow all
# Set in phpunit.xml
```

## Caching Strategy

### Domain Resolution Cache

- Cache key: `partner:domain:{domain}`
- TTL: 5 minutes (300 seconds)
- Invalidate when: Partner domain is updated/deleted

### Default Partner Cache

- Cache key: `partner:default:{id}` or `partner:default:first`
- TTL: 5 minutes
- Invalidate when: Partner status changes

### Manual Cache Invalidation

```php
use Illuminate\Support\Facades\Cache;

// Clear specific domain cache
Cache::forget('partner:domain:partner.example.com');

// Clear default partner cache
Cache::forget('partner:default:first');
Cache::forget('partner:default:1');
```

## Security Considerations

### Tenant Isolation

Partner context is resolved per request and stored in a singleton service. This ensures:

1. No cross-request contamination (each request gets fresh context)
2. Thread-safe context storage (Laravel handles request isolation)
3. No data leakage between partners

### Domain Verification

Only verified domains (`is_verified = true`) are used for partner resolution. This prevents:

1. Unauthorized partners from hijacking domains
2. Partners from using unverified custom domains
3. DNS spoofing attacks

### Admin Route Protection

Admin routes (`/admin/*`) explicitly skip partner resolution to ensure:

1. Super admins can access the platform regardless of domain
2. No partner context interferes with admin operations
3. Admin panel remains accessible even if partner resolution fails

## Testing

### Setting Up Tests

```php
use App\Models\Partner;
use App\Models\PartnerDomain;
use App\Services\PartnerContextService;

protected function setUp(): void
{
    parent::setUp();
    
    // Create partner for testing
    $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
    $partner->branding()->create();
    $partner->wallet()->create();
    
    PartnerDomain::factory()->create([
        'partner_id' => $partner->id,
        'domain' => 'test.example.com',
        'is_verified' => true,
    ]);
    
    // Optionally set partner context manually
    app(PartnerContextService::class)->setPartner($partner);
}
```

### Testing With Domain

```php
public function test_partner_resolution_from_domain(): void
{
    $response = $this->get('http://test.example.com/login');
    
    $this->assertTrue(hasPartner());
    $this->assertNotNull(currentPartner());
}
```

### Resetting Context Between Tests

```php
protected function tearDown(): void
{
    app(PartnerContextService::class)->reset();
    parent::tearDown();
}
```

## Troubleshooting

### Partner Not Resolving

1. Check if domain is verified: `partner_domains.is_verified = true`
2. Check if partner is active: `partners.is_active = true` and `status = 'active'`
3. Check if domain matches exactly (no wildcards)
4. Clear cache: `php artisan cache:clear`

### 404 Errors in Production

1. Verify DNS is pointing to correct server
2. Ensure domain is added to `partner_domains` table
3. Verify domain is marked as verified
4. Check middleware configuration in routes

### Local Development Issues

Set these in `.env`:

```env
PARTNER_ALLOW_MISSING=true
PARTNER_USE_DEFAULT_FALLBACK=true
DEFAULT_PARTNER_ID=1
```

## API Reference

### PartnerContextService

```php
// Resolve partner from domain
resolveFromDomain(string $domain): ?Partner

// Resolve partner from current request
resolveFromRequest(): ?Partner

// Resolve with fallback to default
resolveWithFallback(): ?Partner

// Set partner manually
setPartner(?Partner $partner): void

// Get current partner
getPartner(): ?Partner

// Get partner branding
getBranding(): ?PartnerBranding

// Get partner wallet
getWallet(): ?Wallet

// Get pricing service
getPricingService(): PricingService

// Check if partner is set
hasPartner(): bool

// Check if context is resolved
isResolved(): bool

// Reset context
reset(): void

// Get default partner
getDefaultPartner(): ?Partner
```

### Helper Functions

```php
partnerContext(): PartnerContextService
currentPartner(): ?Partner
partnerBranding(): ?PartnerBranding
partnerWallet(): ?Wallet
partnerPricing(): PricingService
hasPartner(): bool
```

## Performance Considerations

1. **Caching**: Domain resolution is cached for 5 minutes to reduce database queries
2. **Eager Loading**: Branding and wallet are eager loaded with partner
3. **Singleton Service**: Context is resolved once per request
4. **Database Indexes**: Ensure indexes on `partner_domains.domain` and `partners.is_active`

## Future Enhancements

1. Multi-domain support per partner with priority ordering
2. Subdomain wildcard matching (*.partner.com)
3. Path-based partner resolution (/partner-slug/...)
4. Partner-specific caching strategies
5. Partner context events (PartnerResolved, PartnerNotFound)
