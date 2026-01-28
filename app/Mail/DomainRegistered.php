<?php

namespace App\Mail;

use App\Models\Domain;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DomainRegistered extends Mailable
{
    use Queueable, SerializesModels;

    public Domain $domain;
    public Invoice $invoice;

    public function __construct(Domain $domain, Invoice $invoice)
    {
        $this->domain = $domain;
        $this->invoice = $invoice;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Domain Registered Successfully - ' . $this->domain->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.domain-registered',
            with: [
                'domainName' => $this->domain->name,
                'expiresAt' => $this->domain->expires_at->format('F j, Y'),
                'invoiceNumber' => $this->invoice->invoice_number,
                'total' => number_format($this->invoice->total, 2),
                'autoRenew' => $this->domain->auto_renew,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
