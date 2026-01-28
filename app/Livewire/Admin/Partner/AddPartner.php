<?php

namespace App\Livewire\Admin\Partner;

use App\Services\PartnerOnboardingService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AddPartner extends Component
{
    public $name = '';
    public $email = '';
    public $adminName = '';
    public $password = '';
    public $initialBalance = 0;
    public $status = 'active';
    public $sendWelcomeEmail = false;

    protected function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('partners', 'email')],
            'adminName' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'initialBalance' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,pending,suspended'],
            'sendWelcomeEmail' => ['boolean'],
        ];
    }

    public function render()
    {
        return view('livewire.admin.partner.add-partner')
            ->layout('layouts.admin', [
                'title' => 'Add Partner',
                'breadcrumbs' => [
                    ['label' => 'Partners', 'url' => route('admin.partners.list')],
                    ['label' => 'Add Partner'],
                ],
            ]);
    }

    public function save()
    {
        $this->validate();

        try {
            $service = new PartnerOnboardingService();
            
            $partner = $service->createPartner([
                'name' => $this->name,
                'email' => $this->email,
                'admin_name' => $this->adminName ?: $this->name,
                'password' => $this->password,
                'initial_balance' => $this->initialBalance,
                'status' => $this->status,
                'is_active' => $this->status === 'active',
            ]);

            session()->flash('success', 'Partner created successfully!');

            return redirect()->route('admin.partners.show', $partner->id);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create partner: ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        return redirect()->route('admin.partners.list');
    }
}
