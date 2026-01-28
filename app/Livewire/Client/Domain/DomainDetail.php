<?php

namespace App\Livewire\Client\Domain;

use App\Models\Domain;
use App\Services\RegistrarSyncService;
use Livewire\Component;

class DomainDetail extends Component
{
    public Domain $domain;
    public string $activeTab = 'overview';
    public bool $syncing = false;
    public array $activityLog = [];

    protected $queryString = ['activeTab' => ['except' => 'overview']];

    public function mount(Domain $domain)
    {
        $this->authorize('view', $domain);
        
        // Ensure the domain belongs to the current user and partner
        if ($domain->client_id !== auth()->id() || $domain->partner_id !== currentPartner()->id) {
            abort(403, 'Unauthorized access to this domain.');
        }

        $this->domain = $domain;
        $this->loadActivityLog();
    }

    public function switchTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    public function syncWithRegistrar()
    {
        $this->syncing = true;
        
        try {
            $syncService = app(RegistrarSyncService::class);
            $result = $syncService->syncDomain($this->domain);
            
            // Reload domain
            $this->domain->refresh();
            $this->loadActivityLog();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Domain synced successfully with registrar.'
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to sync domain: ' . $e->getMessage()
            ]);
        } finally {
            $this->syncing = false;
        }
    }

    protected function loadActivityLog()
    {
        $this->activityLog = $this->domain->auditLogs()
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'action' => $log->action,
                    'description' => $log->description,
                    'created_at' => $log->created_at,
                    'user_name' => $log->user?->name ?? 'System',
                ];
            })
            ->toArray();
    }

    public function render()
    {
        return view('livewire.client.domain.domain-detail')->layout('layouts.client');
    }
}
