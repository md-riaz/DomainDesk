<?php

namespace App\Livewire\Partner\Client;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class AddClient extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public bool $autoGeneratePassword = true;
    public bool $sendWelcomeEmail = true;
    
    public ?string $generatedPassword = null;
    public bool $showSuccess = false;

    public function mount()
    {
        $this->generatePassword();
    }

    public function generatePassword()
    {
        $this->password = Str::password(12, true, true, false, false);
        $this->autoGeneratePassword = true;
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'sendWelcomeEmail' => ['boolean'],
        ];
    }

    public function save()
    {
        $this->validate();

        $this->generatedPassword = $this->password;

        $client = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => Role::Client,
            'partner_id' => currentPartner()->id,
        ]);

        if ($this->sendWelcomeEmail) {
            // TODO: Send welcome email with credentials
            // $client->notify(new WelcomeClientNotification($this->password));
        }

        $this->showSuccess = true;

        session()->flash('success', 'Client created successfully!');
        
        $this->dispatch('client-created', clientId: $client->id);
    }

    public function resetForm()
    {
        $this->reset(['name', 'email', 'password', 'autoGeneratePassword', 'sendWelcomeEmail', 'showSuccess', 'generatedPassword']);
        $this->generatePassword();
    }

    public function render()
    {
        return view('livewire.partner.client.add-client')->layout('layouts.partner');
    }
}
