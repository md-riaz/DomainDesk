<?php

namespace App\Providers;

use App\Services\PartnerContextService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register PartnerContextService as singleton to maintain context per request
        $this->app->singleton(PartnerContextService::class, function ($app) {
            return new PartnerContextService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
