<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
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
    Route::get('/dashboard', function () {
        return 'Partner Dashboard';
    })->name('dashboard');
});

// Client routes (with partner context)
Route::middleware(['auth', 'role:client', 'partner.context'])->prefix('client')->name('client.')->group(function () {
    Route::get('/dashboard', function () {
        return 'Client Dashboard';
    })->name('dashboard');
});
