<?php

namespace Tests\Feature\Mail;

use App\Mail\DomainExpiryAlert;
use App\Mail\DomainRegistered;
use App\Mail\DomainRenewed;
use App\Mail\DomainTransferCompleted;
use App\Mail\DomainTransferInitiated;
use App\Mail\InvoiceIssued;
use App\Mail\LowBalanceAlert;
use App\Mail\PaymentConfirmation;
use App\Mail\RenewalReminder;
use App\Mail\WelcomeEmail;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\PartnerBranding;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $partner;
    protected PartnerBranding $branding;
    protected User $user;
    protected Domain $domain;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = Partner::factory()->create();
        $this->branding = PartnerBranding::factory()->create([
            'partner_id' => $this->partner->id,
            'email_sender_name' => 'Test Company',
            'email_sender_email' => 'noreply@test.com',
            'primary_color' => '#4f46e5',
            'secondary_color' => '#6366f1',
        ]);
        
        $this->user = User::factory()->create(['partner_id' => $this->partner->id]);
        $this->domain = Domain::factory()->create([
            'partner_id' => $this->partner->id,
            'name' => 'example.com',
            'expires_at' => now()->addYear(),
        ]);
        
        $this->invoice = Invoice::factory()->create([
            'partner_id' => $this->partner->id,
            'invoice_number' => 'INV-001',
            'total' => 15.99,
        ]);
    }

    public function test_domain_registered_email_renders()
    {
        $mailable = new DomainRegistered($this->domain, $this->invoice);
        $html = $mailable->render();

        $this->assertStringContainsString($this->domain->name, $html);
        $this->assertStringContainsString($this->invoice->invoice_number, $html);
        $this->assertStringContainsString('Domain Registration Successful', $html);
    }

    public function test_domain_registered_email_includes_branding()
    {
        $mailable = new DomainRegistered($this->domain, $this->invoice);
        $html = $mailable->render();

        $this->assertStringContainsString($this->branding->primary_color, $html);
        $this->assertStringContainsString($this->branding->email_sender_name, $html);
    }

    public function test_domain_renewed_email_renders()
    {
        $mailable = new DomainRenewed($this->domain, $this->invoice, 'January 1, 2024', 1, 'Credit Card');
        $html = $mailable->render();

        $this->assertStringContainsString($this->domain->name, $html);
        $this->assertStringContainsString('Domain Renewal Successful', $html);
        $this->assertStringContainsString('January 1, 2024', $html);
    }

    public function test_renewal_reminder_normal_urgency()
    {
        $mailable = new RenewalReminder($this->domain, 45, 15.99);
        $html = $mailable->render();

        $this->assertStringContainsString($this->domain->name, $html);
        $this->assertStringContainsString('45 days', $html);
        $this->assertEquals('normal', $mailable->urgencyLevel);
    }

    public function test_renewal_reminder_high_urgency()
    {
        $mailable = new RenewalReminder($this->domain, 15, 15.99);
        $html = $mailable->render();

        $this->assertStringContainsString('15 days', $html);
        $this->assertEquals('high', $mailable->urgencyLevel);
        $this->assertStringContainsString('⚠️', $mailable->envelope()->subject);
    }

    public function test_renewal_reminder_critical_urgency()
    {
        $mailable = new RenewalReminder($this->domain, 3, 15.99);
        $html = $mailable->render();

        $this->assertStringContainsString('3 days', $html);
        $this->assertEquals('critical', $mailable->urgencyLevel);
        $this->assertStringContainsString('🚨', $mailable->envelope()->subject);
    }

    public function test_domain_expiry_alert_renders()
    {
        $mailable = new DomainExpiryAlert($this->domain, 5, 15.99, 80.00, 30);
        $html = $mailable->render();

        $this->assertStringContainsString($this->domain->name, $html);
        $this->assertStringContainsString('EXPIRED', $html);
        $this->assertStringContainsString('5 days', $html);
        $this->assertStringContainsString('80.00', $html); // redemption fee
    }

    public function test_invoice_issued_email_renders()
    {
        $mailable = new InvoiceIssued($this->invoice, 100.00);
        $html = $mailable->render();

        $this->assertStringContainsString($this->invoice->invoice_number, $html);
        $this->assertStringContainsString('15.99', $html);
        $this->assertStringContainsString('100.00', $html); // account balance
    }

    public function test_payment_confirmation_email_renders()
    {
        $mailable = new PaymentConfirmation(
            $this->invoice,
            15.99,
            'Credit Card',
            now(),
            ['example.com - Domain Registration'],
            100.00,
            250.00,
            0.00
        );
        $html = $mailable->render();

        $this->assertStringContainsString('Payment Successful', $html);
        $this->assertStringContainsString('Credit Card', $html);
        $this->assertStringContainsString($this->invoice->invoice_number, $html);
    }

    public function test_low_balance_alert_renders()
    {
        $mailable = new LowBalanceAlert(
            $this->partner,
            25.00,
            50.00,
            100.00,
            30.00,
            [['description' => 'example.com', 'due_date' => 'Jan 15', 'amount' => 15.99]],
            5,
            3,
            ['date' => 'Jan 15', 'domain' => 'example.com']
        );
        $html = $mailable->render();

        $this->assertStringContainsString('Low Balance', $html);
        $this->assertStringContainsString('25.00', $html);
        $this->assertStringContainsString('50.00', $html);
    }

    public function test_welcome_email_renders()
    {
        $mailable = new WelcomeEmail($this->user, 'Special offer!');
        $html = $mailable->render();

        $this->assertStringContainsString('Welcome', $html);
        $this->assertStringContainsString($this->user->name, $html);
        $this->assertStringContainsString('Special offer!', $html);
    }

    public function test_transfer_initiated_email_renders()
    {
        $mailable = new DomainTransferInitiated($this->domain, 'Pending', 'January 30, 2025');
        $html = $mailable->render();

        $this->assertStringContainsString($this->domain->name, $html);
        $this->assertStringContainsString('Transfer', $html);
        $this->assertStringContainsString('Pending', $html);
    }

    public function test_transfer_completed_email_renders()
    {
        $mailable = new DomainTransferCompleted($this->domain);
        $html = $mailable->render();

        $this->assertStringContainsString($this->domain->name, $html);
        $this->assertStringContainsString('Transfer', $html);
        $this->assertStringContainsString('Successful', $html);
    }

    public function test_emails_use_partner_branding_colors()
    {
        $templates = [
            new DomainRegistered($this->domain, $this->invoice),
            new DomainRenewed($this->domain, $this->invoice, 'Jan 1', 1),
            new RenewalReminder($this->domain, 30, 15.99),
        ];

        foreach ($templates as $mailable) {
            $html = $mailable->render();
            $this->assertStringContainsString($this->branding->primary_color, $html);
        }
    }

    public function test_emails_are_mobile_responsive()
    {
        $mailable = new DomainRegistered($this->domain, $this->invoice);
        $html = $mailable->render();

        $this->assertStringContainsString('viewport', $html);
        $this->assertStringContainsString('max-width', $html);
        $this->assertStringContainsString('@media', $html);
    }

    public function test_email_can_be_sent()
    {
        Mail::fake();

        $mailable = new DomainRegistered($this->domain, $this->invoice);
        Mail::to('test@example.com')->send($mailable);

        Mail::assertSent(DomainRegistered::class);
    }

    public function test_email_subject_contains_domain_name()
    {
        $mailable = new DomainRegistered($this->domain, $this->invoice);
        $envelope = $mailable->envelope();

        $this->assertStringContainsString($this->domain->name, $envelope->subject);
    }

    public function test_email_includes_footer_links()
    {
        $mailable = new DomainRegistered($this->domain, $this->invoice);
        $html = $mailable->render();

        $this->assertStringContainsString('Dashboard', $html);
        $this->assertStringContainsString('Support', $html);
        $this->assertStringContainsString('Privacy', $html);
    }
}
