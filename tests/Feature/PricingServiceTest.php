<?php

namespace Tests\Feature;

use App\Enums\MarkupType;
use App\Enums\PriceAction;
use App\Models\Partner;
use App\Models\PartnerPricingRule;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\TldPrice;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PricingService $pricingService;
    private Registrar $registrar;
    private Tld $tld;
    private Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->pricingService = new PricingService();
        
        // Create test registrar
        $this->registrar = Registrar::create([
            'name' => 'Test Registrar',
            'slug' => 'test-registrar',
            'api_class' => 'App\Services\Registrars\TestRegistrar',
            'is_active' => true,
            'is_default' => true,
        ]);

        // Create test TLD
        $this->tld = Tld::create([
            'registrar_id' => $this->registrar->id,
            'extension' => 'com',
            'min_years' => 1,
            'max_years' => 10,
            'supports_dns' => true,
            'supports_whois_privacy' => true,
            'is_active' => true,
        ]);

        // Create test partner
        $this->partner = Partner::create([
            'name' => 'Test Partner',
            'email' => 'partner@test.com',
            'slug' => 'test-partner',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    public function test_base_price_calculation_without_partner()
    {
        // Create base price
        TldPrice::create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 1,
            'price' => 10.00,
            'effective_date' => now()->subDay(),
        ]);

        $price = $this->pricingService->calculateFinalPrice(
            $this->tld,
            null,
            PriceAction::REGISTER,
            1
        );

        $this->assertEquals('10.00', $price);
    }

    public function test_fixed_markup_calculation()
    {
        // Create base price
        TldPrice::create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 1,
            'price' => 10.00,
            'effective_date' => now()->subDay(),
        ]);

        // Create fixed markup rule
        PartnerPricingRule::create([
            'partner_id' => $this->partner->id,
            'tld_id' => null,
            'markup_type' => MarkupType::FIXED,
            'markup_value' => 2.50,
            'is_active' => true,
        ]);

        $price = $this->pricingService->calculateFinalPrice(
            $this->tld,
            $this->partner,
            PriceAction::REGISTER,
            1
        );

        $this->assertEquals('12.50', $price);
    }

    public function test_percentage_markup_calculation()
    {
        // Create base price
        TldPrice::create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 1,
            'price' => 10.00,
            'effective_date' => now()->subDay(),
        ]);

        // Create percentage markup rule: 20%
        PartnerPricingRule::create([
            'partner_id' => $this->partner->id,
            'tld_id' => null,
            'markup_type' => MarkupType::PERCENTAGE,
            'markup_value' => 20.00,
            'is_active' => true,
        ]);

        $price = $this->pricingService->calculateFinalPrice(
            $this->tld,
            $this->partner,
            PriceAction::REGISTER,
            1
        );

        $this->assertEquals('12.00', $price);
    }

    public function test_tld_specific_rule_overrides_global()
    {
        // Create base price
        TldPrice::create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 1,
            'price' => 10.00,
            'effective_date' => now()->subDay(),
        ]);

        // Create global rule: 20%
        PartnerPricingRule::create([
            'partner_id' => $this->partner->id,
            'tld_id' => null,
            'markup_type' => MarkupType::PERCENTAGE,
            'markup_value' => 20.00,
            'is_active' => true,
        ]);

        // Create TLD-specific rule: 10%
        PartnerPricingRule::create([
            'partner_id' => $this->partner->id,
            'tld_id' => $this->tld->id,
            'markup_type' => MarkupType::PERCENTAGE,
            'markup_value' => 10.00,
            'is_active' => true,
        ]);

        $price = $this->pricingService->calculateFinalPrice(
            $this->tld,
            $this->partner,
            PriceAction::REGISTER,
            1
        );

        // Should use TLD-specific 10% markup
        $this->assertEquals('11.00', $price);
    }

    public function test_duration_specific_rule_overrides_general()
    {
        // Create base price
        TldPrice::create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 1,
            'price' => 10.00,
            'effective_date' => now()->subDay(),
        ]);

        TldPrice::create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 3,
            'price' => 28.00,
            'effective_date' => now()->subDay(),
        ]);

        // Create TLD-specific general rule: 10%
        PartnerPricingRule::create([
            'partner_id' => $this->partner->id,
            'tld_id' => $this->tld->id,
            'markup_type' => MarkupType::PERCENTAGE,
            'markup_value' => 10.00,
            'duration' => null,
            'is_active' => true,
        ]);

        // Create TLD-specific duration rule: 5% for 3 years
        PartnerPricingRule::create([
            'partner_id' => $this->partner->id,
            'tld_id' => $this->tld->id,
            'markup_type' => MarkupType::PERCENTAGE,
            'markup_value' => 5.00,
            'duration' => 3,
            'is_active' => true,
        ]);

        $price1y = $this->pricingService->calculateFinalPrice(
            $this->tld,
            $this->partner,
            PriceAction::REGISTER,
            1
        );

        $price3y = $this->pricingService->calculateFinalPrice(
            $this->tld,
            $this->partner,
            PriceAction::REGISTER,
            3
        );

        // 1 year should use 10% markup
        $this->assertEquals('11.00', $price1y);
        
        // 3 years should use 5% markup
        $this->assertEquals('29.40', $price3y);
    }

    public function test_historical_price_tracking()
    {
        // Old price
        TldPrice::create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 1,
            'price' => 9.00,
            'effective_date' => now()->subMonths(6),
        ]);

        // Current price
        TldPrice::create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 1,
            'price' => 10.00,
            'effective_date' => now()->subDay(),
        ]);

        // Future price (should not be used yet)
        TldPrice::create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 1,
            'price' => 11.00,
            'effective_date' => now()->addDay(),
        ]);

        $price = $this->pricingService->calculateFinalPrice(
            $this->tld,
            null,
            PriceAction::REGISTER,
            1
        );

        // Should return current price, not old or future
        $this->assertEquals('10.00', $price);
    }

    public function test_pricing_with_bc_math_precision()
    {
        // Create base price with decimals
        TldPrice::create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 1,
            'price' => 10.33,
            'effective_date' => now()->subDay(),
        ]);

        // Create percentage markup that could cause rounding errors
        PartnerPricingRule::create([
            'partner_id' => $this->partner->id,
            'tld_id' => null,
            'markup_type' => MarkupType::PERCENTAGE,
            'markup_value' => 17.50,
            'is_active' => true,
        ]);

        $price = $this->pricingService->calculateFinalPrice(
            $this->tld,
            $this->partner,
            PriceAction::REGISTER,
            1
        );

        // 10.33 * 1.175 = 12.13775, should round to 12.14
        $this->assertEquals('12.14', $price);
    }

    public function test_calculate_all_prices()
    {
        // Create prices for multiple years
        for ($years = 1; $years <= 3; $years++) {
            foreach (PriceAction::cases() as $action) {
                TldPrice::create([
                    'tld_id' => $this->tld->id,
                    'action' => $action,
                    'years' => $years,
                    'price' => 10.00 * $years,
                    'effective_date' => now()->subDay(),
                ]);
            }
        }

        $prices = $this->pricingService->calculateAllPrices($this->tld);

        $this->assertIsArray($prices);
        $this->assertArrayHasKey('register', $prices);
        $this->assertArrayHasKey('renew', $prices);
        $this->assertArrayHasKey('transfer', $prices);
        
        $this->assertEquals('10.00', $prices['register'][1]);
        $this->assertEquals('20.00', $prices['register'][2]);
        $this->assertEquals('30.00', $prices['register'][3]);
    }

    public function test_pricing_breakdown()
    {
        TldPrice::create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 1,
            'price' => 10.00,
            'effective_date' => now()->subDay(),
        ]);

        PartnerPricingRule::create([
            'partner_id' => $this->partner->id,
            'tld_id' => null,
            'markup_type' => MarkupType::FIXED,
            'markup_value' => 3.00,
            'is_active' => true,
        ]);

        $breakdown = $this->pricingService->getPricingBreakdown(
            $this->tld,
            $this->partner,
            PriceAction::REGISTER,
            1
        );

        $this->assertIsArray($breakdown);
        $this->assertEquals('10.00', $breakdown['base']);
        $this->assertEquals('3.00', $breakdown['markup']);
        $this->assertEquals('13.00', $breakdown['final']);
        $this->assertIsArray($breakdown['rule']);
        $this->assertEquals('fixed', $breakdown['rule']['type']);
    }

    public function test_inactive_rules_are_ignored()
    {
        TldPrice::create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 1,
            'price' => 10.00,
            'effective_date' => now()->subDay(),
        ]);

        // Create inactive rule
        PartnerPricingRule::create([
            'partner_id' => $this->partner->id,
            'tld_id' => null,
            'markup_type' => MarkupType::FIXED,
            'markup_value' => 5.00,
            'is_active' => false,
        ]);

        $price = $this->pricingService->calculateFinalPrice(
            $this->tld,
            $this->partner,
            PriceAction::REGISTER,
            1
        );

        // Should return base price since rule is inactive
        $this->assertEquals('10.00', $price);
    }

    public function test_no_price_returns_null()
    {
        $price = $this->pricingService->calculateFinalPrice(
            $this->tld,
            null,
            PriceAction::REGISTER,
            1
        );

        $this->assertNull($price);
    }
}
