<?php

namespace App\Policies;

use App\Models\Domain;
use App\Models\User;

class DomainPolicy
{
    /**
     * Determine if the user can view the domain.
     */
    public function view(User $user, Domain $domain): bool
    {
        // Super admin can view all domains
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Partner can view domains in their context
        if ($user->isPartner() && $domain->partner_id === $user->partner_id) {
            return true;
        }

        // Client can view their own domains
        if ($user->isClient() && $domain->client_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can update the domain.
     */
    public function update(User $user, Domain $domain): bool
    {
        // Super admin can update all domains
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Partner can update domains in their context
        if ($user->isPartner() && $domain->partner_id === $user->partner_id) {
            return true;
        }

        // Client can update their own domains
        if ($user->isClient() && $domain->client_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can delete the domain.
     */
    public function delete(User $user, Domain $domain): bool
    {
        // Only super admin and partner can delete domains
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isPartner() && $domain->partner_id === $user->partner_id) {
            return true;
        }

        return false;
    }
}
