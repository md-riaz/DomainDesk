<?php

namespace App\Mail;

use App\Models\Domain;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DomainRenewed extends Mailable
{
    use Queueable, SerializesModels;

    public Domain $domain;
    public Invoice $invoice;
    public string $previousExpiryDate;
    public int $renewalPeriod;
    public ?string $paymentMethod;

    public function __construct(Domain $domain, Invoice $invoice, string $previousExpiryDate, int $renewalPeriod = 1, ?string $paymentMethod = null)
    {
        $this->domain = $domain;
        $this->invoice = $invoice;
        $this->previousExpiryDate = $previousExpiryDate;
        $this->renewalPeriod = $renewalPeriod;
        $this->paymentMethod = $paymentMethod;
    }

    public function envelope(): Envelope
    {
        $branding = $this->domain->partner->branding;
        
        return new Envelope(
            subject: 'Domain Renewed Successfully - ' . $this->domain->name,
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
        $branding = $this->domain->partner->branding ?? (object)[
            'email_sender_name' => config('app.name'),
            'primary_color' => '#4f46e5',
            'secondary_color' => '#6366f1',
        ];
        
        return new Content(
            view: 'emails.domain-renewed',
            with: [
                'domain' => $this->domain,
                'invoice' => $this->invoice,
                'branding' => $branding,
                'previousExpiryDate' => $this->previousExpiryDate,
                'renewalPeriod' => $this->renewalPeriod,
                'paymentMethod' => $this->paymentMethod ?? 'Account Balance',
                'dashboardUrl' => url('/dashboard'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
