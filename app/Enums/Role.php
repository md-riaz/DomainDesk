<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Partner = 'partner';
    case Client = 'client';

    public function label(): string
    {
        return match($this) {
            self::SuperAdmin => 'Super Admin',
            self::Partner => 'Partner',
            self::Client => 'Client',
        };
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function isPartner(): bool
    {
        return $this === self::Partner;
    }

    public function isClient(): bool
    {
        return $this === self::Client;
    }
}
