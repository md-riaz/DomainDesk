<?php

namespace App\Enums;

enum DomainStatus: string
{
    case PendingRegistration = 'pending_registration';
    case Active = 'active';
    case Expired = 'expired';
    case GracePeriod = 'grace_period';
    case Redemption = 'redemption';
    case Suspended = 'suspended';
    case TransferredOut = 'transferred_out';

    public function label(): string
    {
        return match($this) {
            self::PendingRegistration => 'Pending Registration',
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::GracePeriod => 'Grace Period',
            self::Redemption => 'Redemption',
            self::Suspended => 'Suspended',
            self::TransferredOut => 'Transferred Out',
        };
    }

    public function isRenewable(): bool
    {
        return in_array($this, [
            self::Active,
            self::GracePeriod,
            self::Redemption,
        ]);
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
