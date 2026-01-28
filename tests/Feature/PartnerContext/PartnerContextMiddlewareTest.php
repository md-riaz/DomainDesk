<?php

namespace Tests\Feature\PartnerContext;

use App\Models\Partner;
use App\Models\PartnerDomain;
use App\Models\User;
use App\Services\PartnerContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerContextMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_resolves_partner_from_domain(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $partner->branding()->create();
        $partner->wallet()->create();
        
        PartnerDomain::factory()->create([
            'partner_id' => $partner->id,
            'domain' => 'partner.test',
            'is_verified' => true,
        ]);

        $response = $this->get('http://partner.test/login');

        $response->assertStatus(200);
        
        $service = app(PartnerContextService::class);
        $this->assertTrue($service->hasPartner());
        $this->assertEquals($partner->id, $service->getPartner()->id);
    }

    public function test_middleware_skips_admin_routes(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        
        PartnerDomain::factory()->create([
            'partner_id' => $partner->id,
            'domain' => 'partner.test',
            'is_verified' => true,
        ]);

        // Create super admin user
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $response = $this->actingAs($admin)
            ->get('http://partner.test/admin/dashboard');

        $response->assertStatus(200);
        
        // Partner context should not be resolved for admin routes
        $service = app(PartnerContextService::class);
        $this->assertFalse($service->isResolved());
    }

    public function test_middleware_returns_404_for_missing_partner_when_configured(): void
    {
        config(['partner.allow_missing_partner' => false]);
        config(['partner.use_default_fallback' => false]);

        $response = $this->get('http://unknown.test/login');

        $response->assertStatus(404);
    }

    public function test_middleware_allows_missing_partner_when_configured(): void
    {
        config(['partner.allow_missing_partner' => true]);

        $response = $this->get('http://unknown.test/login');

        $response->assertStatus(200);
    }

    public function test_middleware_uses_fallback_partner_when_configured(): void
    {
        config(['partner.use_default_fallback' => true]);
        
        $defaultPartner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $defaultPartner->branding()->create();
        $defaultPartner->wallet()->create();

        $response = $this->get('http://unknown.test/login');

        $response->assertStatus(200);
        
        $service = app(PartnerContextService::class);
        $this->assertTrue($service->hasPartner());
        $this->assertEquals($defaultPartner->id, $service->getPartner()->id);
    }

    public function test_partner_context_applies_to_client_routes(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $partner->branding()->create();
        $partner->wallet()->create();
        
        PartnerDomain::factory()->create([
            'partner_id' => $partner->id,
            'domain' => 'client.test',
            'is_verified' => true,
        ]);

        $client = User::factory()->create([
            'role' => 'client',
            'partner_id' => $partner->id,
        ]);

        $response = $this->actingAs($client)
            ->get('http://client.test/client/dashboard');

        $response->assertStatus(200);
        
        $service = app(PartnerContextService::class);
        $this->assertTrue($service->hasPartner());
        $this->assertEquals($partner->id, $service->getPartner()->id);
    }

    public function test_partner_context_applies_to_partner_routes(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $partner->branding()->create();
        $partner->wallet()->create();
        
        PartnerDomain::factory()->create([
            'partner_id' => $partner->id,
            'domain' => 'mypartner.test',
            'is_verified' => true,
        ]);

        $partnerUser = User::factory()->create([
            'role' => 'partner',
            'partner_id' => $partner->id,
        ]);

        $response = $this->actingAs($partnerUser)
            ->get('http://mypartner.test/partner/dashboard');

        $response->assertStatus(200);
        
        $service = app(PartnerContextService::class);
        $this->assertTrue($service->hasPartner());
        $this->assertEquals($partner->id, $service->getPartner()->id);
    }

    public function test_partner_context_maintained_across_requests_in_test(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $partner->branding()->create();
        $partner->wallet()->create();
        
        PartnerDomain::factory()->create([
            'partner_id' => $partner->id,
            'domain' => 'stable.test',
            'is_verified' => true,
        ]);

        // First request
        $this->get('http://stable.test/login');
        
        $service1 = app(PartnerContextService::class);
        $partnerId1 = $service1->getPartner()->id;

        // Reset for second request (simulating new request in tests)
        $service1->reset();

        // Second request
        $this->get('http://stable.test/login');
        
        $service2 = app(PartnerContextService::class);
        $partnerId2 = $service2->getPartner()->id;

        $this->assertEquals($partnerId1, $partnerId2);
    }
}
