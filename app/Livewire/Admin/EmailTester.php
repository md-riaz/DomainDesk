<?php

namespace App\Livewire\Admin;

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
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class EmailTester extends Component
{
    public $emailType = 'domain_registered';
    public $partnerId;
    public $recipientEmail;
    public $testMode = 'preview';
    public $previewHtml = '';
    public $sendStatus = '';
    
    public $availableEmailTypes = [
        'domain_registered' => 'Domain Registered',
        'domain_renewed' => 'Domain Renewed',
        'renewal_reminder_30' => 'Renewal Reminder (30 days)',
        'renewal_reminder_7' => 'Renewal Reminder (7 days)',
        'renewal_reminder_1' => 'Renewal Reminder (1 day)',
        'domain_expired' => 'Domain Expired Alert',
        'invoice_issued' => 'Invoice Issued',
        'payment_confirmation' => 'Payment Confirmation',
        'low_balance' => 'Low Balance Alert',
        'welcome' => 'Welcome Email',
        'transfer_initiated' => 'Transfer Initiated',
        'transfer_completed' => 'Transfer Completed',
    ];

    public function mount()
    {
        $this->partnerId = Partner::first()->id ?? null;
        $this->recipientEmail = auth()->user()->email ?? 'test@example.com';
    }

    public function previewEmail()
    {
        try {
            $mailable = $this->getMailable();
            $this->previewHtml = $mailable->render();
            $this->sendStatus = '';
        } catch (\Exception $e) {
            $this->sendStatus = 'Error: ' . $e->getMessage();
            $this->previewHtml = '';
        }
    }

    public function sendTestEmail()
    {
        try {
            $mailable = $this->getMailable();
            Mail::to($this->recipientEmail)->send($mailable);
            $this->sendStatus = 'success';
        } catch (\Exception $e) {
            $this->sendStatus = 'error: ' . $e->getMessage();
        }
    }

    protected function getMailable()
    {
        $partner = Partner::with('branding')->findOrFail($this->partnerId);
        
        // Create test data
        $user = $partner->users()->first() ?? User::factory()->make([
            'partner_id' => $partner->id,
            'email' => $this->recipientEmail,
        ]);
        
        $domain = Domain::factory()->make([
            'partner_id' => $partner->id,
            'name' => 'example-test-domain.com',
            'expires_at' => now()->addYear(),
        ]);
        $domain->id = 1;
        $domain->partner = $partner;
        
        $invoice = Invoice::factory()->make([
            'partner_id' => $partner->id,
            'invoice_number' => 'INV-TEST-001',
            'total' => 15.99,
            'issued_at' => now(),
            'due_at' => now()->addDays(14),
            'paid_at' => now(),
        ]);
        $invoice->id = 1;
        $invoice->partner = $partner;
        $invoice->setRelation('items', collect([
            (object)[
                'description' => 'Domain Registration - example.com',
                'quantity' => 1,
                'unit_price' => 15.99,
                'total' => 15.99,
                'metadata' => null,
            ]
        ]));
        
        return match ($this->emailType) {
            'domain_registered' => new DomainRegistered($domain, $invoice),
            'domain_renewed' => new DomainRenewed($domain, $invoice, now()->subYear()->format('F j, Y'), 1, 'Credit Card'),
            'renewal_reminder_30' => new RenewalReminder($domain, 30, 15.99),
            'renewal_reminder_7' => new RenewalReminder($domain, 7, 15.99),
            'renewal_reminder_1' => new RenewalReminder($domain, 1, 15.99),
            'domain_expired' => new DomainExpiryAlert($domain, 3, 15.99, 80.00, 30),
            'invoice_issued' => new InvoiceIssued($invoice, 100.00),
            'payment_confirmation' => new PaymentConfirmation(
                $invoice, 
                15.99, 
                'Credit Card',
                now(),
                ['example.com - Domain Registration'],
                100.00,
                250.00,
                0.00
            ),
            'low_balance' => new LowBalanceAlert(
                $partner,
                25.00,
                50.00,
                100.00,
                30.00,
                [
                    ['description' => 'example.com renewal', 'due_date' => now()->addDays(15)->format('M d, Y'), 'amount' => 15.99],
                    ['description' => 'test.com renewal', 'due_date' => now()->addDays(20)->format('M d, Y'), 'amount' => 12.99],
                ],
                5,
                3,
                ['date' => now()->addDays(15)->format('M d, Y'), 'domain' => 'example.com']
            ),
            'welcome' => new WelcomeEmail($user, 'Get 10% off your first domain registration!'),
            'transfer_initiated' => new DomainTransferInitiated($domain, 'Pending', now()->addDays(5)->format('F j, Y'), 'Previous Registrar', 'AUTH123'),
            'transfer_completed' => new DomainTransferCompleted($domain),
            default => throw new \Exception('Unknown email type'),
        };
    }

    public function render()
    {
        $partners = Partner::with('branding')->get();
        
        return view('livewire.admin.email-tester', [
            'partners' => $partners,
        ]);
    }
}
