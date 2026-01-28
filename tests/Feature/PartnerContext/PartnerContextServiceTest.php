<?php

namespace Tests\Feature\PartnerContext;

use App\Models\Partner;
use App\Models\PartnerBranding;
use App\Models\PartnerDomain;
use App\Models\Wallet;
use App\Services\PartnerContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PartnerContextServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PartnerContextService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PartnerContextService::class);
    }

    public function test_can_resolve_partner_from_domain(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $partner->branding()->create([
            'email_sender_name' => 'Test Partner',
        ]);
        $partner->wallet()->create();
        
        PartnerDomain::factory()->create([
            'partner_id' => $partner->id,
            'domain' => 'partner.example.com',
            'is_verified' => true,
        ]);

        $resolved = $this->service->resolveFromDomain('partner.example.com');

        $this->assertNotNull($resolved);
        $this->assertEquals($partner->id, $resolved->id);
        $this->assertTrue($this->service->hasPartner());
        $this->assertTrue($this->service->isResolved());
    }

    public function test_returns_null_for_unknown_domain(): void
    {
        $resolved = $this->service->resolveFromDomain('unknown.example.com');

        $this->assertNull($resolved);
        $this->assertFalse($this->service->hasPartner());
        $this->assertTrue($this->service->isResolved());
    }

    public function test_does_not_resolve_unverified_domain(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        
        PartnerDomain::factory()->create([
            'partner_id' => $partner->id,
            'domain' => 'unverified.example.com',
            'is_verified' => false,
        ]);

        $resolved = $this->service->resolveFromDomain('unverified.example.com');

        $this->assertNull($resolved);
    }

    public function test_does_not_resolve_inactive_partner(): void
    {
        $partner = Partner::factory()->create(['is_active' => false, 'status' => 'suspended']);
        
        PartnerDomain::factory()->create([
            'partner_id' => $partner->id,
            'domain' => 'inactive.example.com',
            'is_verified' => true,
        ]);

        $resolved = $this->service->resolveFromDomain('inactive.example.com');

        $this->assertNull($resolved);
    }

    public function test_caches_partner_resolution(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $partner->branding()->create();
        $partner->wallet()->create();
        
        PartnerDomain::factory()->create([
            'partner_id' => $partner->id,
            'domain' => 'cached.example.com',
            'is_verified' => true,
        ]);

        // First resolution
        $this->service->resolveFromDomain('cached.example.com');

        // Check cache exists
        $cachedPartner = Cache::get('partner:domain:cached.example.com');
        $this->assertNotNull($cachedPartner);
        $this->assertEquals($partner->id, $cachedPartner->id);
    }

    public function test_can_set_partner_manually(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $partner->branding()->create();
        $partner->wallet()->create();

        $this->service->setPartner($partner);

        $this->assertTrue($this->service->hasPartner());
        $this->assertEquals($partner->id, $this->service->getPartner()->id);
    }

    public function test_loads_branding_with_partner(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $branding = $partner->branding()->create([
            'email_sender_name' => 'Test Brand',
            'primary_color' => '#ff0000',
        ]);

        $this->service->setPartner($partner);

        $this->assertNotNull($this->service->getBranding());
        $this->assertEquals('Test Brand', $this->service->getBranding()->email_sender_name);
        $this->assertEquals('#ff0000', $this->service->getBranding()->primary_color);
    }

    public function test_loads_wallet_with_partner(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $wallet = $partner->wallet()->create();

        $this->service->setPartner($partner);

        $this->assertNotNull($this->service->getWallet());
        $this->assertEquals($wallet->id, $this->service->getWallet()->id);
    }

    public function test_provides_pricing_service(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        
        $this->service->setPartner($partner);

        $pricingService = $this->service->getPricingService();
        
        $this->assertNotNull($pricingService);
        $this->assertInstanceOf(\App\Services\PricingService::class, $pricingService);
    }

    public function test_can_reset_context(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        
        $this->service->setPartner($partner);
        $this->assertTrue($this->service->hasPartner());

        $this->service->reset();
        
        $this->assertFalse($this->service->hasPartner());
        $this->assertFalse($this->service->isResolved());
        $this->assertNull($this->service->getPartner());
    }

    public function test_get_default_partner_returns_first_active(): void
    {
        Partner::factory()->create(['is_active' => false, 'status' => 'suspended']);
        $activePartner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $activePartner->branding()->create();
        $activePartner->wallet()->create();
        Partner::factory()->create(['is_active' => true, 'status' => 'active']);

        $defaultPartner = $this->service->getDefaultPartner();

        $this->assertNotNull($defaultPartner);
        $this->assertEquals($activePartner->id, $defaultPartner->id);
    }

    public function test_resolve_with_fallback_uses_default_when_not_found(): void
    {
        config(['partner.use_default_fallback' => true]);
        
        $defaultPartner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $defaultPartner->branding()->create();
        $defaultPartner->wallet()->create();

        // Mock request with unknown domain
        $this->app->instance('request', $this->app->make('request'));

        $partner = $this->service->resolveWithFallback();

        $this->assertNotNull($partner);
        $this->assertEquals($defaultPartner->id, $partner->id);
    }

    public function test_resolve_with_fallback_returns_null_when_disabled(): void
    {
        config(['partner.use_default_fallback' => false]);

        $partner = $this->service->resolveWithFallback();

        $this->assertNull($partner);
    }

    public function test_singleton_maintains_state_within_request(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        
        $service1 = app(PartnerContextService::class);
        $service1->setPartner($partner);

        $service2 = app(PartnerContextService::class);

        $this->assertSame($service1, $service2);
        $this->assertEquals($partner->id, $service2->getPartner()->id);
    }
}
