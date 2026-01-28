<?php

namespace Tests\Feature\Livewire\Admin;

use App\Enums\PriceAction;
use App\Livewire\Admin\Tld\TldPricing;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\TldPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TldPricingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Registrar $registrar;
    protected Tld $tld;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->registrar = Registrar::factory()->create();
        $this->tld = Tld::factory()->create([
            'registrar_id' => $this->registrar->id,
            'extension' => 'com',
            'min_years' => 1,
            'max_years' => 10,
        ]);
    }

    public function test_component_renders_successfully()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldPricing::class, ['tldId' => $this->tld->id])
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.tld.tld-pricing');
    }

    public function test_displays_tld_information()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldPricing::class, ['tldId' => $this->tld->id])
            ->assertSee('.com')
            ->assertSee($this->registrar->name);
    }

    public function test_displays_current_prices()
    {
        TldPrice::factory()->create([
            'tld_id' => $this->tld->id,
            'action' => 'register',
            'years' => 1,
            'price' => 12.99,
            'effective_date' => now()->subDay(),
        ]);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldPricing::class, ['tldId' => $this->tld->id])
            ->assertSee('12.99');
    }

    public function test_can_save_manual_price()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldPricing::class, ['tldId' => $this->tld->id])
            ->set('selectedAction', 'register')
            ->set('selectedYears', 1)
            ->set('newPrice', '15.99')
            ->set('effectiveDate', now()->toDateString())
            ->set('notes', 'Manual price adjustment')
            ->call('saveManualPrice')
            ->assertDispatched('price-updated');
        
        $this->assertDatabaseHas('tld_prices', [
            'tld_id' => $this->tld->id,
            'action' => 'register',
            'years' => 1,
            'price' => '15.99',
        ]);
    }

    public function test_manual_price_validation_requires_price()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldPricing::class, ['tldId' => $this->tld->id])
            ->set('newPrice', '')
            ->set('effectiveDate', now()->toDateString())
            ->call('saveManualPrice')
            ->assertHasErrors(['newPrice' => 'required']);
    }

    public function test_manual_price_validation_requires_positive_price()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldPricing::class, ['tldId' => $this->tld->id])
            ->set('newPrice', '-5.00')
            ->set('effectiveDate', now()->toDateString())
            ->call('saveManualPrice')
            ->assertHasErrors(['newPrice' => 'min']);
    }

    public function test_manual_price_validation_requires_effective_date()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldPricing::class, ['tldId' => $this->tld->id])
            ->set('newPrice', '15.99')
            ->set('effectiveDate', '')
            ->call('saveManualPrice')
            ->assertHasErrors(['effectiveDate' => 'required']);
    }

    public function test_manual_price_validation_effective_date_not_too_far_future()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldPricing::class, ['tldId' => $this->tld->id])
            ->set('newPrice', '15.99')
            ->set('effectiveDate', now()->addDays(31)->toDateString())
            ->call('saveManualPrice')
            ->assertHasErrors(['effectiveDate']);
    }

    public function test_can_toggle_price_history()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldPricing::class, ['tldId' => $this->tld->id])
            ->call('toggleHistory', 'register', 1)
            ->assertSet('showHistory', true)
            ->assertSet('historyAction', 'register')
            ->assertSet('historyYears', 1);
    }

    public function test_displays_price_history()
    {
        TldPrice::factory()->create([
            'tld_id' => $this->tld->id,
            'action' => 'register',
            'years' => 1,
            'price' => 10.00,
            'effective_date' => now()->subMonths(2),
        ]);
        
        TldPrice::factory()->create([
            'tld_id' => $this->tld->id,
            'action' => 'register',
            'years' => 1,
            'price' => 12.00,
            'effective_date' => now()->subMonth(),
        ]);
        
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldPricing::class, ['tldId' => $this->tld->id])
            ->set('showHistory', true)
            ->set('historyAction', 'register')
            ->set('historyYears', 1)
            ->assertSee('10.00')
            ->assertSee('12.00');
    }

    public function test_sync_prices_handles_no_registrar()
    {
        $this->markTestSkipped('Registrar ID is required by database constraint');
    }

    public function test_can_toggle_manual_override_form()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldPricing::class, ['tldId' => $this->tld->id])
            ->assertSet('showManualOverride', false)
            ->set('showManualOverride', true)
            ->assertSet('showManualOverride', true);
    }

    public function test_price_action_options_displayed()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldPricing::class, ['tldId' => $this->tld->id])
            ->assertViewHas('actions', function ($actions) {
                return count($actions) === count(PriceAction::cases());
            });
    }

    public function test_displays_all_year_options()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldPricing::class, ['tldId' => $this->tld->id])
            ->assertSee('1')
            ->assertSee('10');
    }

    public function test_price_change_calculation()
    {
        TldPrice::factory()->create([
            'tld_id' => $this->tld->id,
            'action' => 'register',
            'years' => 1,
            'price' => 10.00,
            'effective_date' => now()->subMonth(),
        ]);
        
        $newPrice = TldPrice::factory()->create([
            'tld_id' => $this->tld->id,
            'action' => 'register',
            'years' => 1,
            'price' => 12.00,
            'effective_date' => now(),
        ]);
        
        $change = $newPrice->getPriceChange();
        
        $this->assertEquals(20.0, $change);
    }

    public function test_closes_manual_override_after_save()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(TldPricing::class, ['tldId' => $this->tld->id])
            ->set('showManualOverride', true)
            ->set('selectedAction', 'register')
            ->set('selectedYears', 1)
            ->set('newPrice', '15.99')
            ->set('effectiveDate', now()->toDateString())
            ->call('saveManualPrice')
            ->assertSet('showManualOverride', false);
    }

    public function test_unauthorized_user_cannot_access()
    {
        $partnerUser = User::factory()->partner()->create();
        
        $this->actingAs($partnerUser);
        
        $response = $this->get(route('admin.tlds.pricing', $this->tld->id));
        
        $response->assertForbidden();
    }

    public function test_guest_cannot_access()
    {
        $response = $this->get(route('admin.tlds.pricing', $this->tld->id));
        
        $response->assertRedirect(route('login'));
    }

    public function test_handles_missing_tld()
    {
        $this->actingAs($this->superAdmin);
        
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        
        Livewire::test(TldPricing::class, ['tldId' => 99999]);
    }
}
