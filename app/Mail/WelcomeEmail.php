<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public ?string $welcomeOffer;

    public function __construct(User $user, ?string $welcomeOffer = null)
    {
        $this->user = $user;
        $this->welcomeOffer = $welcomeOffer;
    }

    public function envelope(): Envelope
    {
        $branding = $this->user->partner->branding;
        $senderName = $branding && $branding->email_sender_name 
            ? $branding->email_sender_name 
            : config('app.name');
        
        return new Envelope(
            subject: "Welcome to {$senderName}!",
            from: $branding && $branding->email_sender_email 
                ? [$branding->email_sender_email => $senderName]
                : null,
            replyTo: $branding && $branding->reply_to_email 
                ? [$branding->reply_to_email]
                : null,
        );
    }

    public function content(): Content
    {
        $branding = $this->user->partner->branding ?? (object)[
            'email_sender_name' => config('app.name'),
            'primary_color' => '#4f46e5',
            'secondary_color' => '#6366f1',
        ];
        
        return new Content(
            view: 'emails.welcome',
            with: [
                'user' => $this->user,
                'branding' => $branding,
                'welcomeOffer' => $this->welcomeOffer,
                'dashboardUrl' => url('/dashboard'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
