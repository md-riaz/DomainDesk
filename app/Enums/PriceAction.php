<?php

namespace App\Enums;

enum PriceAction: string
{
    case REGISTER = 'register';
    case RENEW = 'renew';
    case TRANSFER = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::REGISTER => 'Registration',
            self::RENEW => 'Renewal',
            self::TRANSFER => 'Transfer',
        };
    }
}
