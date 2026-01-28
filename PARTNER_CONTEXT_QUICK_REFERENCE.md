# Partner Context - Quick Reference

## Getting Started (5 minutes)

### 1. Access Partner Context Anywhere

```php
// Get current partner
$partner = currentPartner();

// Get branding
$branding = partnerBranding();

// Get wallet
$wallet = partnerWallet();

// Get pricing service
$pricing = partnerPricing();

// Check if partner exists
if (hasPartner()) {
    // Partner context available
}
```

### 2. In Livewire Components

```php
use App\Livewire\Concerns\HasPartnerContext;

class MyComponent extends Component
{
    use HasPartnerContext;

    public function mount()
    {
        $partner = $this->partner();
        $branding = $this->branding();
        $wallet = $this->wallet();
        $pricing = $this->pricing();
    }
}
```

### 3. In Blade Templates

```blade
<!-- Logo -->
@if(partnerBranding()?->logo_path)
    <img src="{{ Storage::url(partnerBranding()->logo_path) }}" alt="Logo">
@else
    <span>{{ partnerBranding()?->email_sender_name ?? 'DomainDesk' }}</span>
@endif

<!-- Colors -->
<style>
    :root {
        --primary: {{ partnerBranding()?->primary_color ?? '#3b82f6' }};
        --secondary: {{ partnerBranding()?->secondary_color ?? '#8b5cf6' }};
    }
</style>

<!-- Support -->
<a href="mailto:{{ partnerBranding()?->support_email }}">Support</a>
<a href="tel:{{ partnerBranding()?->support_phone }}">Call Us</a>
```

## Configuration

### Local Development (.env)

```env
PARTNER_ALLOW_MISSING=true
PARTNER_USE_DEFAULT_FALLBACK=true
DEFAULT_PARTNER_ID=1
```

### Production (.env)

```env
PARTNER_ALLOW_MISSING=false
PARTNER_USE_DEFAULT_FALLBACK=false
```

## Common Tasks

### Get Partner Wallet Balance

```php
$balance = partnerWallet()?->balance ?? 0;
```

### Calculate Price with Partner Markup

```php
$price = partnerPricing()->calculateFinalPrice(
    tld: $tld,
    partner: currentPartner(),
    action: 'register',
    years: 1
);
```

### Get Pricing Breakdown

```php
$breakdown = partnerPricing()->getPricingBreakdown(
    tld: $tld,
    partner: currentPartner(),
    action: 'register',
    years: 1
);
// Returns: ['base' => '10.00', 'markup' => '2.00', 'final' => '12.00']
```

### Manually Set Partner (Testing)

```php
use App\Services\PartnerContextService;

$service = app(PartnerContextService::class);
$service->setPartner($partner);
```

### Reset Context (Testing)

```php
app(PartnerContextService::class)->reset();
```

## Testing

### Setup Partner in Tests

```php
protected function setUp(): void
{
    parent::setUp();
    
    $partner = Partner::factory()->create([
        'is_active' => true,
        'status' => 'active'
    ]);
    
    $partner->branding()->create([
        'email_sender_name' => 'Test Partner',
        'primary_color' => '#ff0000',
    ]);
    
    $partner->wallet()->create();
    
    app(PartnerContextService::class)->setPartner($partner);
}
```

### Test with Domain

```php
public function test_something(): void
{
    $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
    
    PartnerDomain::factory()->create([
        'partner_id' => $partner->id,
        'domain' => 'test.example.com',
        'is_verified' => true,
    ]);
    
    $response = $this->get('http://test.example.com/login');
    
    $this->assertTrue(hasPartner());
}
```

## Troubleshooting

### Issue: 404 on all routes

**Solution**:
```env
PARTNER_ALLOW_MISSING=true
```

### Issue: Partner not resolving

**Check**:
1. Domain in `partner_domains` table?
2. Domain verified? (`is_verified = true`)
3. Partner active? (`is_active = true`, `status = 'active'`)
4. Clear cache: `php artisan cache:clear`

### Issue: Branding not showing

**Check**:
1. Partner has branding record?
2. Using null-safe operator: `partnerBranding()?->logo_path`
3. Storage linked: `php artisan storage:link`

## API Quick Reference

```php
// Service
partnerContext()                        // Get service
->resolveFromDomain('example.com')     // Resolve from domain
->resolveFromRequest()                 // Resolve from request
->resolveWithFallback()                // Resolve with fallback
->setPartner($partner)                 // Set manually
->getPartner()                         // Get partner
->getBranding()                        // Get branding
->getWallet()                          // Get wallet
->getPricingService()                  // Get pricing
->hasPartner()                         // Check if set
->reset()                              // Reset context

// Helpers
currentPartner()                       // Partner
partnerBranding()                      // Branding
partnerWallet()                        // Wallet
partnerPricing()                       // Pricing service
hasPartner()                           // Boolean

// Trait (in Livewire)
$this->partner()                       // Partner
$this->branding()                      // Branding
$this->wallet()                        // Wallet
$this->pricing()                       // Pricing service
$this->hasPartner()                    // Boolean
```

## Middleware Routes

```php
// Partner context applied
Route::middleware(['partner.context'])->group(function () {
    Route::get('/login', Login::class);
    Route::get('/register', Register::class);
});

// Partner context skipped
Route::prefix('admin')->group(function () {
    // Admin routes - no partner context
});
```

## Cache Keys

```
partner:domain:{domain}              // Domain resolution (5 min)
partner:default:{id}                 // Default partner (5 min)
partner:default:first               // First active (5 min)
```

Clear specific cache:
```php
Cache::forget('partner:domain:example.com');
```

## Performance Tips

1. Use caching - domains cached for 5 minutes
2. Use null-safe operators - `partnerBranding()?->logo_path`
3. Check `hasPartner()` before accessing
4. Leverage eager loading (automatic)
5. Singleton service (per-request)

## Security Checklist

- ✅ Only verified domains resolved
- ✅ Only active partners resolved
- ✅ Admin routes skip partner context
- ✅ Request-scoped isolation
- ✅ No cross-request contamination

## More Information

- **Full Documentation**: PARTNER_CONTEXT_DOCS.md
- **Implementation Summary**: PHASE_2.2_PARTNER_CONTEXT_SUMMARY.md
- **Tests**: tests/Feature/PartnerContext/
