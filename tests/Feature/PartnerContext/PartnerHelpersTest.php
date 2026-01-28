<?php

namespace Tests\Feature\PartnerContext;

use App\Models\Partner;
use App\Models\PartnerBranding;
use App\Models\Wallet;
use App\Services\PartnerContextService;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerHelpersTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_context_helper_returns_service(): void
    {
        $service = partnerContext();

        $this->assertInstanceOf(PartnerContextService::class, $service);
    }

    public function test_current_partner_helper_returns_partner(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        
        app(PartnerContextService::class)->setPartner($partner);

        $result = currentPartner();

        $this->assertInstanceOf(Partner::class, $result);
        $this->assertEquals($partner->id, $result->id);
    }

    public function test_current_partner_helper_returns_null_when_no_partner(): void
    {
        $result = currentPartner();

        $this->assertNull($result);
    }

    public function test_partner_branding_helper_returns_branding(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $branding = $partner->branding()->create([
            'email_sender_name' => 'Test Branding',
        ]);
        
        app(PartnerContextService::class)->setPartner($partner);

        $result = partnerBranding();

        $this->assertInstanceOf(PartnerBranding::class, $result);
        $this->assertEquals('Test Branding', $result->email_sender_name);
    }

    public function test_partner_branding_helper_returns_null_when_no_partner(): void
    {
        $result = partnerBranding();

        $this->assertNull($result);
    }

    public function test_partner_wallet_helper_returns_wallet(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $wallet = $partner->wallet()->create();
        
        app(PartnerContextService::class)->setPartner($partner);

        $result = partnerWallet();

        $this->assertInstanceOf(Wallet::class, $result);
        $this->assertEquals($wallet->id, $result->id);
    }

    public function test_partner_wallet_helper_returns_null_when_no_partner(): void
    {
        $result = partnerWallet();

        $this->assertNull($result);
    }

    public function test_partner_pricing_helper_returns_pricing_service(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        
        app(PartnerContextService::class)->setPartner($partner);

        $result = partnerPricing();

        $this->assertInstanceOf(PricingService::class, $result);
    }

    public function test_has_partner_helper_returns_true_when_partner_set(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        
        app(PartnerContextService::class)->setPartner($partner);

        $this->assertTrue(hasPartner());
    }

    public function test_has_partner_helper_returns_false_when_no_partner(): void
    {
        $this->assertFalse(hasPartner());
    }

    public function test_helpers_work_consistently_across_calls(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $partner->branding()->create();
        $partner->wallet()->create();
        
        app(PartnerContextService::class)->setPartner($partner);

        $partner1 = currentPartner();
        $partner2 = currentPartner();

        $this->assertSame($partner1, $partner2);
        $this->assertEquals($partner->id, $partner1->id);
    }
}
