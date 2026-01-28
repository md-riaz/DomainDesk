<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceIssued extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public ?float $accountBalance;

    public function __construct(Invoice $invoice, ?float $accountBalance = null)
    {
        $this->invoice = $invoice;
        $this->accountBalance = $accountBalance;
    }

    public function envelope(): Envelope
    {
        $branding = $this->invoice->partner->branding;
        
        $subject = "New Invoice #{$this->invoice->invoice_number}";
        if ($this->invoice->due_at->isPast()) {
            $subject .= " - OVERDUE";
        } elseif ($this->invoice->due_at->diffInDays(now()) <= 7) {
            $subject .= " - Due Soon";
        }
        
        return new Envelope(
            subject: $subject,
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
            view: 'emails.invoice-issued',
            with: [
                'invoice' => $this->invoice,
                'branding' => $branding,
                'accountBalance' => $this->accountBalance,
                'paymentUrl' => url('/dashboard/invoices/' . $this->invoice->id . '/pay'),
                'dashboardUrl' => url('/dashboard'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
