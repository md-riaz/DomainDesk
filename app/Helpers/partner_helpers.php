<?php

use App\Models\Partner;
use App\Models\PartnerBranding;
use App\Models\Wallet;
use App\Models\AuditLog;
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

if (!function_exists('auditLog')) {
    /**
     * Create an audit log entry
     */
    function auditLog(
        string $action,
        $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => auth()->id(),
            'partner_id' => session('partner_id') ?? session('impersonating_partner_id'),
            'action' => $action,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable ? $auditable->id : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

if (!function_exists('formatBDT')) {
    /**
     * Format amount in BDT (Bangladeshi Taka)
     */
    function formatBDT(int $amountInCents, bool $showSymbol = true): string
    {
        $amount = $amountInCents / 100;
        $formatted = number_format($amount, 2);
        
        return $showSymbol ? "৳{$formatted}" : $formatted;
    }
}

if (!function_exists('formatCurrency')) {
    /**
     * Format amount in the system's default currency
     */
    function formatCurrency(int $amountInCents, ?string $currency = null): string
    {
        $currency = $currency ?? config('app.currency', 'BDT');
        
        $amount = $amountInCents / 100;
        $formatted = number_format($amount, 2);
        
        // Support multiple currencies
        return match($currency) {
            'BDT' => "৳{$formatted}",
            'USD' => "\${$formatted}",
            'EUR' => "€{$formatted}",
            'GBP' => "£{$formatted}",
            'INR' => "₹{$formatted}",
            default => "{$currency} {$formatted}",
        };
    }
}
