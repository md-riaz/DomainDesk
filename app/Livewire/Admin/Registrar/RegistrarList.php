<?php

namespace App\Livewire\Admin\Registrar;

use App\Models\Registrar;
use App\Services\Registrar\RegistrarFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class RegistrarList extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';
    public $sortBy = 'name';
    public $sortDirection = 'asc';

    protected $queryString = ['search', 'statusFilter', 'sortBy', 'sortDirection'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updateSortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function testConnection($registrarId)
    {
        try {
            $registrar = Registrar::findOrFail($registrarId);
            
            $instance = RegistrarFactory::make($registrar->id);
            $result = $instance->testConnection();
            
            if ($result) {
                $this->dispatch('registrar-tested', [
                    'success' => true,
                    'message' => "Connection to {$registrar->name} successful!"
                ]);
                
                auditLog('Tested registrar connection successfully', $registrar);
            } else {
                throw new \Exception('Connection test returned false');
            }
        } catch (\Throwable $e) {
            $this->dispatch('registrar-tested', [
                'success' => false,
                'message' => "Connection failed: {$e->getMessage()}"
            ]);
            
            Log::error('Registrar connection test failed', [
                'registrar_id' => $registrarId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function toggleActive($registrarId)
    {
        $registrar = Registrar::findOrFail($registrarId);
        $newStatus = !$registrar->is_active;
        
        $registrar->update(['is_active' => $newStatus]);
        
        RegistrarFactory::clearCache($registrar->id);
        
        $status = $newStatus ? 'activated' : 'deactivated';
        
        $this->dispatch('registrar-updated', [
            'message' => "Registrar {$status} successfully"
        ]);
        
        auditLog("Registrar {$status}", $registrar);
    }

    public function setDefault($registrarId)
    {
        $registrar = Registrar::findOrFail($registrarId);
        
        if (!$registrar->is_active) {
            $this->dispatch('registrar-error', [
                'message' => 'Cannot set inactive registrar as default'
            ]);
            return;
        }
        
        $registrar->markAsDefault();
        
        $this->dispatch('registrar-updated', [
            'message' => "Set {$registrar->name} as default registrar"
        ]);
        
        auditLog('Set registrar as default', $registrar);
    }

    public function getHealthStatus($registrarId)
    {
        $cacheKey = "registrar_health_{$registrarId}";
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($registrarId) {
            try {
                $registrar = Registrar::find($registrarId);
                
                if (!$registrar || !$registrar->is_active) {
                    return 'inactive';
                }
                
                $instance = RegistrarFactory::make($registrar->id);
                $result = $instance->testConnection();
                
                return $result ? 'operational' : 'error';
            } catch (\Throwable $e) {
                return 'error';
            }
        });
    }

    public function render()
    {
        $registrars = Registrar::query()
            ->withCount('tlds')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('api_class', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                if ($this->statusFilter === 'active') {
                    $query->where('is_active', true);
                } elseif ($this->statusFilter === 'inactive') {
                    $query->where('is_active', false);
                } elseif ($this->statusFilter === 'default') {
                    $query->where('is_default', true);
                }
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);

        return view('livewire.admin.registrar.registrar-list', [
            'registrars' => $registrars,
        ])->layout('layouts.admin', [
            'title' => 'Registrar Management',
            'breadcrumbs' => [
                ['label' => 'Registrars'],
            ],
        ]);
    }
}
