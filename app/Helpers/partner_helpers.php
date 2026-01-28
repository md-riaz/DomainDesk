<?php

use App\Models\Partner;
use App\Models\PartnerBranding;
use App\Models\Wallet;
use App\Services\PartnerContextService;
use App\Services\PricingService;

if (!function_exists('partnerContext')) {
    /**
     * Get the partner context service instance
     */
    function partnerContext(): PartnerContextService
    {
        return app(PartnerContextService::class);
    }
}

if (!function_exists('currentPartner')) {
    /**
     * Get the current partner from context
     */
    function currentPartner(): ?Partner
    {
        return partnerContext()->getPartner();
    }
}

if (!function_exists('partnerBranding')) {
    /**
     * Get the current partner branding
     */
    function partnerBranding(): ?PartnerBranding
    {
        return partnerContext()->getBranding();
    }
}

if (!function_exists('partnerPricing')) {
    /**
     * Get the pricing service for the current partner
     */
    function partnerPricing(): PricingService
    {
        return partnerContext()->getPricingService();
    }
}

if (!function_exists('partnerWallet')) {
    /**
     * Get the current partner wallet
     */
    function partnerWallet(): ?Wallet
    {
        return partnerContext()->getWallet();
    }
}

if (!function_exists('hasPartner')) {
    /**
     * Check if partner context is available
     */
    function hasPartner(): bool
    {
        return partnerContext()->hasPartner();
    }
}
