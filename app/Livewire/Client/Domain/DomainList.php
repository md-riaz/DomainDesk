<?php

namespace App\Livewire\Client\Domain;

use App\Enums\DomainStatus;
use App\Models\Domain;
use Livewire\Component;
use Livewire\WithPagination;

class DomainList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $sortBy = 'name';
    public string $sortDirection = 'asc';
    public array $selectedDomains = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'sortBy' => ['except' => 'name'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function sortByColumn($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function toggleAutoRenew(Domain $domain)
    {
        $this->authorize('update', $domain);
        
        $domain->update([
            'auto_renew' => !$domain->auto_renew
        ]);
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Auto-renew updated successfully.'
        ]);
    }

    public function getDomains()
    {
        $query = Domain::where('client_id', auth()->id())
            ->where('partner_id', currentPartner()->id);

        // Apply search
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        // Apply status filter
        if ($this->statusFilter !== 'all') {
            if ($this->statusFilter === 'expiring_soon') {
                $query->expiring(30);
            } else {
                $query->where('status', DomainStatus::from($this->statusFilter));
            }
        }

        // Apply sorting
        if ($this->sortBy === 'expires_at') {
            $query->orderBy('expires_at', $this->sortDirection);
        } elseif ($this->sortBy === 'created_at') {
            $query->orderBy('created_at', $this->sortDirection);
        } else {
            $query->orderBy('name', $this->sortDirection);
        }

        return $query->paginate(20);
    }

    public function render()
    {
        return view('livewire.client.domain.domain-list', [
            'domains' => $this->getDomains(),
        ])->layout('layouts.client');
    }
}
