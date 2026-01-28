<?php

namespace App\Enums;

enum ContactType: string
{
    case Registrant = 'registrant';
    case Admin = 'admin';
    case Tech = 'tech';
    case Billing = 'billing';

    public function label(): string
    {
        return match($this) {
            self::Registrant => 'Registrant',
            self::Admin => 'Administrative',
            self::Tech => 'Technical',
            self::Billing => 'Billing',
        };
    }
}
