<?php

namespace App\Mail;

use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RenewalReminder extends Mailable
{
    use Queueable, SerializesModels;

    public Domain $domain;
    public int $daysUntilExpiry;
    public float $renewalCost;
    public string $urgencyLevel;

    public function __construct(Domain $domain, int $daysUntilExpiry, float $renewalCost)
    {
        $this->domain = $domain;
        $this->daysUntilExpiry = $daysUntilExpiry;
        $this->renewalCost = $renewalCost;
        
        // Determine urgency level based on days
        if ($daysUntilExpiry <= 7) {
            $this->urgencyLevel = 'critical';
        } elseif ($daysUntilExpiry <= 30) {
            $this->urgencyLevel = 'high';
        } else {
            $this->urgencyLevel = 'normal';
        }
    }

    public function envelope(): Envelope
    {
        $branding = $this->domain->partner->branding;
        
        $subject = match ($this->urgencyLevel) {
            'critical' => "🚨 URGENT: {$this->domain->name} expires in {$this->daysUntilExpiry} days!",
            'high' => "⚠️ Renewal Reminder: {$this->domain->name} expires soon",
            default => "Domain Renewal Notice - {$this->domain->name}",
        };
        
        $from = $branding && $branding->email_sender_email 
            ? new Address($branding->email_sender_email, $branding->email_sender_name ?? config('app.name'))
            : null;
            
        $replyTo = $branding && $branding->reply_to_email 
            ? [new Address($branding->reply_to_email)]
            : [];
        
        return new Envelope(
            subject: $subject,
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
        
        return new Content(
            view: 'emails.renewal-reminder',
            with: [
                'domain' => $this->domain,
                'branding' => $branding,
                'daysUntilExpiry' => $this->daysUntilExpiry,
                'renewalCost' => $this->renewalCost,
                'urgencyLevel' => $this->urgencyLevel,
                'renewalUrl' => url('/dashboard/domains/' . $this->domain->id . '/renew'),
                'dashboardUrl' => url('/dashboard'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
