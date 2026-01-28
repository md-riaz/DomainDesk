<?php

namespace App\Livewire\Partner\Settings;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class BrandingSettings extends Component
{
    use WithFileUploads;

    public $logo;
    public $favicon;
    public $logoPath;
    public $faviconPath;
    public $primaryColor = '#3B82F6';
    public $secondaryColor = '#10B981';
    public $emailSenderName = '';
    public $emailSenderEmail = '';
    public $replyToEmail = '';
    public $supportEmail = '';
    public $supportPhone = '';
    public $supportUrl = '';
    
    public $showPreview = false;

    protected function rules()
    {
        return [
            'logo' => 'nullable|image|max:2048|mimes:png,jpg,jpeg,svg',
            'favicon' => 'nullable|image|max:100|mimes:ico,png',
            'primaryColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondaryColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'emailSenderName' => 'required|string|max:255',
            'emailSenderEmail' => 'required|email|max:255',
            'replyToEmail' => 'nullable|email|max:255',
            'supportEmail' => 'required|email|max:255',
            'supportPhone' => 'nullable|string|max:50',
            'supportUrl' => 'nullable|url|max:255',
        ];
    }

    protected $messages = [
        'logo.max' => 'Logo must not exceed 2MB.',
        'logo.mimes' => 'Logo must be a PNG, JPG, or SVG file.',
        'favicon.max' => 'Favicon must not exceed 100KB.',
        'favicon.mimes' => 'Favicon must be an ICO or PNG file.',
        'primaryColor.regex' => 'Primary color must be a valid hex color (e.g., #3B82F6).',
        'secondaryColor.regex' => 'Secondary color must be a valid hex color (e.g., #10B981).',
    ];

    public function mount()
    {
        $partner = currentPartner();
        $branding = $partner->branding;

        if ($branding) {
            $this->logoPath = $branding->logo_path;
            $this->faviconPath = $branding->favicon_path;
            $this->primaryColor = $branding->primary_color ?? '#3B82F6';
            $this->secondaryColor = $branding->secondary_color ?? '#10B981';
            $this->emailSenderName = $branding->email_sender_name ?? '';
            $this->emailSenderEmail = $branding->email_sender_email ?? '';
            $this->replyToEmail = $branding->reply_to_email ?? '';
            $this->supportEmail = $branding->support_email ?? '';
            $this->supportPhone = $branding->support_phone ?? '';
            $this->supportUrl = $branding->support_url ?? '';
        }
    }

    public function updatedLogo()
    {
        $this->validate(['logo' => $this->rules()['logo']]);
    }

    public function updatedFavicon()
    {
        $this->validate(['favicon' => $this->rules()['favicon']]);
    }

    public function removeLogo()
    {
        $partner = currentPartner();
        $branding = $partner->branding;

        if ($branding && $branding->logo_path) {
            Storage::disk('public')->delete($branding->logo_path);
            $branding->update(['logo_path' => null]);
            $this->logoPath = null;
            $this->logo = null;
            
            session()->flash('message', 'Logo removed successfully.');
        }
    }

    public function removeFavicon()
    {
        $partner = currentPartner();
        $branding = $partner->branding;

        if ($branding && $branding->favicon_path) {
            Storage::disk('public')->delete($branding->favicon_path);
            $branding->update(['favicon_path' => null]);
            $this->faviconPath = null;
            $this->favicon = null;
            
            session()->flash('message', 'Favicon removed successfully.');
        }
    }

    public function resetColors()
    {
        $this->primaryColor = '#3B82F6';
        $this->secondaryColor = '#10B981';
        session()->flash('message', 'Colors reset to defaults.');
    }

    public function togglePreview()
    {
        $this->showPreview = !$this->showPreview;
    }

    public function save()
    {
        $this->validate();

        $partner = currentPartner();
        $branding = $partner->branding;

        $data = [
            'partner_id' => $partner->id,
            'primary_color' => $this->primaryColor,
            'secondary_color' => $this->secondaryColor,
            'email_sender_name' => $this->emailSenderName,
            'email_sender_email' => $this->emailSenderEmail,
            'reply_to_email' => $this->replyToEmail,
            'support_email' => $this->supportEmail,
            'support_phone' => $this->supportPhone,
            'support_url' => $this->supportUrl,
        ];

        // Handle logo upload
        if ($this->logo) {
            // Delete old logo if exists
            if ($branding && $branding->logo_path) {
                Storage::disk('public')->delete($branding->logo_path);
            }
            
            $logoPath = $this->logo->store("partner-{$partner->id}/branding", 'public');
            $data['logo_path'] = $logoPath;
            $this->logoPath = $logoPath;
        }

        // Handle favicon upload
        if ($this->favicon) {
            // Delete old favicon if exists
            if ($branding && $branding->favicon_path) {
                Storage::disk('public')->delete($branding->favicon_path);
            }
            
            $faviconPath = $this->favicon->store("partner-{$partner->id}/branding", 'public');
            $data['favicon_path'] = $faviconPath;
            $this->faviconPath = $faviconPath;
        }

        if ($branding) {
            $branding->update($data);
        } else {
            $partner->branding()->create($data);
        }

        // Reset file inputs
        $this->logo = null;
        $this->favicon = null;

        session()->flash('message', 'Branding settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.partner.settings.branding-settings')
            ->layout('layouts.app');
    }
}
