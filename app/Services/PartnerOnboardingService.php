<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Partner;
use App\Models\PartnerBranding;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PartnerOnboardingService
{
    /**
     * Create a new partner with all required setup
     */
    public function createPartner(array $data): Partner
    {
        return DB::transaction(function () use ($data) {
            // Create partner
            $partner = Partner::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'status' => $data['status'] ?? 'active',
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Create partner admin user
            $user = User::create([
                'name' => $data['admin_name'] ?? $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password'] ?? Str::random(16)),
                'role' => Role::Partner,
                'partner_id' => $partner->id,
            ]);

            // Create wallet with initial balance
            $wallet = Wallet::create([
                'partner_id' => $partner->id,
            ]);

            if (isset($data['initial_balance']) && $data['initial_balance'] > 0) {
                $wallet->credit(
                    amount: $data['initial_balance'],
                    description: 'Initial wallet balance',
                    createdBy: auth()->id()
                );
            }

            // Create default branding
            PartnerBranding::create([
                'partner_id' => $partner->id,
                'primary_color' => '#3b82f6',
                'secondary_color' => '#8b5cf6',
                'email_sender_name' => $data['name'],
                'email_sender_address' => $data['email'],
                'support_email' => $data['email'],
            ]);

            // Log to audit
            auditLog(
                'Partner created via admin panel',
                $partner,
                null,
                [
                    'admin_user_id' => $user->id,
                    'initial_balance' => $data['initial_balance'] ?? 0,
                ]
            );

            return $partner->fresh(['branding', 'wallet', 'users']);
        });
    }

    /**
     * Update partner details
     */
    public function updatePartner(Partner $partner, array $data): Partner
    {
        return DB::transaction(function () use ($partner, $data) {
            $partner->update([
                'name' => $data['name'] ?? $partner->name,
                'email' => $data['email'] ?? $partner->email,
                'status' => $data['status'] ?? $partner->status,
                'is_active' => $data['is_active'] ?? $partner->is_active,
            ]);

            // Log to audit
            auditLog('Partner updated via admin panel', $partner, null, $data);

            return $partner->fresh();
        });
    }

    /**
     * Suspend partner
     */
    public function suspendPartner(Partner $partner, ?string $reason = null): Partner
    {
        return DB::transaction(function () use ($partner, $reason) {
            $partner->update([
                'status' => 'suspended',
                'is_active' => false,
            ]);

            // Log to audit
            auditLog('Partner suspended', $partner, null, ['reason' => $reason]);

            return $partner->fresh();
        });
    }

    /**
     * Activate partner
     */
    public function activatePartner(Partner $partner): Partner
    {
        return DB::transaction(function () use ($partner) {
            $partner->update([
                'status' => 'active',
                'is_active' => true,
            ]);

            // Log to audit
            auditLog('Partner activated', $partner);

            return $partner->fresh();
        });
    }

    /**
     * Adjust partner wallet balance
     */
    public function adjustWalletBalance(
        Partner $partner,
        float $amount,
        string $reason,
        string $type = 'adjustment'
    ): void {
        $wallet = $partner->wallet;

        if (!$wallet) {
            throw new \Exception('Partner wallet not found');
        }

        DB::transaction(function () use ($wallet, $amount, $reason, $type) {
            if ($type === 'credit' && $amount > 0) {
                $wallet->credit(
                    amount: abs($amount),
                    description: $reason,
                    createdBy: auth()->id()
                );
            } elseif ($type === 'debit' && $amount > 0) {
                $wallet->debit(
                    amount: abs($amount),
                    description: $reason,
                    createdBy: auth()->id(),
                    allowNegative: true
                );
            } else {
                $wallet->adjust(
                    amount: $amount,
                    description: $reason,
                    createdBy: auth()->id()
                );
            }

            // Log to audit
            auditLog(
                'Wallet balance adjusted by admin',
                $wallet->partner,
                null,
                [
                    'type' => $type,
                    'amount' => $amount,
                    'reason' => $reason,
                ]
            );
        });
    }
}
