<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 10, 500);
        
        return [
            'invoice_id' => Invoice::factory(),
            'description' => fake()->sentence(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $quantity * $unitPrice,
            'reference_type' => null,
            'reference_id' => null,
        ];
    }

    public function domainRegistration(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => 'Domain Registration - ' . fake()->domainName(),
            'quantity' => 1,
            'unit_price' => fake()->randomFloat(2, 10, 50),
        ]);
    }

    public function domainRenewal(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => 'Domain Renewal - ' . fake()->domainName(),
            'quantity' => 1,
            'unit_price' => fake()->randomFloat(2, 10, 50),
        ]);
    }
}
