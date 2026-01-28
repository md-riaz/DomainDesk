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
}
