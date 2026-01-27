<?php

namespace App\Enums;

enum TransactionType: string
{
    case Credit = 'credit';
    case Debit = 'debit';
    case Refund = 'refund';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match($this) {
            self::Credit => 'Credit',
            self::Debit => 'Debit',
            self::Refund => 'Refund',
            self::Adjustment => 'Adjustment',
        };
    }

    public function isCredit(): bool
    {
        return $this === self::Credit || $this === self::Refund;
    }

    public function isDebit(): bool
    {
        return $this === self::Debit;
    }
}
