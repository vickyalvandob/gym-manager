<?php

namespace App\Enums;

enum MemberMembershipStatus: string
{
    case Upcoming = 'upcoming';
    case Active = 'active';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Akan datang',
            self::Active => 'Aktif',
            self::Expired => 'Kedaluwarsa',
        };
    }
}
