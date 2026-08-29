<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Trialing => 'Masa uji coba',
            self::Active => 'Aktif',
            self::PastDue => 'Pembayaran terlambat',
            self::Suspended => 'Ditangguhkan',
            self::Cancelled => 'Dibatalkan',
            self::Expired => 'Berakhir',
        };
    }
}
