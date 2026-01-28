<?php

namespace App\Scopes;

use App\Services\PartnerContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class PartnerScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Skip scope if explicitly disabled on the query
        if ($this->isScopeDisabled($builder)) {
            return;
        }

        // Skip scope for SuperAdmin users
        if ($this->isSuperAdmin()) {
            return;
        }

        // For User model, only apply to clients
        if ($model instanceof \App\Models\User) {
            $partnerId = $this->getCurrentPartnerId();
            if ($partnerId !== null) {
                $builder->where($model->getTable() . '.role', 'client')
                    ->where($model->getTable() . '.partner_id', $partnerId);
            }
            return;
        }

        // Get current partner context
        $partnerId = $this->getCurrentPartnerId();

        // Apply partner filter if we have a partner context
        if ($partnerId !== null) {
            $builder->where($model->getTable() . '.partner_id', $partnerId);
        } else {
            // If no partner context, apply a filter that returns no results for security
            // This prevents data leakage when no partner context is available
            $builder->whereRaw('1 = 0');
        }
    }

    /**
     * Check if the scope is disabled for this query
     */
    protected function isScopeDisabled(Builder $builder): bool
    {
        // Check if this global scope was explicitly removed
        $removedScopes = $builder->removedScopes();
        return in_array(static::class, $removedScopes, true);
    }

    /**
     * Check if current user is SuperAdmin
     */
    protected function isSuperAdmin(): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        return method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
    }

    /**
     * Get current partner ID from context or user
     */
    protected function getCurrentPartnerId(): ?int
    {
        // Try to get from partner context service
        $partnerContext = app(PartnerContextService::class);
        
        if ($partnerContext->hasPartner()) {
            return $partnerContext->getPartner()->id;
        }

        // Fallback to authenticated user's partner
        $user = Auth::user();
        
        if ($user && isset($user->partner_id) && $user->partner_id !== null) {
            return $user->partner_id;
        }

        return null;
    }
}
