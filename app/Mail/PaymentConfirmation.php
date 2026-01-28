<?php

namespace App\Mail;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public float $amount;
    public string $paymentMethod;
    public Carbon $paymentDate;
    public ?array $services;
    public ?float $accountBalance;
    public ?float $totalPaid;
    public ?float $outstandingBalance;

    public function __construct(
        Invoice $invoice, 
        float $amount, 
        string $paymentMethod,
        Carbon $paymentDate,
        ?array $services = null,
        ?float $accountBalance = null,
        ?float $totalPaid = null,
        ?float $outstandingBalance = null
    ) {
        $this->invoice = $invoice;
        $this->amount = $amount;
        $this->paymentMethod = $paymentMethod;
        $this->paymentDate = $paymentDate;
        $this->services = $services;
        $this->accountBalance = $accountBalance;
        $this->totalPaid = $totalPaid;
        $this->outstandingBalance = $outstandingBalance;
    }

    public function envelope(): Envelope
    {
        $branding = $this->invoice->partner->branding;
        
        return new Envelope(
            subject: "Payment Received - Invoice #{$this->invoice->invoice_number}",
            from: $branding && $branding->email_sender_email 
                ? [$branding->email_sender_email => $branding->email_sender_name ?? config('app.name')]
                : null,
            replyTo: $branding && $branding->reply_to_email 
                ? [$branding->reply_to_email]
                : null,
        );
    }

    public function content(): Content
    {
        $branding = $this->invoice->partner->branding ?? (object)[
            'email_sender_name' => config('app.name'),
            'primary_color' => '#4f46e5',
            'secondary_color' => '#6366f1',
        ];
        
        return new Content(
            view: 'emails.payment-confirmation',
            with: [
                'invoice' => $this->invoice,
                'branding' => $branding,
                'amount' => $this->amount,
                'paymentMethod' => $this->paymentMethod,
                'paymentDate' => $this->paymentDate,
                'services' => $this->services,
                'accountBalance' => $this->accountBalance,
                'totalPaid' => $this->totalPaid,
                'outstandingBalance' => $this->outstandingBalance,
                'transaction' => (object)['id' => 'TXN-' . strtoupper(substr(md5($this->invoice->id . $this->paymentDate), 0, 12))],
                'dashboardUrl' => url('/dashboard'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
