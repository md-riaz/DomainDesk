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
    case PendingTransfer = 'pending_transfer';
    case TransferInProgress = 'transfer_in_progress';
    case TransferApproved = 'transfer_approved';
    case TransferCompleted = 'transfer_completed';
    case TransferFailed = 'transfer_failed';
    case TransferCancelled = 'transfer_cancelled';

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
            self::PendingTransfer => 'Pending Transfer',
            self::TransferInProgress => 'Transfer In Progress',
            self::TransferApproved => 'Transfer Approved',
            self::TransferCompleted => 'Transfer Completed',
            self::TransferFailed => 'Transfer Failed',
            self::TransferCancelled => 'Transfer Cancelled',
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

    public function isTransferring(): bool
    {
        return in_array($this, [
            self::PendingTransfer,
            self::TransferInProgress,
            self::TransferApproved,
        ]);
    }

    public function canCancelTransfer(): bool
    {
        return in_array($this, [
            self::PendingTransfer,
            self::TransferInProgress,
        ]);
    }
}
