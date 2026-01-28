<?php

namespace Tests\Feature\PartnerContext;

use App\Livewire\Concerns\HasPartnerContext;
use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Tests\TestCase;

class TestComponentWithContext extends Component
{
    use HasPartnerContext;

    public function render()
    {
        return view('livewire.auth.login');
    }
}

class LivewirePartnerContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_livewire_component_can_access_partner_via_trait(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $partner->branding()->create();
        $partner->wallet()->create();
        
        app(\App\Services\PartnerContextService::class)->setPartner($partner);

        $component = new TestComponentWithContext();

        $this->assertNotNull($component->partner());
        $this->assertEquals($partner->id, $component->partner()->id);
    }

    public function test_livewire_component_can_access_branding_via_trait(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $branding = $partner->branding()->create([
            'email_sender_name' => 'Component Test',
        ]);
        
        app(\App\Services\PartnerContextService::class)->setPartner($partner);

        $component = new TestComponentWithContext();

        $this->assertNotNull($component->branding());
        $this->assertEquals('Component Test', $component->branding()->email_sender_name);
    }

    public function test_livewire_component_can_access_wallet_via_trait(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $wallet = $partner->wallet()->create();
        
        app(\App\Services\PartnerContextService::class)->setPartner($partner);

        $component = new TestComponentWithContext();

        $this->assertNotNull($component->wallet());
        $this->assertEquals($wallet->id, $component->wallet()->id);
    }

    public function test_livewire_component_can_access_pricing_service_via_trait(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        
        app(\App\Services\PartnerContextService::class)->setPartner($partner);

        $component = new TestComponentWithContext();

        $this->assertNotNull($component->pricing());
        $this->assertInstanceOf(\App\Services\PricingService::class, $component->pricing());
    }

    public function test_livewire_component_has_partner_method(): void
    {
        $component = new TestComponentWithContext();
        $this->assertFalse($component->hasPartner());

        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        app(\App\Services\PartnerContextService::class)->setPartner($partner);

        $this->assertTrue($component->hasPartner());
    }
}
