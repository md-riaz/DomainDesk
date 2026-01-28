<?php

namespace App\Notifications;

use App\Models\Partner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowBalanceAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Partner $partner,
        public float $currentBalance,
        public float $threshold
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $percentageOfThreshold = ($this->currentBalance / $this->threshold) * 100;
        
        return (new MailMessage)
            ->subject("⚠️ Low Wallet Balance Alert - {$this->partner->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line('Your partner wallet balance is running low.')
            ->line("**Current Balance:** \$" . number_format($this->currentBalance, 2))
            ->line("**Alert Threshold:** \$" . number_format($this->threshold, 2))
            ->line("**Percentage of Threshold:** " . number_format($percentageOfThreshold, 1) . "%")
            ->when($this->currentBalance <= 0, function ($mail) {
                return $mail->error()
                    ->line('🚨 **CRITICAL**: Your balance is zero or negative! Immediate top-up required.');
            })
            ->when($this->currentBalance > 0 && $this->currentBalance < ($this->threshold * 0.5), function ($mail) {
                return $mail->line('⚠️ Your balance is below 50% of the threshold. Please consider topping up soon.');
            })
            ->action('Top Up Wallet', url("/partners/{$this->partner->id}/wallet/topup"))
            ->line('Maintaining adequate balance ensures uninterrupted service for your domain operations.')
            ->line('You can also:')
            ->line('• View transaction history: ' . url("/partners/{$this->partner->id}/wallet/transactions"))
            ->line('• Set up automatic top-ups: ' . url("/partners/{$this->partner->id}/wallet/settings"))
            ->line('Thank you for your attention!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'partner_id' => $this->partner->id,
            'partner_name' => $this->partner->name,
            'current_balance' => $this->currentBalance,
            'threshold' => $this->threshold,
            'percentage_of_threshold' => ($this->currentBalance / $this->threshold) * 100,
            'severity' => $this->getSeverity(),
            'topup_url' => url("/partners/{$this->partner->id}/wallet/topup"),
            'transactions_url' => url("/partners/{$this->partner->id}/wallet/transactions"),
        ];
    }

    protected function getSeverity(): string
    {
        if ($this->currentBalance <= 0) {
            return 'critical';
        }
        
        $percentage = ($this->currentBalance / $this->threshold) * 100;
        
        return match (true) {
            $percentage < 25 => 'high',
            $percentage < 50 => 'medium',
            default => 'low',
        };
    }
}
