<?php

namespace App\Livewire\Admin\System;

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class MaintenanceMode extends Component
{
    public $isMaintenanceMode = false;
    public $maintenanceMessage = '';
    public $allowedIps = '';
    public $showPreview = false;

    public function mount()
    {
        $this->isMaintenanceMode = app()->isDownForMaintenance();
        $this->maintenanceMessage = Setting::get('maintenance_message', 'We are performing scheduled maintenance. Please check back soon.');
        $this->allowedIps = Setting::get('maintenance_allowed_ips', '');
    }

    public function toggleMaintenanceMode()
    {
        $this->validate([
            'maintenanceMessage' => 'required|string|max:500',
            'allowedIps' => 'nullable|string',
        ]);

        // Save settings
        Setting::set('maintenance_message', $this->maintenanceMessage, 'string', 'system');
        Setting::set('maintenance_allowed_ips', $this->allowedIps, 'string', 'system');

        if ($this->isMaintenanceMode) {
            // Turn off maintenance mode
            Artisan::call('up');
            $this->isMaintenanceMode = false;
            
            auditLog('Maintenance mode disabled');
            
            session()->flash('success', 'Maintenance mode disabled successfully.');
        } else {
            // Turn on maintenance mode
            $ips = array_filter(array_map('trim', explode(',', $this->allowedIps)));
            
            if (!empty($ips)) {
                Artisan::call('down', [
                    '--secret' => config('app.key'),
                    '--render' => 'errors::503',
                ]);
            } else {
                Artisan::call('down', [
                    '--secret' => config('app.key'),
                    '--render' => 'errors::503',
                ]);
            }
            
            $this->isMaintenanceMode = true;
            
            auditLog('Maintenance mode enabled');
            
            session()->flash('warning', 'Maintenance mode enabled. Your IP has access via secret URL.');
        }

        Setting::clearCache();
    }

    public function togglePreview()
    {
        $this->showPreview = !$this->showPreview;
    }

    public function saveMessage()
    {
        $this->validate([
            'maintenanceMessage' => 'required|string|max:500',
            'allowedIps' => 'nullable|string',
        ]);

        Setting::set('maintenance_message', $this->maintenanceMessage, 'string', 'system');
        Setting::set('maintenance_allowed_ips', $this->allowedIps, 'string', 'system');

        Setting::clearCache();
        
        session()->flash('success', 'Maintenance settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.system.maintenance-mode')
            ->layout('layouts.admin', ['title' => 'Maintenance Mode']);
    }
}
