<?php

namespace App\Enums;

enum DocumentType: string
{
    case IdentityProof = 'identity_proof';
    case AddressProof = 'address_proof';
    case AuthorizationLetter = 'authorization_letter';
    case Other = 'other';

    public function label(): string
    {
        return match($this) {
            self::IdentityProof => 'Identity Proof',
            self::AddressProof => 'Address Proof',
            self::AuthorizationLetter => 'Authorization Letter',
            self::Other => 'Other',
        };
    }
}
