<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Partner;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    public function definition(): array
    {
        $wallet = Wallet::factory()->create();
        
        return [
            'wallet_id' => $wallet->id,
            'partner_id' => $wallet->partner_id,
            'type' => fake()->randomElement([
                TransactionType::Credit,
                TransactionType::Debit,
                TransactionType::Refund,
                TransactionType::Adjustment,
            ]),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'description' => fake()->sentence(),
            'reference_type' => null,
            'reference_id' => null,
            'created_by' => null,
        ];
    }

    public function credit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Credit,
        ]);
    }

    public function debit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Debit,
        ]);
    }

    public function refund(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Refund,
        ]);
    }

    public function adjustment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Adjustment,
        ]);
    }

    public function withCreator(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => User::factory(),
        ]);
    }
}
