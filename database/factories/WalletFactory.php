<?php

namespace Database\Factories;

use App\Models\Partner;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'partner_id' => Partner::factory(),
        ];
    }
}
