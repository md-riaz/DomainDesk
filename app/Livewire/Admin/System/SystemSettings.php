<?php

namespace App\Livewire\Admin\System;

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SystemSettings extends Component
{
    public $activeTab = 'general';
    
    // General Settings
    public $site_name;
    public $admin_email;
    public $default_timezone;
    public $default_currency;
    public $date_format;
    public $time_format;
    
    // Email Settings
    public $smtp_host;
    public $smtp_port;
    public $smtp_username;
    public $smtp_password;
    public $smtp_encryption;
    public $mail_from_address;
    public $mail_from_name;
    
    // Domain Settings
    public $default_nameserver_1;
    public $default_nameserver_2;
    public $default_nameserver_3;
    public $default_nameserver_4;
    public $default_ttl;
    public $auto_renewal_lead_time;
    public $grace_period_days;
    
    // Billing Settings
    public $currency_symbol;
    public $tax_rate;
    public $invoice_prefix;
    public $low_balance_threshold;
    
    // System Settings (read-only info)
    public $maintenance_mode;
    public $debug_mode;

    public function mount()
    {
        $this->loadSettings();
    }

    public function loadSettings()
    {
        // General
        $this->site_name = Setting::get('site_name', 'DomainDesk');
        $this->admin_email = Setting::get('admin_email', config('mail.from.address'));
        $this->default_timezone = Setting::get('default_timezone', 'UTC');
        $this->default_currency = Setting::get('default_currency', 'USD');
        $this->date_format = Setting::get('date_format', 'Y-m-d');
        $this->time_format = Setting::get('time_format', 'H:i:s');
        
        // Email
        $this->smtp_host = Setting::get('smtp_host', config('mail.mailers.smtp.host'));
        $this->smtp_port = Setting::get('smtp_port', config('mail.mailers.smtp.port'));
        $this->smtp_username = Setting::get('smtp_username', config('mail.mailers.smtp.username'));
        $this->smtp_password = ''; // Don't load encrypted password
        $this->smtp_encryption = Setting::get('smtp_encryption', config('mail.mailers.smtp.encryption', 'tls'));
        $this->mail_from_address = Setting::get('mail_from_address', config('mail.from.address'));
        $this->mail_from_name = Setting::get('mail_from_name', config('mail.from.name'));
        
        // Domain
        $this->default_nameserver_1 = Setting::get('default_nameserver_1', 'ns1.example.com');
        $this->default_nameserver_2 = Setting::get('default_nameserver_2', 'ns2.example.com');
        $this->default_nameserver_3 = Setting::get('default_nameserver_3', '');
        $this->default_nameserver_4 = Setting::get('default_nameserver_4', '');
        $this->default_ttl = Setting::get('default_ttl', 86400);
        $this->auto_renewal_lead_time = Setting::get('auto_renewal_lead_time', 30);
        $this->grace_period_days = Setting::get('grace_period_days', 30);
        
        // Billing
        $this->currency_symbol = Setting::get('currency_symbol', '$');
        $this->tax_rate = Setting::get('tax_rate', 0);
        $this->invoice_prefix = Setting::get('invoice_prefix', 'INV-');
        $this->low_balance_threshold = Setting::get('low_balance_threshold', 100);
        
        // System (read-only)
        $this->maintenance_mode = app()->isDownForMaintenance();
        $this->debug_mode = config('app.debug');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function saveGeneralSettings()
    {
        $this->validate([
            'site_name' => 'required|string|max:255',
            'admin_email' => 'required|email',
            'default_timezone' => 'required|string',
            'default_currency' => 'required|string|size:3',
            'date_format' => 'required|string',
            'time_format' => 'required|string',
        ]);

        Setting::set('site_name', $this->site_name, 'string', 'general');
        Setting::set('admin_email', $this->admin_email, 'string', 'general');
        Setting::set('default_timezone', $this->default_timezone, 'string', 'general');
        Setting::set('default_currency', $this->default_currency, 'string', 'general');
        Setting::set('date_format', $this->date_format, 'string', 'general');
        Setting::set('time_format', $this->time_format, 'string', 'general');

        Setting::clearCache();
        
        session()->flash('success', 'General settings saved successfully.');
    }

    public function saveEmailSettings()
    {
        $this->validate([
            'smtp_host' => 'required|string',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'smtp_username' => 'required|string',
            'smtp_password' => 'nullable|string',
            'smtp_encryption' => 'required|in:tls,ssl',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string',
        ]);

        Setting::set('smtp_host', $this->smtp_host, 'string', 'email');
        Setting::set('smtp_port', $this->smtp_port, 'integer', 'email');
        Setting::set('smtp_username', $this->smtp_username, 'string', 'email');
        
        if ($this->smtp_password) {
            Setting::set('smtp_password', $this->smtp_password, 'encrypted', 'email');
        }
        
        Setting::set('smtp_encryption', $this->smtp_encryption, 'string', 'email');
        Setting::set('mail_from_address', $this->mail_from_address, 'string', 'email');
        Setting::set('mail_from_name', $this->mail_from_name, 'string', 'email');

        Setting::clearCache();
        
        session()->flash('success', 'Email settings saved successfully.');
    }

    public function testEmail()
    {
        $this->validate([
            'mail_from_address' => 'required|email',
            'admin_email' => 'required|email',
        ]);

        try {
            Mail::raw('This is a test email from DomainDesk.', function ($message) {
                $message->to($this->admin_email)
                    ->subject('Test Email from DomainDesk');
            });

            session()->flash('success', 'Test email sent successfully to ' . $this->admin_email);
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'mail_from_address' => 'Failed to send test email: ' . $e->getMessage()
            ]);
        }
    }

    public function saveDomainSettings()
    {
        $this->validate([
            'default_nameserver_1' => 'required|string',
            'default_nameserver_2' => 'required|string',
            'default_nameserver_3' => 'nullable|string',
            'default_nameserver_4' => 'nullable|string',
            'default_ttl' => 'required|integer|min:60',
            'auto_renewal_lead_time' => 'required|integer|min:1|max:365',
            'grace_period_days' => 'required|integer|min:0|max:90',
        ]);

        Setting::set('default_nameserver_1', $this->default_nameserver_1, 'string', 'domain');
        Setting::set('default_nameserver_2', $this->default_nameserver_2, 'string', 'domain');
        Setting::set('default_nameserver_3', $this->default_nameserver_3, 'string', 'domain');
        Setting::set('default_nameserver_4', $this->default_nameserver_4, 'string', 'domain');
        Setting::set('default_ttl', $this->default_ttl, 'integer', 'domain');
        Setting::set('auto_renewal_lead_time', $this->auto_renewal_lead_time, 'integer', 'domain');
        Setting::set('grace_period_days', $this->grace_period_days, 'integer', 'domain');

        Setting::clearCache();
        
        session()->flash('success', 'Domain settings saved successfully.');
    }

    public function saveBillingSettings()
    {
        $this->validate([
            'currency_symbol' => 'required|string|max:10',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'invoice_prefix' => 'required|string|max:20',
            'low_balance_threshold' => 'required|numeric|min:0',
        ]);

        Setting::set('currency_symbol', $this->currency_symbol, 'string', 'billing');
        Setting::set('tax_rate', $this->tax_rate, 'float', 'billing');
        Setting::set('invoice_prefix', $this->invoice_prefix, 'string', 'billing');
        Setting::set('low_balance_threshold', $this->low_balance_threshold, 'float', 'billing');

        Setting::clearCache();
        
        session()->flash('success', 'Billing settings saved successfully.');
    }

    public function resetToDefaults()
    {
        if ($this->activeTab === 'general') {
            $this->site_name = 'DomainDesk';
            $this->admin_email = config('mail.from.address');
            $this->default_timezone = 'UTC';
            $this->default_currency = 'USD';
            $this->date_format = 'Y-m-d';
            $this->time_format = 'H:i:s';
        } elseif ($this->activeTab === 'email') {
            $this->smtp_host = config('mail.mailers.smtp.host');
            $this->smtp_port = config('mail.mailers.smtp.port');
            $this->smtp_username = config('mail.mailers.smtp.username');
            $this->smtp_password = '';
            $this->smtp_encryption = 'tls';
            $this->mail_from_address = config('mail.from.address');
            $this->mail_from_name = config('mail.from.name');
        } elseif ($this->activeTab === 'domain') {
            $this->default_nameserver_1 = 'ns1.example.com';
            $this->default_nameserver_2 = 'ns2.example.com';
            $this->default_nameserver_3 = '';
            $this->default_nameserver_4 = '';
            $this->default_ttl = 86400;
            $this->auto_renewal_lead_time = 30;
            $this->grace_period_days = 30;
        } elseif ($this->activeTab === 'billing') {
            $this->currency_symbol = '$';
            $this->tax_rate = 0;
            $this->invoice_prefix = 'INV-';
            $this->low_balance_threshold = 100;
        }

        session()->flash('info', 'Settings reset to defaults. Click Save to apply.');
    }

    public function render()
    {
        $timezones = timezone_identifiers_list();
        $currencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'INR'];

        return view('livewire.admin.system.system-settings', [
            'timezones' => $timezones,
            'currencies' => $currencies,
        ])->layout('layouts.admin', ['title' => 'System Settings']);
    }
}
