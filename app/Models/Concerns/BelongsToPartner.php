<?php

namespace App\Models\Concerns;

use App\Models\Partner;
use App\Models\User;
use App\Scopes\PartnerScope;
use App\Services\PartnerContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToPartner
{
    /**
     * Boot the BelongsToPartner trait for a model.
     */
    protected static function bootBelongsToPartner(): void
    {
        // Add global scope for partner isolation
        static::addGlobalScope(new PartnerScope());

        // Automatically set partner_id on creation if not set
        static::creating(function ($model) {
            if (empty($model->partner_id)) {
                $model->partner_id = static::determinePartnerId($model);
            }

            // Validate partner_id is set (allow null for certain User roles)
            if (empty($model->partner_id) && !static::allowsNullPartnerId($model)) {
                throw new \InvalidArgumentException(
                    'partner_id is required for ' . get_class($model)
                );
            }

            // Audit log: Track entity creation with partner context
            static::auditPartnerCreation($model);
        });

        // Prevent partner_id changes after creation
        static::updating(function ($model) {
            if ($model->isDirty('partner_id')) {
                $original = $model->getOriginal('partner_id');
                $new = $model->partner_id;

                // Audit log: Track attempted partner change
                static::auditPartnerChange($model, $original, $new);

                throw new \Exception(
                    'partner_id cannot be changed after creation for security reasons'
                );
            }
        });
    }

    /**
     * Get the partner that owns the model.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Determine partner ID for the model
     */
    protected static function determinePartnerId($model): ?int
    {
        // 1. Try to get from partner context service
        $partnerContext = app(PartnerContextService::class);
        if ($partnerContext->hasPartner()) {
            return $partnerContext->getPartner()->id;
        }

        // 2. Try to get from authenticated user
        $user = Auth::user();
        if ($user && isset($user->partner_id)) {
            return $user->partner_id;
        }

        // 3. For User model, check if it's being created with a partner relation
        if ($model instanceof User && $model->isClient()) {
            // Client users must have partner_id set explicitly
            return null;
        }

        return null;
    }

    /**
     * Check if model allows null partner_id
     */
    protected static function allowsNullPartnerId($model): bool
    {
        // User model can have null partner_id for SuperAdmin and Partner roles
        if ($model instanceof User) {
            return !$model->isClient();
        }

        return false;
    }

    /**
     * Scope to temporarily disable partner scope
     */
    public function scopeWithoutPartnerScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(PartnerScope::class);
    }

    /**
     * Scope to query for a specific partner
     */
    public function scopeForPartner(Builder $query, int|Partner $partner): Builder
    {
        $partnerId = $partner instanceof Partner ? $partner->id : $partner;
        
        return $query->withoutGlobalScope(PartnerScope::class)
            ->where('partner_id', $partnerId);
    }

    /**
     * Scope to query for current partner from context
     */
    public function scopeForCurrentPartner(Builder $query): Builder
    {
        $partnerContext = app(PartnerContextService::class);
        
        if (!$partnerContext->hasPartner()) {
            // If no partner context, use authenticated user's partner
            $user = Auth::user();
            if ($user && isset($user->partner_id)) {
                return $query->forPartner($user->partner_id);
            }

            throw new \Exception('No partner context available');
        }

        return $query->forPartner($partnerContext->getPartner());
    }

    /**
     * Audit log for partner creation
     */
    protected static function auditPartnerCreation($model): void
    {
        if (!method_exists($model, 'audit')) {
            return;
        }

        $model->audit('created', [
            'partner_id' => $model->partner_id,
            'context' => 'partner_isolation',
        ]);
    }

    /**
     * Audit log for partner change attempt
     */
    protected static function auditPartnerChange($model, $original, $new): void
    {
        if (!method_exists($model, 'audit')) {
            return;
        }

        $model->audit('partner_change_attempted', [
            'original_partner_id' => $original,
            'new_partner_id' => $new,
            'blocked' => true,
            'context' => 'security_violation',
        ]);
    }

    /**
     * Check if model belongs to specific partner
     */
    public function belongsToPartner(int|Partner $partner): bool
    {
        $partnerId = $partner instanceof Partner ? $partner->id : $partner;
        return $this->partner_id === $partnerId;
    }

    /**
     * Check if model belongs to current partner context
     */
    public function belongsToCurrentPartner(): bool
    {
        $partnerContext = app(PartnerContextService::class);
        
        if (!$partnerContext->hasPartner()) {
            return false;
        }

        return $this->belongsToPartner($partnerContext->getPartner());
    }
}
