<?php

namespace App\Mail;

use App\Models\Domain;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
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
        $branding = $this->domain->partner->branding;
        
        $from = $branding && $branding->email_sender_email 
            ? new Address($branding->email_sender_email, $branding->email_sender_name ?? config('app.name'))
            : null;
            
        $replyTo = $branding && $branding->reply_to_email 
            ? [new Address($branding->reply_to_email)]
            : [];
        
        return new Envelope(
            subject: 'Domain Registered Successfully - ' . $this->domain->name,
            from: $from,
            replyTo: $replyTo,
        );
    }

    public function content(): Content
    {
        $branding = $this->domain->partner->branding ?? (object)[
            'email_sender_name' => config('app.name'),
            'primary_color' => '#4f46e5',
            'secondary_color' => '#6366f1',
        ];
        
        $nameservers = $this->domain->nameservers()->pluck('nameserver')->toArray();
        
        return new Content(
            view: 'emails.domain-registered',
            with: [
                'domain' => $this->domain,
                'invoice' => $this->invoice,
                'branding' => $branding,
                'nameservers' => $nameservers,
                'dashboardUrl' => url('/dashboard'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
