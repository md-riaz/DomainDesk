<?php

namespace App\Livewire\Partner\Invoice;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Partner Invoice List Component
 * 
 * Displays all invoices for clients belonging to the current partner.
 * Includes filtering by status, client, date range, and export functionality.
 */
class InvoiceList extends Component
{
    use WithPagination;

    public string $statusFilter = 'all';
    public string $clientFilter = 'all';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';
    public string $search = '';

    protected $queryString = [
        'statusFilter' => ['except' => 'all'],
        'clientFilter' => ['except' => 'all'],
        'startDate' => ['except' => null],
        'endDate' => ['except' => null],
        'search' => ['except' => ''],
    ];

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingClientFilter()
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

    public function updatingSearch()
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
        $this->clientFilter = 'all';
        $this->startDate = null;
        $this->endDate = null;
        $this->search = '';
        $this->resetPage();
    }

    public function getInvoices()
    {
        $query = Invoice::where('partner_id', currentPartner()->id)
            ->with(['client', 'items']);

        // Apply status filter
        if ($this->statusFilter !== 'all') {
            $query->where('status', InvoiceStatus::from($this->statusFilter));
        }

        // Apply client filter
        if ($this->clientFilter !== 'all') {
            $query->where('client_id', $this->clientFilter);
        }

        // Apply date range filter
        if ($this->startDate) {
            $query->whereDate('issued_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('issued_at', '<=', $this->endDate);
        }

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('invoice_number', 'like', '%' . $this->search . '%')
                    ->orWhereHas('client', function ($q2) {
                        $q2->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('email', 'like', '%' . $this->search . '%');
                    });
            });
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(20);
    }

    public function getClients()
    {
        return \App\Models\User::where('partner_id', currentPartner()->id)
            ->where('role', \App\Enums\Role::Client)
            ->orderBy('name')
            ->get();
    }

    public function getStatistics()
    {
        $partner = currentPartner();
        
        return [
            'total' => Invoice::where('partner_id', $partner->id)->count(),
            'paid' => Invoice::where('partner_id', $partner->id)->where('status', InvoiceStatus::Paid)->count(),
            'pending' => Invoice::where('partner_id', $partner->id)->where('status', InvoiceStatus::Pending)->count(),
            'overdue' => Invoice::where('partner_id', $partner->id)->where('status', InvoiceStatus::Overdue)->count(),
            'totalRevenue' => Invoice::where('partner_id', $partner->id)->where('status', InvoiceStatus::Paid)->sum('total'),
            'pendingAmount' => Invoice::where('partner_id', $partner->id)->whereIn('status', [InvoiceStatus::Pending, InvoiceStatus::Overdue])->sum('total'),
        ];
    }

    public function exportCsv()
    {
        // TODO: Implement CSV export
        session()->flash('info', 'CSV export feature coming soon.');
    }

    public function render()
    {
        return view('livewire.partner.invoice.invoice-list', [
            'invoices' => $this->getInvoices(),
            'clients' => $this->getClients(),
            'statistics' => $this->getStatistics(),
        ])->layout('layouts.partner');
    }
}
