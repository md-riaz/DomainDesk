<?php

namespace App\Livewire\Auth;

use App\Enums\Role;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Register')]
class Register extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];
    }

    public function register(): void
    {
        $validated = $this->validate();

        // For now, get the first partner as default
        // This will be replaced with proper partner context in Phase 2.2
        $defaultPartner = Partner::first();

        if (!$defaultPartner) {
            // If no partner exists, create a default one for development
            $defaultPartner = Partner::create([
                'name' => 'Default Partner',
                'email' => 'partner@domaindesk.com',
                'slug' => 'default-partner',
                'is_active' => true,
            ]);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => Role::Client,
            'partner_id' => $defaultPartner->id,
        ]);

        Auth::login($user);

        session()->regenerate();

        $this->redirect(route('client.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
