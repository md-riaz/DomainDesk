<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 5000);
        $tax = $subtotal * 0.1; // 10% tax
        
        return [
            'invoice_number' => null, // Will be auto-generated
            'partner_id' => Partner::factory(),
            'client_id' => User::factory(),
            'status' => InvoiceStatus::Draft,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $subtotal + $tax,
            'issued_at' => null,
            'paid_at' => null,
            'due_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Draft,
            'issued_at' => null,
            'paid_at' => null,
            'due_at' => null,
        ]);
    }

    public function issued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Issued,
            'issued_at' => now()->subDays(5),
            'due_at' => now()->addDays(25),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Paid,
            'issued_at' => now()->subDays(10),
            'paid_at' => now()->subDays(3),
            'due_at' => now()->addDays(20),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Issued,
            'issued_at' => now()->subDays(40),
            'due_at' => now()->subDays(10),
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Refunded,
            'issued_at' => now()->subDays(20),
            'paid_at' => now()->subDays(15),
            'due_at' => now()->subDays(10),
        ]);
    }
}
