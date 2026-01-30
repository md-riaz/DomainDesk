<?php

namespace App\Livewire\Partner\Invoice;

use App\Models\Invoice;
use Livewire\Component;

/**
 * Partner Invoice Detail Component
 * 
 * Displays detailed information about a specific invoice for partners.
 * Partners can view invoice details, items, and client information.
 */
class InvoiceDetail extends Component
{
    public Invoice $invoice;

    public function mount(Invoice $invoice)
    {
        // Ensure the invoice belongs to the current partner
        if ($invoice->partner_id !== currentPartner()->id) {
            abort(403, 'Unauthorized access to this invoice.');
        }

        $this->invoice = $invoice->load(['items', 'client']);
    }

    public function downloadPdf()
    {
        // TODO: Implement PDF generation
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'PDF download feature coming soon.'
        ]);
    }

    public function sendToClient()
    {
        // TODO: Implement email sending
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Send invoice email feature coming soon.'
        ]);
    }

    public function markAsPaid()
    {
        // TODO: Implement mark as paid
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Mark as paid feature coming soon.'
        ]);
    }

    public function print()
    {
        $this->dispatch('print-invoice');
    }

    public function render()
    {
        return view('livewire.partner.invoice.invoice-detail')->layout('layouts.partner');
    }
}
