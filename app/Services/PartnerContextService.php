<?php

namespace App\Services;

use App\Models\Partner;
use App\Models\PartnerBranding;
use App\Models\Wallet;
use Illuminate\Support\Facades\Cache;

class PartnerContextService
{
    protected ?Partner $currentPartner = null;
    protected ?PartnerBranding $currentBranding = null;
    protected ?Wallet $currentWallet = null;
    protected ?PricingService $currentPricingService = null;
    protected bool $resolved = false;

    /**
     * Resolve partner from request domain
     */
    public function resolveFromDomain(string $domain): ?Partner
    {
        if ($this->resolved) {
            return $this->currentPartner;
        }

        $this->resolved = true;

        // Try to find partner by domain (cache for 5 minutes)
        $partner = Cache::remember(
            "partner:domain:{$domain}",
            300,
            function () use ($domain) {
                return Partner::query()
                    ->active()
                    ->whereHas('domains', function ($query) use ($domain) {
                        $query->where('domain', $domain)
                            ->where('is_verified', true);
                    })
                    ->with(['branding', 'wallet'])
                    ->first();
            }
        );

        if ($partner) {
            $this->setPartner($partner);
        }

        return $this->currentPartner;
    }

    /**
     * Resolve partner from request
     */
    public function resolveFromRequest(): ?Partner
    {
        if ($this->resolved) {
            return $this->currentPartner;
        }

        $domain = request()->getHost();
        return $this->resolveFromDomain($domain);
    }

    /**
     * Set the current partner context
     */
    public function setPartner(?Partner $partner): void
    {
        $this->currentPartner = $partner;
        $this->resolved = true;

        // Pre-load relationships
        if ($partner) {
            $this->currentBranding = $partner->branding;
            $this->currentWallet = $partner->wallet;
        } else {
            $this->currentBranding = null;
            $this->currentWallet = null;
            $this->currentPricingService = null;
        }
    }

    /**
     * Get current partner
     */
    public function getPartner(): ?Partner
    {
        return $this->currentPartner;
    }

    /**
     * Get current partner branding
     */
    public function getBranding(): ?PartnerBranding
    {
        return $this->currentBranding;
    }

    /**
     * Get current partner wallet
     */
    public function getWallet(): ?Wallet
    {
        return $this->currentWallet;
    }

    /**
     * Get pricing service for current partner
     */
    public function getPricingService(): PricingService
    {
        if (!$this->currentPricingService) {
            $this->currentPricingService = app(PricingService::class);
        }

        return $this->currentPricingService;
    }

    /**
     * Check if partner context is resolved
     */
    public function hasPartner(): bool
    {
        return $this->currentPartner !== null;
    }

    /**
     * Check if partner context is resolved
     */
    public function isResolved(): bool
    {
        return $this->resolved;
    }

    /**
     * Reset partner context (useful for testing)
     */
    public function reset(): void
    {
        $this->currentPartner = null;
        $this->currentBranding = null;
        $this->currentWallet = null;
        $this->currentPricingService = null;
        $this->resolved = false;
    }

    /**
     * Get default partner (for local development or fallback)
     */
    public function getDefaultPartner(): ?Partner
    {
        // Try to get default partner from config
        $defaultPartnerId = config('partner.default_partner_id');
        
        if ($defaultPartnerId) {
            return Cache::remember(
                "partner:default:{$defaultPartnerId}",
                300,
                fn() => Partner::active()
                    ->with(['branding', 'wallet'])
                    ->find($defaultPartnerId)
            );
        }

        // Otherwise get first active partner
        return Cache::remember(
            'partner:default:first',
            300,
            fn() => Partner::active()
                ->with(['branding', 'wallet'])
                ->first()
        );
    }

    /**
     * Resolve partner with fallback to default
     */
    public function resolveWithFallback(): ?Partner
    {
        $partner = $this->resolveFromRequest();

        if (!$partner && config('partner.use_default_fallback', false)) {
            $partner = $this->getDefaultPartner();
            if ($partner) {
                $this->setPartner($partner);
            }
        }

        return $partner;
    }
}
