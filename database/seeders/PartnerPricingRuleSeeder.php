<?php

namespace Database\Seeders;

use App\Enums\MarkupType;
use App\Models\Partner;
use App\Models\PartnerPricingRule;
use App\Models\Tld;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PartnerPricingRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $partners = Partner::all();

        if ($partners->isEmpty()) {
            // Create a test partner for demonstration
            $partner = Partner::create([
                'name' => 'Demo Partner',
                'email' => 'demo@partner.com',
                'slug' => 'demo-partner',
                'status' => 'active',
                'is_active' => true,
            ]);
            $partners = collect([$partner]);
            $this->command->info('Created demo partner for pricing rules.');
        }

        $tlds = Tld::all();
        $comTld = $tlds->where('extension', 'com')->first();
        $ioTld = $tlds->where('extension', 'io')->first();

        $count = 0;

        foreach ($partners as $partner) {
            // Global percentage markup: 20%
            PartnerPricingRule::create([
                'partner_id' => $partner->id,
                'tld_id' => null,
                'markup_type' => MarkupType::PERCENTAGE,
                'markup_value' => 20.00,
                'duration' => null,
                'is_active' => true,
            ]);
            $count++;

            // Special markup for .com TLD: 15% (lower than global)
            if ($comTld) {
                PartnerPricingRule::create([
                    'partner_id' => $partner->id,
                    'tld_id' => $comTld->id,
                    'markup_type' => MarkupType::PERCENTAGE,
                    'markup_value' => 15.00,
                    'duration' => null,
                    'is_active' => true,
                ]);
                $count++;

                // Special fixed markup for .com 1-year: $2.00
                PartnerPricingRule::create([
                    'partner_id' => $partner->id,
                    'tld_id' => $comTld->id,
                    'markup_type' => MarkupType::FIXED,
                    'markup_value' => 2.00,
                    'duration' => 1,
                    'is_active' => true,
                ]);
                $count++;
            }

            // Premium TLD (.io) - higher markup: 30%
            if ($ioTld) {
                PartnerPricingRule::create([
                    'partner_id' => $partner->id,
                    'tld_id' => $ioTld->id,
                    'markup_type' => MarkupType::PERCENTAGE,
                    'markup_value' => 30.00,
                    'duration' => null,
                    'is_active' => true,
                ]);
                $count++;
            }

            // Duration-specific markup for 3+ years: 25% (encourage longer terms)
            PartnerPricingRule::create([
                'partner_id' => $partner->id,
                'tld_id' => null,
                'markup_type' => MarkupType::PERCENTAGE,
                'markup_value' => 25.00,
                'duration' => 3,
                'is_active' => true,
            ]);
            $count++;
        }

        $this->command->info('Created ' . $count . ' pricing rules');
    }
}
