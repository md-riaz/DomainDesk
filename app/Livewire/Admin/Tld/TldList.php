<?php

namespace App\Livewire\Admin\Tld;

use App\Models\Registrar;
use App\Models\Tld;
use Livewire\Component;
use Livewire\WithPagination;

class TldList extends Component
{
    use WithPagination;

    public $search = '';
    public $registrarFilter = 'all';
    public $statusFilter = 'all';
    public $featureFilter = 'all';
    public $sortBy = 'extension';
    public $sortDirection = 'asc';
    
    public $selectedTlds = [];
    public $selectAll = false;

    protected $queryString = [
        'search',
        'registrarFilter',
        'statusFilter',
        'featureFilter',
        'sortBy',
        'sortDirection'
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRegistrarFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingFeatureFilter()
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

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedTlds = $this->getTlds()->pluck('id')->toArray();
        } else {
            $this->selectedTlds = [];
        }
    }

    public function bulkActivate()
    {
        if (empty($this->selectedTlds)) {
            $this->dispatch('tld-error', [
                'message' => 'No TLDs selected'
            ]);
            return;
        }

        Tld::whereIn('id', $this->selectedTlds)->update(['is_active' => true]);
        
        $count = count($this->selectedTlds);
        $this->selectedTlds = [];
        $this->selectAll = false;
        
        $this->dispatch('tld-updated', [
            'message' => "Activated {$count} TLD(s)"
        ]);
    }

    public function bulkDeactivate()
    {
        if (empty($this->selectedTlds)) {
            $this->dispatch('tld-error', [
                'message' => 'No TLDs selected'
            ]);
            return;
        }

        Tld::whereIn('id', $this->selectedTlds)->update(['is_active' => false]);
        
        $count = count($this->selectedTlds);
        $this->selectedTlds = [];
        $this->selectAll = false;
        
        $this->dispatch('tld-updated', [
            'message' => "Deactivated {$count} TLD(s)"
        ]);
    }

    public function bulkAssignRegistrar($registrarId)
    {
        if (empty($this->selectedTlds)) {
            $this->dispatch('tld-error', [
                'message' => 'No TLDs selected'
            ]);
            return;
        }

        $registrar = Registrar::findOrFail($registrarId);
        
        Tld::whereIn('id', $this->selectedTlds)->update(['registrar_id' => $registrarId]);
        
        $count = count($this->selectedTlds);
        $this->selectedTlds = [];
        $this->selectAll = false;
        
        $this->dispatch('tld-updated', [
            'message' => "Assigned {$count} TLD(s) to {$registrar->name}"
        ]);
        
        auditLog("Bulk assigned {$count} TLDs to registrar", $registrar);
    }

    public function toggleActive($tldId)
    {
        $tld = Tld::findOrFail($tldId);
        $newStatus = !$tld->is_active;
        
        $tld->update(['is_active' => $newStatus]);
        
        $status = $newStatus ? 'activated' : 'deactivated';
        
        $this->dispatch('tld-updated', [
            'message' => "TLD {$status}"
        ]);
        
        auditLog("TLD {$status}", $tld);
    }

    protected function getTlds()
    {
        return Tld::query()
            ->with(['registrar', 'prices' => function ($query) {
                $query->where('effective_date', '<=', now()->toDateString())
                    ->orderBy('effective_date', 'desc');
            }])
            ->when($this->search, function ($query) {
                $query->where('extension', 'like', '%' . $this->search . '%');
            })
            ->when($this->registrarFilter !== 'all', function ($query) {
                $query->where('registrar_id', $this->registrarFilter);
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                if ($this->statusFilter === 'active') {
                    $query->where('is_active', true);
                } elseif ($this->statusFilter === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when($this->featureFilter !== 'all', function ($query) {
                if ($this->featureFilter === 'dns') {
                    $query->where('supports_dns', true);
                } elseif ($this->featureFilter === 'whois_privacy') {
                    $query->where('supports_whois_privacy', true);
                }
            })
            ->orderBy($this->sortBy, $this->sortDirection);
    }

    public function render()
    {
        $tlds = $this->getTlds()->paginate(50);
        
        $registrars = Registrar::orderBy('name')->get();

        return view('livewire.admin.tld.tld-list', [
            'tlds' => $tlds,
            'registrars' => $registrars,
        ])->layout('layouts.admin', [
            'title' => 'TLD Management',
            'breadcrumbs' => [
                ['label' => 'TLDs'],
            ],
        ]);
    }
}
