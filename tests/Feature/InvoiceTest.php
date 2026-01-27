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

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_can_be_created()
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create([
            'partner_id' => $partner->id,
            'role' => Role::Client,
        ]);

        $invoice = Invoice::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'partner_id' => $partner->id,
            'client_id' => $client->id,
        ]);
    }

    public function test_invoice_number_is_auto_generated()
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create([
            'partner_id' => $partner->id,
            'role' => Role::Client,
        ]);

        $invoice = Invoice::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'invoice_number' => null,
        ]);

        $this->assertNotNull($invoice->invoice_number);
        $this->assertStringContainsString('INV-', $invoice->invoice_number);
        $this->assertStringContainsString((string)$partner->id, $invoice->invoice_number);
    }

    public function test_invoice_number_generation_is_sequential()
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create([
            'partner_id' => $partner->id,
            'role' => Role::Client,
        ]);

        $invoice1 = Invoice::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'invoice_number' => null,
        ]);

        $invoice2 = Invoice::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'invoice_number' => null,
        ]);

        $parts1 = explode('-', $invoice1->invoice_number);
        $parts2 = explode('-', $invoice2->invoice_number);
        
        $this->assertEquals((int)end($parts1) + 1, (int)end($parts2));
    }

    public function test_invoice_can_have_items()
    {
        $invoice = Invoice::factory()->create();
        
        InvoiceItem::factory()->count(3)->create([
            'invoice_id' => $invoice->id,
        ]);

        $this->assertCount(3, $invoice->items);
    }

    public function test_invoice_item_total_is_calculated()
    {
        $invoice = Invoice::factory()->create();
        
        $item = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 5,
            'unit_price' => 10.00,
            'total' => null,
        ]);

        $this->assertEquals(50.00, $item->total);
    }

    public function test_invoice_totals_can_be_calculated_from_items()
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create([
            'partner_id' => $partner->id,
            'role' => Role::Client,
        ]);

        $invoice = Invoice::factory()->draft()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 2,
            'unit_price' => 50.00,
            'total' => 100.00,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 1,
            'unit_price' => 30.00,
            'total' => 30.00,
        ]);

        $invoice->calculateTotals();
        $invoice->save();

        $this->assertEquals(130.00, $invoice->subtotal);
        $this->assertEquals(130.00, $invoice->total); // tax is 0
    }

    public function test_draft_invoice_can_be_issued()
    {
        $invoice = Invoice::factory()->draft()->create();

        $result = $invoice->issue();

        $this->assertTrue($result);
        $this->assertEquals(InvoiceStatus::Issued, $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->issued_at);
        $this->assertNotNull($invoice->fresh()->due_at);
    }

    public function test_issued_invoice_cannot_be_issued_again()
    {
        $invoice = Invoice::factory()->issued()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only draft invoices can be issued');

        $invoice->issue();
    }

    public function test_issued_invoice_can_be_marked_as_paid()
    {
        $partner = Partner::factory()->create();
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);
        $wallet->credit(1000.00, 'Initial balance');

        $client = User::factory()->create([
            'partner_id' => $partner->id,
            'role' => Role::Client,
        ]);

        $invoice = Invoice::factory()->issued()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'total' => 100.00,
        ]);

        $result = $invoice->markAsPaid();

        $this->assertTrue($result);
        $this->assertEquals(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_at);
        $this->assertEquals(900.00, $wallet->fresh()->balance); // 1000 - 100
    }

    public function test_draft_invoice_cannot_be_marked_as_paid()
    {
        $invoice = Invoice::factory()->draft()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invoice cannot be marked as paid');

        $invoice->markAsPaid();
    }

    public function test_paid_invoice_can_be_refunded()
    {
        $partner = Partner::factory()->create();
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);
        $wallet->credit(500.00, 'Initial balance');

        $client = User::factory()->create([
            'partner_id' => $partner->id,
            'role' => Role::Client,
        ]);

        $invoice = Invoice::factory()->paid()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'total' => 100.00,
        ]);

        // Simulate the payment deduction first
        $wallet->debit(100.00, 'Invoice payment');

        $result = $invoice->refund();

        $this->assertTrue($result);
        $this->assertEquals(InvoiceStatus::Refunded, $invoice->fresh()->status);
        $this->assertEquals(500.00, $wallet->fresh()->balance); // 500 - 100 + 100
    }

    public function test_draft_invoice_cannot_be_refunded()
    {
        $invoice = Invoice::factory()->draft()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only paid invoices can be refunded');

        $invoice->refund();
    }

    public function test_invoice_amounts_cannot_be_modified_after_issued()
    {
        $invoice = Invoice::factory()->issued()->create([
            'subtotal' => 100.00,
            'total' => 110.00,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invoice amounts cannot be modified');

        $invoice->update(['total' => 150.00]);
    }

    public function test_invoice_status_can_be_changed_after_issued()
    {
        $invoice = Invoice::factory()->issued()->create();

        $invoice->update(['status' => InvoiceStatus::Failed]);

        $this->assertEquals(InvoiceStatus::Failed, $invoice->fresh()->status);
    }

    public function test_invoice_items_cannot_be_modified_after_issued()
    {
        $invoice = Invoice::factory()->issued()->create();
        $item = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invoice items cannot be modified');

        $item->update(['quantity' => 10]);
    }

    public function test_invoice_items_cannot_be_deleted_after_issued()
    {
        $invoice = Invoice::factory()->issued()->create();
        $item = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invoice items cannot be deleted');

        $item->delete();
    }

    public function test_invoice_can_detect_overdue_status()
    {
        $overdueInvoice = Invoice::factory()->overdue()->create();
        $currentInvoice = Invoice::factory()->issued()->create();

        $this->assertTrue($overdueInvoice->isOverdue());
        $this->assertFalse($currentInvoice->isOverdue());
    }

    public function test_invoice_overdue_scope()
    {
        Invoice::factory()->overdue()->count(3)->create();
        Invoice::factory()->issued()->count(2)->create();

        $overdueInvoices = Invoice::overdue()->get();

        $this->assertCount(3, $overdueInvoices);
    }

    public function test_partner_has_invoices_relationship()
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create([
            'partner_id' => $partner->id,
            'role' => Role::Client,
        ]);

        $invoice = Invoice::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
        ]);

        $this->assertTrue($partner->invoices->contains($invoice));
    }

    public function test_client_has_invoices_relationship()
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create([
            'partner_id' => $partner->id,
            'role' => Role::Client,
        ]);

        $invoice = Invoice::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
        ]);

        $this->assertTrue($client->invoices->contains($invoice));
    }
}
