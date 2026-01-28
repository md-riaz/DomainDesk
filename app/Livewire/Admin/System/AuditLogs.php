<?php

namespace App\Livewire\Admin\System;

use App\Models\AuditLog;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogs extends Component
{
    use WithPagination;

    public $search = '';
    public $filterAction = '';
    public $filterModel = '';
    public $filterPartnerId = '';
    public $filterRole = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 50;
    public $autoRefresh = false;
    
    public $showDetailsModal = false;
    public $selectedLog = null;
    public $relatedLogs = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'filterAction' => ['except' => ''],
        'filterModel' => ['except' => ''],
        'filterPartnerId' => ['except' => ''],
        'filterRole' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function mount()
    {
        if (empty($this->dateTo)) {
            $this->dateTo = now()->format('Y-m-d');
        }
        
        if (empty($this->dateFrom)) {
            $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterAction()
    {
        $this->resetPage();
    }

    public function updatingFilterModel()
    {
        $this->resetPage();
    }

    public function updatingFilterPartnerId()
    {
        $this->resetPage();
    }

    public function updatingFilterRole()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterAction = '';
        $this->filterModel = '';
        $this->filterPartnerId = '';
        $this->filterRole = '';
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function exportCsv()
    {
        $logs = $this->getFilteredQuery()->get();
        
        $filename = 'audit_logs_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 support
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV headers
            fputcsv($file, [
                'Timestamp',
                'User',
                'User Role',
                'Partner',
                'Action',
                'Model Type',
                'Model ID',
                'IP Address',
                'User Agent',
                'Changes',
            ]);

            foreach ($logs as $log) {
                // Sanitize to prevent CSV injection
                $sanitize = fn($val) => str_replace(['=', '+', '-', '@'], '', $val);
                
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $sanitize($log->user?->email ?? 'System'),
                    $sanitize($log->user?->role?->value ?? 'N/A'),
                    $sanitize($log->partner?->name ?? 'N/A'),
                    $sanitize($log->action),
                    $sanitize($log->auditable_type ?? 'N/A'),
                    $log->auditable_id ?? 'N/A',
                    $sanitize($log->ip_address),
                    $sanitize(substr($log->user_agent, 0, 100)),
                    json_encode($log->changes),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function viewDetails($logId)
    {
        $this->selectedLog = AuditLog::with(['user', 'partner', 'auditable'])
            ->withoutGlobalScopes()
            ->findOrFail($logId);
        
        // Get related logs (same model and ID, within 1 hour)
        if ($this->selectedLog->auditable_type && $this->selectedLog->auditable_id) {
            $this->relatedLogs = AuditLog::with(['user', 'partner'])
                ->withoutGlobalScopes()
                ->where('auditable_type', $this->selectedLog->auditable_type)
                ->where('auditable_id', $this->selectedLog->auditable_id)
                ->where('id', '!=', $this->selectedLog->id)
                ->whereBetween('created_at', [
                    $this->selectedLog->created_at->subHour(),
                    $this->selectedLog->created_at->addHour(),
                ])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        } else {
            $this->relatedLogs = collect();
        }
        
        $this->showDetailsModal = true;
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedLog = null;
        $this->relatedLogs = [];
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
    }

    private function getFilteredQuery()
    {
        $query = AuditLog::with(['user', 'partner', 'auditable'])
            ->withoutGlobalScopes();

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('user', function ($userQuery) {
                    $userQuery->where('email', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%");
                })
                ->orWhereHas('partner', function ($partnerQuery) {
                    $partnerQuery->where('name', 'like', "%{$this->search}%");
                })
                ->orWhere('auditable_type', 'like', "%{$this->search}%")
                ->orWhere('action', 'like', "%{$this->search}%");
            });
        }

        // Filter by action
        if ($this->filterAction) {
            $query->where('action', $this->filterAction);
        }

        // Filter by model type
        if ($this->filterModel) {
            $query->where('auditable_type', $this->filterModel);
        }

        // Filter by partner
        if ($this->filterPartnerId) {
            $query->where('partner_id', $this->filterPartnerId);
        }

        // Filter by user role
        if ($this->filterRole) {
            $query->whereHas('user', function ($userQuery) {
                $userQuery->whereRaw("role = ?", [$this->filterRole]);
            });
        }

        // Date range filter
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return $query->latest();
    }

    public function render()
    {
        $logs = $this->getFilteredQuery()->paginate($this->perPage);
        
        // Get filter options
        $actions = AuditLog::withoutGlobalScopes()
            ->distinct()
            ->pluck('action')
            ->sort()
            ->values();
        
        $modelTypes = AuditLog::withoutGlobalScopes()
            ->whereNotNull('auditable_type')
            ->distinct()
            ->pluck('auditable_type')
            ->map(fn($type) => class_basename($type))
            ->sort()
            ->values();
        
        $partners = Partner::withoutGlobalScopes()
            ->orderBy('name')
            ->get(['id', 'name']);

        $roles = ['super_admin', 'partner', 'client'];

        return view('livewire.admin.system.audit-logs', [
            'logs' => $logs,
            'actions' => $actions,
            'modelTypes' => $modelTypes,
            'partners' => $partners,
            'roles' => $roles,
        ])->layout('layouts.admin', ['title' => 'Audit Logs']);
    }
}
