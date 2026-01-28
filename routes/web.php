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
    Route::get('/dashboard', function () {
        return 'Super Admin Dashboard';
    })->name('dashboard');
});

// Partner routes (with partner context)
Route::middleware(['auth', 'role:partner', 'partner.context'])->prefix('partner')->name('partner.')->group(function () {
    Route::get('/dashboard', \App\Livewire\Partner\Dashboard::class)->name('dashboard');
    
    // Client routes
    Route::get('/clients', \App\Livewire\Partner\Client\ClientList::class)->name('clients.list');
    Route::get('/clients/add', \App\Livewire\Partner\Client\AddClient::class)->name('clients.add');
    Route::get('/clients/{clientId}', \App\Livewire\Partner\Client\ClientDetail::class)->name('clients.show');
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
