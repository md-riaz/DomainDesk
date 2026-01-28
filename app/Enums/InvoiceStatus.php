<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::Draft => 'Draft',
            self::Issued => 'Issued',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
        };
    }

    public function canBeModified(): bool
    {
        return $this === self::Draft;
    }

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }

    public function canBePaid(): bool
    {
        return $this === self::Issued || $this === self::Failed;
    }

    public function canBeRefunded(): bool
    {
        return $this === self::Paid;
    }
}
