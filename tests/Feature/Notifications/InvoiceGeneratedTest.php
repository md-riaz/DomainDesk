<?php

namespace Tests\Feature\Notifications;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\User;
use App\Notifications\InvoiceGenerated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceGeneratedTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_uses_correct_channels(): void
    {
        $invoice = Invoice::factory()->create();
        $notification = new InvoiceGenerated($invoice);

        $user = User::factory()->create();
        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    public function test_mail_notification_contains_invoice_details(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create([
            'name' => 'John Client',
            'partner_id' => $partner->id,
            'role' => 'client',
        ]);

        $invoice = Invoice::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'invoice_number' => 'INV-2024-001',
            'total' => 150.00,
            'status' => InvoiceStatus::Issued,
        ]);

        $notification = new InvoiceGenerated($invoice);
        $mail = $notification->toMail($client);

        $rendered = $mail->render();
        $this->assertStringContainsString('John Client', $mail->greeting);
        $this->assertStringContainsString('INV-2024-001', $mail->subject);
        $this->assertStringContainsString('INV-2024-001', $rendered);
        $this->assertStringContainsString('150.00', $rendered);
    }

    public function test_shows_paid_status_message(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);

        $invoice = Invoice::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'status' => InvoiceStatus::Paid,
        ]);

        $notification = new InvoiceGenerated($invoice);
        $mail = $notification->toMail($client);

        $rendered = $mail->render();
        $this->assertStringContainsString('paid', strtolower($rendered));
        $this->assertStringContainsString('Thank you', $rendered);
    }

    public function test_shows_pending_payment_message(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);

        $invoice = Invoice::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'status' => InvoiceStatus::Issued,
        ]);

        $notification = new InvoiceGenerated($invoice);
        $mail = $notification->toMail($client);

        $rendered = $mail->render();
        $this->assertStringContainsString('process payment', strtolower($rendered));
    }

    public function test_shows_failed_invoice_message(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);

        $invoice = Invoice::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'status' => InvoiceStatus::Failed,
        ]);

        $notification = new InvoiceGenerated($invoice);
        $mail = $notification->toMail($client);

        $rendered = $mail->render();
        $this->assertStringContainsString('failed', strtolower($rendered));
    }

    public function test_includes_action_buttons_for_issued_invoice(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);

        $invoice = Invoice::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'status' => InvoiceStatus::Issued,
        ]);

        $notification = new InvoiceGenerated($invoice);
        $mail = $notification->toMail($client);

        $this->assertNotEmpty($mail->actionUrl);
    }

    public function test_array_notification_contains_required_fields(): void
    {
        $invoice = Invoice::factory()->create([
            'invoice_number' => 'INV-2024-002',
            'total' => 200.00,
            'status' => InvoiceStatus::Issued,
        ]);

        $notification = new InvoiceGenerated($invoice);
        $user = User::factory()->create();
        $array = $notification->toArray($user);

        $this->assertArrayHasKey('invoice_id', $array);
        $this->assertArrayHasKey('invoice_number', $array);
        $this->assertArrayHasKey('total', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('due_date', $array);
        $this->assertArrayHasKey('is_paid', $array);
        $this->assertArrayHasKey('is_overdue', $array);
        $this->assertArrayHasKey('view_url', $array);
        $this->assertArrayHasKey('pay_url', $array);
        $this->assertArrayHasKey('download_url', $array);

        $this->assertEquals($invoice->id, $array['invoice_id']);
        $this->assertEquals('INV-2024-002', $array['invoice_number']);
        $this->assertEquals(200.00, $array['total']);
    }

    public function test_shows_invoice_date(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);

        $invoice = Invoice::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
        ]);

        $notification = new InvoiceGenerated($invoice);
        $mail = $notification->toMail($client);

        $rendered = $mail->render();
        $this->assertStringContainsString('Invoice Date', $rendered);
    }

    public function test_includes_pay_now_action_for_failed_invoice(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);

        $invoice = Invoice::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'status' => InvoiceStatus::Failed,
        ]);

        $notification = new InvoiceGenerated($invoice);
        $mail = $notification->toMail($client);

        // Failed invoices can be paid
        $this->assertNotEmpty($mail->actionUrl);
    }
}
