<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\Role;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Partner;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_billing_workflow()
    {
        // 1. Setup: Create partner and wallet
        $partner = Partner::factory()->create(['name' => 'Test Partner']);
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);
        
        // 2. Partner adds funds to wallet
        $wallet->credit(1000.00, 'Initial deposit');
        $this->assertEquals(1000.00, $wallet->fresh()->balance);

        // 3. Create client
        $client = User::factory()->create([
            'partner_id' => $partner->id,
            'role' => Role::Client,
            'name' => 'Test Client',
        ]);

        // 4. Create draft invoice
        $invoice = Invoice::factory()->draft()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
        ]);

        // 5. Add items to invoice
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Domain Registration - example.com',
            'quantity' => 1,
            'unit_price' => 15.99,
            'total' => 15.99,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Domain Renewal - oldsite.com',
            'quantity' => 1,
            'unit_price' => 12.99,
            'total' => 12.99,
        ]);

        // 6. Calculate totals
        $invoice->calculateTotals();
        $invoice->tax = $invoice->subtotal * 0.1; // 10% tax
        $invoice->total = $invoice->subtotal + $invoice->tax;
        $invoice->save();

        $this->assertEquals(28.98, $invoice->fresh()->subtotal);
        $this->assertEquals(2.90, $invoice->fresh()->tax);
        $this->assertEquals(31.88, $invoice->fresh()->total);

        // 7. Issue invoice
        $invoice->issue();
        $this->assertEquals(InvoiceStatus::Issued, $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->issued_at);

        // 8. Client pays invoice (deducted from partner wallet)
        $invoice->fresh()->markAsPaid($client->id);
        
        $this->assertEquals(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_at);
        $this->assertEquals(968.12, $wallet->fresh()->balance); // 1000 - 31.88

        // 9. Verify transaction was recorded
        $transaction = $wallet->fresh()->transactions()
            ->where('reference_type', Invoice::class)
            ->where('reference_id', $invoice->id)
            ->first();
        
        $this->assertNotNull($transaction);
        $this->assertEquals('debit', $transaction->type->value);
        $this->assertEquals(31.88, $transaction->amount);

        // 10. Refund the invoice
        $invoice->fresh()->refund($client->id);
        
        $this->assertEquals(InvoiceStatus::Refunded, $invoice->fresh()->status);
        $this->assertEquals(1000.00, $wallet->fresh()->balance); // Back to original

        // 11. Verify refund transaction
        $refundTransaction = $wallet->fresh()->transactions()
            ->where('type', 'refund')
            ->where('reference_type', Invoice::class)
            ->where('reference_id', $invoice->id)
            ->first();
        
        $this->assertNotNull($refundTransaction);
        $this->assertEquals(31.88, $refundTransaction->amount);

        // 12. Verify all transactions are recorded
        $this->assertCount(3, $wallet->fresh()->transactions); // credit, debit, refund
    }

    public function test_financial_integrity_with_multiple_invoices()
    {
        // Setup partner with wallet
        $partner = Partner::factory()->create();
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);
        $wallet->credit(5000.00, 'Initial balance');

        $client = User::factory()->create([
            'partner_id' => $partner->id,
            'role' => Role::Client,
        ]);

        // Create and pay multiple invoices
        $invoice1 = Invoice::factory()->issued()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'total' => 100.00,
        ]);
        $invoice1->markAsPaid();

        $invoice2 = Invoice::factory()->issued()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'total' => 250.00,
        ]);
        $invoice2->markAsPaid();

        $invoice3 = Invoice::factory()->issued()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'total' => 75.00,
        ]);
        $invoice3->markAsPaid();

        // Refund one invoice
        $invoice2->fresh()->refund();

        // Verify balance: 5000 - 100 - 250 - 75 + 250 = 4825
        $this->assertEquals(4825.00, $wallet->fresh()->balance);

        // Verify transaction count: 1 initial + 3 payments + 1 refund = 5
        $this->assertCount(5, $wallet->fresh()->transactions);
    }

    public function test_invoice_cannot_be_paid_without_sufficient_balance()
    {
        $partner = Partner::factory()->create();
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);
        $wallet->credit(50.00, 'Small balance');

        $client = User::factory()->create([
            'partner_id' => $partner->id,
            'role' => Role::Client,
        ]);

        $invoice = Invoice::factory()->issued()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'total' => 100.00,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient wallet balance');

        $invoice->markAsPaid();
    }

    public function test_wallet_transaction_append_only_constraint()
    {
        $wallet = Wallet::factory()->create();
        $wallet->credit(100.00, 'Test transaction');

        $transaction = $wallet->transactions->first();

        // Try to update - should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Wallet transactions cannot be updated');

        $transaction->amount = 999.99;
        $transaction->save();
    }

    public function test_invoice_immutability_after_issued()
    {
        $invoice = Invoice::factory()->issued()->create([
            'total' => 100.00,
        ]);

        // Try to modify total - should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invoice amounts cannot be modified');

        $invoice->total = 200.00;
        $invoice->save();
    }

    public function test_balance_accuracy_with_decimal_precision()
    {
        $wallet = Wallet::factory()->create();

        // Test with various decimal amounts
        $wallet->credit(10.99, 'Amount 1');
        $wallet->credit(20.01, 'Amount 2');
        $wallet->debit(5.50, 'Amount 3');
        $wallet->credit(0.50, 'Amount 4');
        $wallet->debit(1.00, 'Amount 5');

        // Expected: 10.99 + 20.01 - 5.50 + 0.50 - 1.00 = 25.00
        $this->assertEquals(25.00, $wallet->fresh()->balance);
    }
}
