<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Client\Domain\ManageDns;
use App\Livewire\Client\Domain\ManageNameservers;
use App\Livewire\Client\Domain\RegisterDomain;
use App\Livewire\Client\Domain\RenewDomain;
use App\Livewire\Client\Domain\SearchDomain;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Guest routes (authentication) - with partner context
Route::middleware(['guest', 'partner.context'])->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

// Logout route (authenticated users only)
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->middleware('auth')->name('logout');

// Super Admin routes (no partner context)
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', \App\Livewire\Admin\Dashboard::class)->name('dashboard');
    
    // Partner routes
    Route::get('/partners', \App\Livewire\Admin\Partner\PartnerList::class)->name('partners.list');
    Route::get('/partners/add', \App\Livewire\Admin\Partner\AddPartner::class)->name('partners.add');
    Route::get('/partners/{partnerId}', \App\Livewire\Admin\Partner\PartnerDetail::class)->name('partners.show');
    
    // Partner actions
    Route::get('/partners/{partnerId}/impersonate', function ($partnerId) {
        // Double-check authorization
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }
        
        $partner = \App\Models\Partner::withoutGlobalScopes()->findOrFail($partnerId);
        session(['impersonating_partner_id' => $partner->id]);
        
        auditLog('Started impersonating partner', $partner);
            
        return redirect()->route('partner.dashboard');
    })->name('partners.impersonate');
    
    Route::get('/partners/stop-impersonate', function () {
        // Double-check authorization
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }
        
        $partnerId = session('impersonating_partner_id');
        
        if ($partnerId) {
            $partner = \App\Models\Partner::withoutGlobalScopes()->find($partnerId);
            
            if ($partner) {
                auditLog('Stopped impersonating partner', $partner);
            }
        }
        
        session()->forget('impersonating_partner_id');
        
        return redirect()->route('admin.dashboard');
    })->name('partners.stop-impersonate');
});

// Partner routes (with partner context)
Route::middleware(['auth', 'role:partner', 'partner.context'])->prefix('partner')->name('partner.')->group(function () {
    Route::get('/dashboard', \App\Livewire\Partner\Dashboard::class)->name('dashboard');
    
    // Client routes
    Route::get('/clients', \App\Livewire\Partner\Client\ClientList::class)->name('clients.list');
    Route::get('/clients/add', \App\Livewire\Partner\Client\AddClient::class)->name('clients.add');
    Route::get('/clients/{clientId}', \App\Livewire\Partner\Client\ClientDetail::class)->name('clients.show');
    
    // Settings routes
    Route::get('/settings/branding', \App\Livewire\Partner\Settings\BrandingSettings::class)->name('settings.branding');
    Route::get('/settings/domains', \App\Livewire\Partner\Settings\DomainSettings::class)->name('settings.domains');
    
    // Pricing routes
    Route::get('/pricing/rules', \App\Livewire\Partner\Pricing\PricingRules::class)->name('pricing.rules');
});

// Client routes (with partner context)
Route::middleware(['auth', 'role:client', 'partner.context'])->prefix('client')->name('client.')->group(function () {
    Route::get('/dashboard', \App\Livewire\Client\Dashboard::class)->name('dashboard');
    
    // Domain routes
    Route::get('/domains', \App\Livewire\Client\Domain\DomainList::class)->name('domains.list');
    Route::get('/domains/search', SearchDomain::class)->name('domains.search');
    Route::get('/domains/register', RegisterDomain::class)->name('domains.register');
    Route::get('/domains/transfer', \App\Livewire\Client\Domain\TransferDomain::class)->name('domains.transfer');
    Route::get('/domains/{domain}', \App\Livewire\Client\Domain\DomainDetail::class)->name('domains.show');
    Route::get('/domains/{domain}/renew', RenewDomain::class)->name('domains.renew');
    Route::get('/domains/{domain}/transfer-status', \App\Livewire\Client\Domain\TransferStatus::class)->name('domains.transfer-status');
    Route::get('/domains/{domain}/nameservers', ManageNameservers::class)->name('domains.nameservers');
    Route::get('/domains/{domain}/dns', ManageDns::class)->name('domains.dns');
    
    // Invoice routes
    Route::get('/invoices', \App\Livewire\Client\Invoice\InvoiceList::class)->name('invoices.list');
    Route::get('/invoices/{invoice}', \App\Livewire\Client\Invoice\InvoiceDetail::class)->name('invoices.show');
});
