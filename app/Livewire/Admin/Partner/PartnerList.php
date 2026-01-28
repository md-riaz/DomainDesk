<?php

namespace App\Livewire\Admin\Partner;

use App\Models\Domain;
use App\Models\Partner;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class PartnerList extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    protected $queryString = ['search', 'statusFilter', 'sortBy', 'sortDirection'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $partners = Partner::withoutGlobalScopes()
            ->with(['wallet', 'branding'])
            ->withCount(['users' => function ($query) {
                $query->whereClient();
            }])
            ->withCount('clientDomains')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('livewire.admin.partner.partner-list', [
            'partners' => $partners,
        ])->layout('layouts.admin', [
            'title' => 'Partner Management',
            'breadcrumbs' => [
                ['label' => 'Partners'],
            ],
        ]);
    }

    public function exportCsv()
    {
        $partners = Partner::withoutGlobalScopes()
            ->with(['wallet'])
            ->withCount(['users' => function ($query) {
                $query->whereClient();
            }])
            ->withCount('clientDomains')
            ->get();

        $csv = "Name,Email,Status,Clients,Domains,Wallet Balance,Created At\n";
        
        foreach ($partners as $partner) {
            $csv .= sprintf(
                "%s,%s,%s,%d,%d,%s,%s\n",
                $partner->name,
                $partner->email,
                $partner->status,
                $partner->users_count,
                $partner->client_domains_count,
                $partner->wallet?->balance ?? 0,
                $partner->created_at->format('Y-m-d')
            );
        }

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'partners-' . now()->format('Y-m-d') . '.csv');
    }

    public function confirmSuspend($partnerId)
    {
        $this->dispatch('confirm-suspend', partnerId: $partnerId);
    }

    public function confirmActivate($partnerId)
    {
        $this->dispatch('confirm-activate', partnerId: $partnerId);
    }

    public function impersonatePartner($partnerId)
    {
        $partner = Partner::withoutGlobalScopes()->findOrFail($partnerId);
        
        session(['impersonating_partner_id' => $partner->id]);
        
        auditLog('Started impersonating partner', $partner);

        return redirect()->route('partner.dashboard');
    }
}
