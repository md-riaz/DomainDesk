<?php

namespace App\Livewire\Admin\System;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class SystemHealth extends Component
{
    public $healthChecks = [];
    public $lastChecked = null;

    public function mount()
    {
        $this->runHealthChecks();
    }

    public function refresh()
    {
        $this->runHealthChecks();
        session()->flash('success', 'Health checks refreshed.');
    }

    public function runHealthChecks()
    {
        $this->healthChecks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'mail' => $this->checkMail(),
        ];

        $this->lastChecked = now();
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $version = DB::select('SELECT VERSION() as version')[0]->version ?? 'Unknown';
            
            return [
                'status' => 'ok',
                'message' => 'Connected',
                'details' => "Version: {$version}",
                'troubleshooting' => null,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Connection failed',
                'details' => $e->getMessage(),
                'troubleshooting' => 'Check database credentials in .env file and ensure database server is running.',
            ];
        }
    }

    private function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            Cache::put($testKey, true, 10);
            $value = Cache::get($testKey);
            Cache::forget($testKey);
            
            if ($value === true) {
                $driver = config('cache.default');
                
                return [
                    'status' => 'ok',
                    'message' => 'Working',
                    'details' => "Driver: {$driver}",
                    'troubleshooting' => null,
                ];
            }
            
            return [
                'status' => 'warning',
                'message' => 'Cache write/read failed',
                'details' => 'Could not verify cache operation',
                'troubleshooting' => 'Check cache driver configuration and ensure cache server is accessible.',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Failed',
                'details' => $e->getMessage(),
                'troubleshooting' => 'Check cache configuration in .env and ensure cache server is running.',
            ];
        }
    }

    private function checkQueue(): array
    {
        try {
            $driver = config('queue.default');
            
            if ($driver === 'sync') {
                return [
                    'status' => 'warning',
                    'message' => 'Using sync driver',
                    'details' => 'Jobs run synchronously (no queue worker)',
                    'troubleshooting' => 'For production, use a real queue driver like database or redis.',
                ];
            }
            
            // Check if jobs table exists for database driver
            if ($driver === 'database') {
                $pendingJobs = DB::table('jobs')->count();
                $failedJobs = DB::table('failed_jobs')->count();
                
                if ($failedJobs > 10) {
                    return [
                        'status' => 'warning',
                        'message' => "Running ({$failedJobs} failed jobs)",
                        'details' => "{$pendingJobs} pending, {$failedJobs} failed",
                        'troubleshooting' => "Review failed jobs with 'php artisan queue:failed'.",
                    ];
                }
                
                return [
                    'status' => 'ok',
                    'message' => 'Running',
                    'details' => "{$pendingJobs} pending jobs",
                    'troubleshooting' => null,
                ];
            }
            
            return [
                'status' => 'ok',
                'message' => 'Configured',
                'details' => "Driver: {$driver}",
                'troubleshooting' => null,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Failed',
                'details' => $e->getMessage(),
                'troubleshooting' => 'Check queue configuration and ensure queue worker is running.',
            ];
        }
    }

    private function checkStorage(): array
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            Storage::put($testFile, 'test');
            $content = Storage::get($testFile);
            Storage::delete($testFile);
            
            if ($content === 'test') {
                // Get storage usage
                $path = storage_path();
                $total = disk_total_space($path);
                $free = disk_free_space($path);
                $used = (($total - $free) / $total) * 100;
                
                $status = $used < 80 ? 'ok' : ($used < 90 ? 'warning' : 'error');
                $message = $status === 'ok' ? 'Writable' : 'Low disk space';
                
                return [
                    'status' => $status,
                    'message' => $message,
                    'details' => sprintf('%.1f%% used (%s / %s)', 
                        $used,
                        $this->formatBytes($total - $free),
                        $this->formatBytes($total)
                    ),
                    'troubleshooting' => $status !== 'ok' ? 'Free up disk space or expand storage.' : null,
                ];
            }
            
            return [
                'status' => 'error',
                'message' => 'Write/read failed',
                'details' => 'Could not verify storage operation',
                'troubleshooting' => 'Check storage permissions and ensure directory is writable.',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Not writable',
                'details' => $e->getMessage(),
                'troubleshooting' => 'Check storage directory permissions (should be writable by web server).',
            ];
        }
    }

    private function checkMail(): array
    {
        try {
            $mailer = config('mail.default');
            
            if ($mailer === 'log') {
                return [
                    'status' => 'warning',
                    'message' => 'Using log driver',
                    'details' => 'Emails written to log file',
                    'troubleshooting' => 'Configure SMTP settings for production use.',
                ];
            }
            
            // Check if SMTP settings are configured
            $host = config('mail.mailers.smtp.host');
            
            if (empty($host) || $host === 'mailpit') {
                return [
                    'status' => 'warning',
                    'message' => 'Not configured for production',
                    'details' => 'Using development mail server',
                    'troubleshooting' => 'Configure SMTP settings in System Settings.',
                ];
            }
            
            return [
                'status' => 'ok',
                'message' => 'Configured',
                'details' => "Driver: {$mailer}, Host: {$host}",
                'troubleshooting' => null,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Configuration error',
                'details' => $e->getMessage(),
                'troubleshooting' => 'Check mail configuration in .env file.',
            ];
        }
    }

    private function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function render()
    {
        return view('livewire.admin.system.system-health')
            ->layout('layouts.admin', ['title' => 'System Health']);
    }
}
