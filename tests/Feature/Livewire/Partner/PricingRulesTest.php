<?php

namespace Tests\Feature\Livewire\Partner;

use App\Enums\MarkupType;
use App\Enums\PriceAction;
use App\Livewire\Partner\Pricing\PricingRules;
use App\Models\Partner;
use App\Models\PartnerPricingRule;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\TldPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PricingRulesTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $partner;
    protected User $partnerUser;
    protected Registrar $registrar;
    protected Tld $tld;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = Partner::factory()->create();
        $this->partnerUser = User::factory()->partner()->create([
            'partner_id' => $this->partner->id,
        ]);

        $this->registrar = Registrar::factory()->create();
        $this->tld = Tld::create([
            'registrar_id' => $this->registrar->id,
            'extension' => 'com',
            'min_years' => 1,
            'max_years' => 10,
            'is_active' => true,
        ]);

        TldPrice::create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 1,
            'price' => 10.00,
            'effective_date' => now()->subDay(),
        ]);
        
        // Set partner context for tests
        partnerContext()->setPartner($this->partner);
    }

    public function test_component_renders(): void
    {
        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->assertOk();
    }

    public function test_displays_tlds_with_base_prices(): void
    {
        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->assertSee('.com')
            ->assertSee('$10.00');
    }

    public function test_can_search_tlds(): void
    {
        Tld::create([
            'registrar_id' => $this->registrar->id,
            'extension' => 'net',
            'min_years' => 1,
            'max_years' => 10,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->set('search', 'com')
            ->assertSee('.com');
    }

    public function test_can_filter_tlds_with_rules(): void
    {
        PartnerPricingRule::create([
            'partner_id' => $this->partner->id,
            'tld_id' => $this->tld->id,
            'markup_type' => MarkupType::PERCENTAGE,
            'markup_value' => 20,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->set('filter', 'with_rules')
            ->assertSee('.com');
    }

    public function test_can_edit_pricing_rule(): void
    {
        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->call('editRule', $this->tld->id)
            ->assertSet("editingRules.{$this->tld->id}.markup_type", 'percentage');
    }

    public function test_can_save_pricing_rule(): void
    {
        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->call('editRule', $this->tld->id)
            ->set("editingRules.{$this->tld->id}.markup_type", 'percentage')
            ->set("editingRules.{$this->tld->id}.markup_value", 25)
            ->call('saveRule', $this->tld->id)
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertDatabaseHas('partner_pricing_rules', [
            'partner_id' => $this->partner->id,
            'tld_id' => $this->tld->id,
            'markup_type' => 'percentage',
            'markup_value' => 25,
        ]);
    }

    public function test_validates_markup_value(): void
    {
        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->call('editRule', $this->tld->id)
            ->set("editingRules.{$this->tld->id}.markup_value", -5)
            ->call('saveRule', $this->tld->id)
            ->assertHasErrors(["editingRules.{$this->tld->id}.markup_value"]);
    }

    public function test_validates_markup_value_max(): void
    {
        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->call('editRule', $this->tld->id)
            ->set("editingRules.{$this->tld->id}.markup_value", 1500)
            ->call('saveRule', $this->tld->id)
            ->assertHasErrors(["editingRules.{$this->tld->id}.markup_value"]);
    }

    public function test_can_cancel_edit(): void
    {
        $component = Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->call('editRule', $this->tld->id)
            ->set("editingRules.{$this->tld->id}.markup_value", 25)
            ->call('cancelEdit', $this->tld->id);
        
        $editingRules = $component->get('editingRules');
        $this->assertArrayNotHasKey($this->tld->id, $editingRules);
    }

    public function test_can_reset_pricing_rule(): void
    {
        $rule = PartnerPricingRule::create([
            'partner_id' => $this->partner->id,
            'tld_id' => $this->tld->id,
            'markup_type' => MarkupType::PERCENTAGE,
            'markup_value' => 20,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->call('resetRule', $this->tld->id)
            ->assertOk();

        $this->assertDatabaseMissing('partner_pricing_rules', [
            'id' => $rule->id,
        ]);
    }

    public function test_can_toggle_bulk_form(): void
    {
        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->assertSet('showBulkForm', false)
            ->call('toggleBulkForm')
            ->assertSet('showBulkForm', true)
            ->call('toggleBulkForm')
            ->assertSet('showBulkForm', false);
    }

    public function test_can_apply_bulk_markup(): void
    {
        $tld2 = Tld::create([
            'registrar_id' => $this->registrar->id,
            'extension' => 'net',
            'min_years' => 1,
            'max_years' => 10,
            'is_active' => true,
        ]);

        TldPrice::create([
            'tld_id' => $tld2->id,
            'action' => PriceAction::REGISTER,
            'years' => 1,
            'price' => 12.00,
            'effective_date' => now()->subDay(),
        ]);

        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->set('selectedTlds', [$this->tld->id, $tld2->id])
            ->set('bulkMarkupType', 'percentage')
            ->set('bulkMarkupValue', 30)
            ->call('applyBulkMarkup')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertDatabaseHas('partner_pricing_rules', [
            'partner_id' => $this->partner->id,
            'tld_id' => $this->tld->id,
            'markup_value' => 30,
        ]);

        $this->assertDatabaseHas('partner_pricing_rules', [
            'partner_id' => $this->partner->id,
            'tld_id' => $tld2->id,
            'markup_value' => 30,
        ]);
    }

    public function test_bulk_markup_requires_selected_tlds(): void
    {
        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->set('bulkMarkupType', 'percentage')
            ->set('bulkMarkupValue', 20)
            ->call('applyBulkMarkup');

        // Verify no rules were created since no TLDs selected
        $this->assertDatabaseMissing('partner_pricing_rules', [
            'partner_id' => $this->partner->id,
        ]);
    }

    public function test_can_apply_template_add_20_percent(): void
    {
        $component = Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->call('applyTemplate', 'add_20_percent')
            ->assertSet('bulkMarkupType', 'percentage')
            ->assertSet('bulkMarkupValue', '20')
            ->assertSet('showBulkForm', true);

        $this->assertNotEmpty($component->get('selectedTlds'));
    }

    public function test_can_apply_template_add_5_dollars(): void
    {
        $component = Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->call('applyTemplate', 'add_5_dollars')
            ->assertSet('bulkMarkupType', 'fixed')
            ->assertSet('bulkMarkupValue', '5')
            ->assertSet('showBulkForm', true);

        $this->assertNotEmpty($component->get('selectedTlds'));
    }

    public function test_can_apply_template_premium_50_percent(): void
    {
        $component = Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->call('applyTemplate', 'premium_50_percent')
            ->assertSet('bulkMarkupType', 'percentage')
            ->assertSet('bulkMarkupValue', '50')
            ->assertSet('showBulkForm', true);

        $this->assertNotEmpty($component->get('selectedTlds'));
    }

    public function test_can_clear_all_rules(): void
    {
        PartnerPricingRule::create([
            'partner_id' => $this->partner->id,
            'tld_id' => $this->tld->id,
            'markup_type' => MarkupType::PERCENTAGE,
            'markup_value' => 20,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->call('clearAllRules')
            ->assertOk();

        $this->assertDatabaseMissing('partner_pricing_rules', [
            'partner_id' => $this->partner->id,
        ]);
    }

    public function test_can_calculate_price_preview(): void
    {
        PartnerPricingRule::create([
            'partner_id' => $this->partner->id,
            'tld_id' => $this->tld->id,
            'markup_type' => MarkupType::PERCENTAGE,
            'markup_value' => 20,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->set('previewDomain', 'example.com')
            ->call('calculatePreview')
            ->assertSet('showPreview', true)
            ->assertHasNoErrors();
    }

    public function test_validates_preview_domain(): void
    {
        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->set('previewDomain', '')
            ->call('calculatePreview')
            ->assertHasErrors(['previewDomain']);
    }

    public function test_can_close_preview(): void
    {
        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->set('showPreview', true)
            ->set('previewDomain', 'example.com')
            ->call('closePreview')
            ->assertSet('showPreview', false)
            ->assertSet('previewDomain', '');
    }

    public function test_displays_final_price_with_markup(): void
    {
        PartnerPricingRule::create([
            'partner_id' => $this->partner->id,
            'tld_id' => $this->tld->id,
            'markup_type' => MarkupType::FIXED,
            'markup_value' => 5,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->assertSee('$15.00'); // Base $10 + $5 markup
    }

    public function test_updates_existing_pricing_rule(): void
    {
        $rule = PartnerPricingRule::create([
            'partner_id' => $this->partner->id,
            'tld_id' => $this->tld->id,
            'markup_type' => MarkupType::PERCENTAGE,
            'markup_value' => 20,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->call('editRule', $this->tld->id)
            ->set("editingRules.{$this->tld->id}.markup_value", 30)
            ->call('saveRule', $this->tld->id);

        $this->assertEquals(30, $rule->fresh()->markup_value);
    }

    public function test_pagination_works(): void
    {
        // Create 25 TLDs to test pagination (default is 20 per page)
        for ($i = 1; $i <= 25; $i++) {
            Tld::create([
                'registrar_id' => $this->registrar->id,
                'extension' => "test{$i}",
                'min_years' => 1,
                'max_years' => 10,
                'is_active' => true,
            ]);
        }

        $component = Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class);

        $tlds = $component->get('tlds');
        $this->assertLessThanOrEqual(20, $tlds->count());
    }

    public function test_only_shows_active_tlds(): void
    {
        $inactiveTld = Tld::create([
            'registrar_id' => $this->registrar->id,
            'extension' => 'inactive',
            'min_years' => 1,
            'max_years' => 10,
            'is_active' => false,
        ]);

        Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class)
            ->assertDontSee('.inactive');
    }

    public function test_only_partner_can_see_their_rules(): void
    {
        $otherPartner = Partner::factory()->create();
        PartnerPricingRule::create([
            'partner_id' => $otherPartner->id,
            'tld_id' => $this->tld->id,
            'markup_type' => MarkupType::PERCENTAGE,
            'markup_value' => 50,
            'is_active' => true,
        ]);

        $component = Livewire::actingAs($this->partnerUser)
            ->test(PricingRules::class);

        $tlds = $component->get('tlds');
        $comTld = $tlds->firstWhere('extension', 'com');
        $this->assertNull($comTld->current_rule);
    }
}
