<?php

namespace App\Enums;

enum MemberMembershipStatus: string
{
    case Pending = 'pending';
    case Upcoming = 'upcoming';
    case Active = 'active';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu pembayaran',
            self::Upcoming => 'Akan datang',
            self::Active => 'Aktif',
            self::Expired => 'Kedaluwarsa',
        };
    }
}
