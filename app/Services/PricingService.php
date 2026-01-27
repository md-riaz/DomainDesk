<?php

namespace App\Services;

use App\Enums\PriceAction;
use App\Models\Partner;
use App\Models\PartnerPricingRule;
use App\Models\Tld;

class PricingService
{
    /**
     * Calculate the final price for a domain action
     * 
     * @param Tld|int $tld The TLD model or ID
     * @param Partner|int|null $partner The Partner model or ID (null for base price)
     * @param PriceAction|string $action The pricing action
     * @param int $years The number of years
     * @return string|null The final price as a string (using BC Math), or null if no price exists
     */
    public function calculateFinalPrice(
        Tld|int $tld,
        Partner|int|null $partner,
        PriceAction|string $action,
        int $years
    ): ?string {
        // Resolve TLD if needed
        if (is_int($tld)) {
            $tld = Tld::find($tld);
            if (!$tld) {
                return null;
            }
        }

        // Get base price
        $basePrice = $tld->getBasePrice($action, $years);
        
        if ($basePrice === null) {
            return null;
        }

        // If no partner, return base price
        if ($partner === null) {
            return number_format($basePrice, 2, '.', '');
        }

        // Resolve partner if needed
        if (is_int($partner)) {
            $partner = Partner::find($partner);
            if (!$partner) {
                return number_format($basePrice, 2, '.', '');
            }
        }

        // Find most specific pricing rule
        $rule = PartnerPricingRule::getMostSpecificRule(
            $partner->id,
            $tld->id,
            $years
        );

        // If no rule found, return base price
        if (!$rule) {
            return number_format($basePrice, 2, '.', '');
        }

        // Apply markup using BC Math to avoid floating point errors
        return $rule->applyMarkup($basePrice);
    }

    /**
     * Calculate prices for all available years for a TLD and partner
     * 
     * @return array Indexed by action and years: ['register' => [1 => '10.00', 2 => '18.00'], ...]
     */
    public function calculateAllPrices(
        Tld|int $tld,
        Partner|int|null $partner = null
    ): array {
        // Resolve TLD if needed
        if (is_int($tld)) {
            $tld = Tld::find($tld);
            if (!$tld) {
                return [];
            }
        }

        $prices = [];

        foreach (PriceAction::cases() as $action) {
            for ($years = $tld->min_years; $years <= $tld->max_years; $years++) {
                $price = $this->calculateFinalPrice($tld, $partner, $action, $years);
                
                if ($price !== null) {
                    $prices[$action->value][$years] = $price;
                }
            }
        }

        return $prices;
    }

    /**
     * Calculate the markup amount for a partner
     * 
     * @return string|null The markup amount, or null if no markup
     */
    public function calculateMarkupAmount(
        Tld|int $tld,
        Partner|int $partner,
        PriceAction|string $action,
        int $years
    ): ?string {
        // Resolve TLD if needed
        if (is_int($tld)) {
            $tld = Tld::find($tld);
            if (!$tld) {
                return null;
            }
        }

        // Resolve partner if needed
        if (is_int($partner)) {
            $partner = Partner::find($partner);
            if (!$partner) {
                return null;
            }
        }

        $basePrice = $tld->getBasePrice($action, $years);
        if ($basePrice === null) {
            return null;
        }

        $finalPrice = $this->calculateFinalPrice($tld, $partner, $action, $years);
        if ($finalPrice === null) {
            return null;
        }

        return bcsub($finalPrice, (string)$basePrice, 2);
    }

    /**
     * Get pricing breakdown showing base price, markup, and final price
     * 
     * @return array|null ['base' => '10.00', 'markup' => '2.00', 'final' => '12.00', 'rule' => ...]
     */
    public function getPricingBreakdown(
        Tld|int $tld,
        Partner|int $partner,
        PriceAction|string $action,
        int $years
    ): ?array {
        // Resolve TLD if needed
        if (is_int($tld)) {
            $tld = Tld::find($tld);
            if (!$tld) {
                return null;
            }
        }

        // Resolve partner if needed
        if (is_int($partner)) {
            $partner = Partner::find($partner);
            if (!$partner) {
                return null;
            }
        }

        $basePrice = $tld->getBasePrice($action, $years);
        if ($basePrice === null) {
            return null;
        }

        $basePriceStr = number_format($basePrice, 2, '.', '');

        $rule = PartnerPricingRule::getMostSpecificRule(
            $partner->id,
            $tld->id,
            $years
        );

        if (!$rule) {
            return [
                'base' => $basePriceStr,
                'markup' => '0.00',
                'final' => $basePriceStr,
                'rule' => null,
            ];
        }

        $finalPrice = $rule->applyMarkup($basePrice);
        $markup = bcsub($finalPrice, $basePriceStr, 2);

        return [
            'base' => $basePriceStr,
            'markup' => $markup,
            'final' => $finalPrice,
            'rule' => [
                'id' => $rule->id,
                'type' => $rule->markup_type->value,
                'value' => $rule->markup_value,
                'tld_specific' => $rule->tld_id !== null,
                'duration_specific' => $rule->duration !== null,
            ],
        ];
    }
}
