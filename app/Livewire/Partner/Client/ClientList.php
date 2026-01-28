<?php

namespace App\Livewire\Partner\Client;

use App\Enums\Role;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Client List Component
 * 
 * Displays paginated list of partner's clients with search, filtering, and sorting.
 * Provides functionality to suspend/activate clients and export to CSV.
 * Includes domain count for each client.
 */
class ClientList extends Component
{
    use WithPagination;

    const ITEMS_PER_PAGE = 20;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'sortBy' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
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

    public function suspendClient($clientId)
    {
        $client = User::whereClient()
            ->where('partner_id', currentPartner()->id)
            ->findOrFail($clientId);

        $client->delete();

        session()->flash('success', 'Client suspended successfully.');
    }

    public function activateClient($clientId)
    {
        $client = User::whereClient()
            ->withTrashed()
            ->where('partner_id', currentPartner()->id)
            ->findOrFail($clientId);

        $client->restore();

        session()->flash('success', 'Client activated successfully.');
    }

    public function exportClients()
    {
        $clients = $this->getClientsQuery()->get();

        $csv = "Name,Email,Domain Count,Created At,Status\n";
        
        foreach ($clients as $client) {
            $csv .= sprintf(
                '"%s","%s",%d,%s,%s' . "\n",
                str_replace('"', '""', $client->name),
                str_replace('"', '""', $client->email),
                $client->domains_count,
                $client->created_at->format('Y-m-d'),
                $client->trashed() ? 'Suspended' : 'Active'
            );
        }

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'clients-' . now()->format('Y-m-d') . '.csv');
    }

    protected function getClientsQuery()
    {
        $query = User::whereClient()
            ->where('partner_id', currentPartner()->id)
            ->withCount('domains');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter === 'active') {
            $query->whereNull('deleted_at');
        } elseif ($this->statusFilter === 'suspended') {
            $query->onlyTrashed();
        } else {
            $query->withTrashed();
        }

        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query;
    }

    public function render()
    {
        $clients = $this->getClientsQuery()->paginate(self::ITEMS_PER_PAGE);

        return view('livewire.partner.client.client-list', [
            'clients' => $clients,
        ])->layout('layouts.partner');
    }
}
