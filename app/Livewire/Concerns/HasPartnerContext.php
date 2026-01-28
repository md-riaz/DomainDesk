<?php

namespace App\Livewire\Concerns;

use App\Models\Partner;
use App\Models\PartnerBranding;
use App\Models\Wallet;
use App\Services\PricingService;

trait HasPartnerContext
{
    /**
     * Get the current partner from context
     */
    public function partner(): ?Partner
    {
        return currentPartner();
    }

    /**
     * Get the current partner branding
     */
    public function branding(): ?PartnerBranding
    {
        return partnerBranding();
    }

    /**
     * Get the pricing service for current partner
     */
    public function pricing(): PricingService
    {
        return partnerPricing();
    }

    /**
     * Get the current partner wallet
     */
    public function wallet(): ?Wallet
    {
        return partnerWallet();
    }

    /**
     * Check if partner context is available
     */
    public function hasPartner(): bool
    {
        return hasPartner();
    }
}
