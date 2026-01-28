<?php

namespace App\Livewire\Partner\Settings;

use App\Models\PartnerDomain;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DomainSettings extends Component
{
    public $domains = [];
    public $newDomain = '';
    public $showAddForm = false;
    public $domainToDelete = null;
    public $showDeleteConfirm = false;

    protected function rules()
    {
        return [
            'newDomain' => [
                'required',
                'string',
                'regex:/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i',
                'unique:partner_domains,domain',
                'max:255',
            ],
        ];
    }

    protected $messages = [
        'newDomain.regex' => 'Please enter a valid domain name (e.g., yourdomain.com).',
        'newDomain.unique' => 'This domain is already registered.',
    ];

    public function mount()
    {
        $this->loadDomains();
    }

    public function loadDomains()
    {
        $this->domains = currentPartner()
            ->domains()
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function toggleAddForm()
    {
        $this->showAddForm = !$this->showAddForm;
        if (!$this->showAddForm) {
            $this->reset(['newDomain']);
            $this->resetValidation();
        }
    }

    public function addDomain()
    {
        $this->validate();

        $partner = currentPartner();
        
        // Generate CNAME target (could be customized per environment)
        $cnameTarget = config('app.domain', parse_url(config('app.url'), PHP_URL_HOST));

        $domain = $partner->domains()->create([
            'domain' => strtolower($this->newDomain),
            'is_primary' => false,
            'is_verified' => false,
            'dns_status' => 'pending',
            'ssl_status' => 'pending',
        ]);

        $this->loadDomains();
        $this->reset(['newDomain', 'showAddForm']);
        session()->flash('message', 'Domain added successfully. Please configure your DNS records.');
    }

    public function verifyDomain($domainId)
    {
        $domain = PartnerDomain::where('partner_id', currentPartner()->id)
            ->findOrFail($domainId);

        try {
            // Perform DNS lookup for CNAME record
            $cnameTarget = config('app.domain', parse_url(config('app.url'), PHP_URL_HOST));
            $records = @dns_get_record($domain->domain, DNS_CNAME);
            
            if ($records && count($records) > 0) {
                // Check if CNAME points to our target
                $cnameFound = false;
                foreach ($records as $record) {
                    if (isset($record['target']) && 
                        str_contains($record['target'], $cnameTarget)) {
                        $cnameFound = true;
                        break;
                    }
                }

                if ($cnameFound) {
                    $domain->update([
                        'is_verified' => true,
                        'dns_status' => 'verified',
                        'verified_at' => now(),
                    ]);
                    
                    $this->loadDomains();
                    session()->flash('message', 'Domain verified successfully!');
                    return;
                }
            }

            // If we get here, verification failed
            $domain->update([
                'is_verified' => false,
                'dns_status' => 'failed',
            ]);
            
            $this->loadDomains();
            session()->flash('error', 'Domain verification failed. Please check your DNS settings.');
            
        } catch (\Exception $e) {
            $domain->update([
                'is_verified' => false,
                'dns_status' => 'failed',
            ]);
            
            $this->loadDomains();
            session()->flash('error', 'Unable to verify domain. Please try again later.');
        }
    }

    public function setPrimary($domainId)
    {
        $partner = currentPartner();
        $domain = PartnerDomain::where('partner_id', $partner->id)
            ->findOrFail($domainId);

        // Only verified domains can be set as primary
        if (!$domain->isVerified()) {
            session()->flash('error', 'Only verified domains can be set as primary.');
            return;
        }

        DB::transaction(function () use ($partner, $domain) {
            // Unset current primary
            $partner->domains()->update(['is_primary' => false]);
            
            // Set new primary
            $domain->update(['is_primary' => true]);
        });

        $this->loadDomains();
        session()->flash('message', 'Primary domain updated successfully.');
    }

    public function confirmDelete($domainId)
    {
        $this->domainToDelete = $domainId;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete()
    {
        $this->reset(['domainToDelete', 'showDeleteConfirm']);
    }

    public function deleteDomain()
    {
        if (!$this->domainToDelete) {
            return;
        }

        $domain = PartnerDomain::where('partner_id', currentPartner()->id)
            ->findOrFail($this->domainToDelete);

        // Prevent deletion of primary domain if it's the only one
        if ($domain->is_primary && currentPartner()->domains()->count() > 1) {
            session()->flash('error', 'Please set another domain as primary before deleting this one.');
            $this->cancelDelete();
            return;
        }

        $domain->delete();
        
        $this->loadDomains();
        $this->cancelDelete();
        session()->flash('message', 'Domain removed successfully.');
    }

    public function copyToClipboard($text)
    {
        // This will be handled by JavaScript in the view
        $this->dispatch('copied');
    }

    public function render()
    {
        return view('livewire.partner.settings.domain-settings')
            ->layout('layouts.app');
    }
}
