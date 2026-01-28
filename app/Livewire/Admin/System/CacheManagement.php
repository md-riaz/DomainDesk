<?php

namespace App\Livewire\Admin\System;

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class CacheManagement extends Component
{
    public $cacheStats = [];
    public $showConfirmation = false;
    public $confirmationType = '';

    public function mount()
    {
        $this->loadCacheStats();
    }

    public function loadCacheStats()
    {
        $this->cacheStats = [
            'driver' => config('cache.default'),
            'stores' => array_keys(config('cache.stores')),
        ];
    }

    public function confirmClearAll()
    {
        $this->confirmationType = 'all';
        $this->showConfirmation = true;
    }

    public function confirmClearCache($type)
    {
        $this->confirmationType = $type;
        $this->showConfirmation = true;
    }

    public function cancelClear()
    {
        $this->showConfirmation = false;
        $this->confirmationType = '';
    }

    public function executeClear()
    {
        try {
            switch ($this->confirmationType) {
                case 'all':
                    $this->clearAllCaches();
                    break;
                case 'application':
                    $this->clearApplicationCache();
                    break;
                case 'config':
                    $this->clearConfigCache();
                    break;
                case 'route':
                    $this->clearRouteCache();
                    break;
                case 'view':
                    $this->clearViewCache();
                    break;
                case 'event':
                    $this->clearEventCache();
                    break;
            }
            
            auditLog("Cache cleared: {$this->confirmationType}");
            
            $this->showConfirmation = false;
            $this->confirmationType = '';
            
            session()->flash('success', 'Cache cleared successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }

    private function clearAllCaches()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('event:clear');
        
        Setting::clearCache();
    }

    private function clearApplicationCache()
    {
        Artisan::call('cache:clear');
        Setting::clearCache();
    }

    private function clearConfigCache()
    {
        Artisan::call('config:clear');
    }

    private function clearRouteCache()
    {
        Artisan::call('route:clear');
    }

    private function clearViewCache()
    {
        Artisan::call('view:clear');
    }

    private function clearEventCache()
    {
        Artisan::call('event:clear');
    }

    public function optimizeCache()
    {
        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            Artisan::call('event:cache');
            
            auditLog('Cache optimized');
            
            session()->flash('success', 'Cache optimized successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to optimize cache: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.system.cache-management')
            ->layout('layouts.admin', ['title' => 'Cache Management']);
    }
}
