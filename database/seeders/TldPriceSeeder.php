<?php

namespace Database\Seeders;

use App\Enums\PriceAction;
use App\Models\Tld;
use App\Models\TldPrice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TldPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tlds = Tld::all();

        if ($tlds->isEmpty()) {
            $this->command->error('No TLDs found. Please run TldSeeder first.');
            return;
        }

        // Base prices for popular TLDs
        $basePrices = [
            'com' => ['register' => 10.99, 'renew' => 12.99, 'transfer' => 10.99],
            'net' => ['register' => 12.99, 'renew' => 14.99, 'transfer' => 12.99],
            'org' => ['register' => 11.99, 'renew' => 13.99, 'transfer' => 11.99],
            'info' => ['register' => 8.99, 'renew' => 10.99, 'transfer' => 8.99],
            'biz' => ['register' => 9.99, 'renew' => 11.99, 'transfer' => 9.99],
            'io' => ['register' => 39.99, 'renew' => 44.99, 'transfer' => 39.99],
            'co' => ['register' => 19.99, 'renew' => 24.99, 'transfer' => 19.99],
            'dev' => ['register' => 14.99, 'renew' => 16.99, 'transfer' => 14.99],
            'app' => ['register' => 14.99, 'renew' => 16.99, 'transfer' => 14.99],
            'xyz' => ['register' => 1.99, 'renew' => 8.99, 'transfer' => 8.99],
        ];

        $effectiveDate = now()->subMonths(3)->toDateString();
        $count = 0;

        foreach ($tlds as $tld) {
            $prices = $basePrices[$tld->extension] ?? [
                'register' => 15.99,
                'renew' => 17.99,
                'transfer' => 15.99,
            ];

            foreach (PriceAction::cases() as $action) {
                for ($years = $tld->min_years; $years <= min($tld->max_years, 5); $years++) {
                    $basePrice = $prices[$action->value];
                    
                    // Multi-year discount: 2% per additional year
                    $yearlyPrice = $basePrice * $years * (1 - (($years - 1) * 0.02));

                    TldPrice::create([
                        'tld_id' => $tld->id,
                        'action' => $action->value,
                        'years' => $years,
                        'price' => round($yearlyPrice, 2),
                        'effective_date' => $effectiveDate,
                    ]);

                    $count++;
                }
            }
        }

        // Add some historical prices for .com (simulate price changes)
        $comTld = Tld::where('extension', 'com')->first();
        if ($comTld) {
            // Price from 6 months ago
            $oldDate = now()->subMonths(6)->toDateString();
            TldPrice::create([
                'tld_id' => $comTld->id,
                'action' => PriceAction::REGISTER->value,
                'years' => 1,
                'price' => 9.99,
                'effective_date' => $oldDate,
            ]);

            // Future price change (next month)
            $futureDate = now()->addMonth()->toDateString();
            TldPrice::create([
                'tld_id' => $comTld->id,
                'action' => PriceAction::REGISTER->value,
                'years' => 1,
                'price' => 11.99,
                'effective_date' => $futureDate,
            ]);

            $count += 2;
        }

        $this->command->info('Created ' . $count . ' TLD prices');
    }
}
