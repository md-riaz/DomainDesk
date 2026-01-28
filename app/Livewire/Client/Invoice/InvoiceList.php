<?php

namespace App\Livewire\Client\Invoice;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Livewire\Component;
use Livewire\WithPagination;

class InvoiceList extends Component
{
    use WithPagination;

    public string $statusFilter = 'all';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'statusFilter' => ['except' => 'all'],
        'startDate' => ['except' => null],
        'endDate' => ['except' => null],
    ];

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingStartDate()
    {
        $this->resetPage();
    }

    public function updatingEndDate()
    {
        $this->resetPage();
    }

    public function sortByColumn($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
    }

    public function clearFilters()
    {
        $this->statusFilter = 'all';
        $this->startDate = null;
        $this->endDate = null;
        $this->resetPage();
    }

    public function getInvoices()
    {
        $query = Invoice::where('client_id', auth()->id())
            ->where('partner_id', currentPartner()->id)
            ->with('items');

        // Apply status filter
        if ($this->statusFilter !== 'all') {
            $query->where('status', InvoiceStatus::from($this->statusFilter));
        }

        // Apply date range filter
        if ($this->startDate) {
            $query->whereDate('issued_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('issued_at', '<=', $this->endDate);
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(20);
    }

    public function getTotalAmount()
    {
        $query = Invoice::where('client_id', auth()->id())
            ->where('partner_id', currentPartner()->id);

        // Apply filters
        if ($this->statusFilter !== 'all') {
            $query->where('status', InvoiceStatus::from($this->statusFilter));
        }
        if ($this->startDate) {
            $query->whereDate('issued_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('issued_at', '<=', $this->endDate);
        }

        return $query->sum('total');
    }

    public function render()
    {
        return view('livewire.client.invoice.invoice-list', [
            'invoices' => $this->getInvoices(),
            'totalAmount' => $this->getTotalAmount(),
        ])->layout('layouts.client');
    }
}
