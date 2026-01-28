<?php

namespace App\Livewire\Client\Invoice;

use App\Models\Invoice;
use Livewire\Component;

class InvoiceDetail extends Component
{
    public Invoice $invoice;

    public function mount(Invoice $invoice)
    {
        // Ensure the invoice belongs to the current user and partner
        if ($invoice->client_id !== auth()->id() || $invoice->partner_id !== currentPartner()->id) {
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

    public function print()
    {
        $this->dispatch('print-invoice');
    }

    public function render()
    {
        return view('livewire.client.invoice.invoice-detail')->layout('layouts.client');
    }
}
