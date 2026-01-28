<?php

namespace App\Enums;

enum DnsRecordType: string
{
    case A = 'A';
    case AAAA = 'AAAA';
    case CNAME = 'CNAME';
    case MX = 'MX';
    case TXT = 'TXT';
    case NS = 'NS';
    case SRV = 'SRV';

    public function label(): string
    {
        return $this->value;
    }

    public function supportsPriority(): bool
    {
        return in_array($this, [self::MX, self::SRV]);
    }

    public function getColor(): string
    {
        return match($this) {
            self::A => 'blue',
            self::AAAA => 'indigo',
            self::CNAME => 'purple',
            self::MX => 'green',
            self::TXT => 'yellow',
            self::NS => 'red',
            self::SRV => 'pink',
        };
    }

    public function getBadgeClasses(): string
    {
        return match($this) {
            self::A => 'bg-blue-100 text-blue-800',
            self::AAAA => 'bg-indigo-100 text-indigo-800',
            self::CNAME => 'bg-purple-100 text-purple-800',
            self::MX => 'bg-green-100 text-green-800',
            self::TXT => 'bg-yellow-100 text-yellow-800',
            self::NS => 'bg-red-100 text-red-800',
            self::SRV => 'bg-pink-100 text-pink-800',
        };
    }
}
