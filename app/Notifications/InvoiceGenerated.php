<?php

namespace App\Notifications;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceGenerated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Invoice $invoice
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Invoice #{$this->invoice->invoice_number} Generated")
            ->greeting("Hello {$notifiable->name}!")
            ->line("A new invoice has been generated for your account.")
            ->line("**Invoice Number:** {$this->invoice->invoice_number}")
            ->line("**Invoice Date:** {$this->invoice->created_at->format('F j, Y')}")
            ->line("**Due Date:** {$this->invoice->due_at->format('F j, Y')}")
            ->line("**Amount:** \$" . number_format($this->invoice->total, 2))
            ->line("**Status:** " . $this->invoice->status->label())
            ->when($this->invoice->status === InvoiceStatus::Paid, function ($mail) {
                return $mail->line('✓ This invoice has been **paid**. Thank you!');
            })
            ->when($this->invoice->status === InvoiceStatus::Issued, function ($mail) {
                return $mail->line('Please process payment at your earliest convenience.')
                    ->action('View Invoice', url("/invoices/{$this->invoice->id}"))
                    ->action('Pay Now', url("/invoices/{$this->invoice->id}/pay"));
            })
            ->when($this->invoice->status === InvoiceStatus::Failed, function ($mail) {
                return $mail->line('⚠️ The previous payment attempt **failed**. Please try again.')
                    ->action('Pay Now', url("/invoices/{$this->invoice->id}/pay"));
            })
            ->line('You can download the PDF invoice from your account.')
            ->line('Thank you for your business!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'total' => $this->invoice->total,
            'status' => $this->invoice->status->value,
            'due_date' => $this->invoice->due_at->toDateTimeString(),
            'is_paid' => $this->invoice->status === InvoiceStatus::Paid,
            'is_overdue' => $this->invoice->due_at->isPast() && $this->invoice->status !== InvoiceStatus::Paid,
            'view_url' => url("/invoices/{$this->invoice->id}"),
            'pay_url' => url("/invoices/{$this->invoice->id}/pay"),
            'download_url' => url("/invoices/{$this->invoice->id}/pdf"),
        ];
    }
}
