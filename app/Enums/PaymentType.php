<?php

namespace App\Enums;

enum PaymentType: string
{
    case Membership = 'membership';
    case PersonalTraining = 'personal_training';

    public function label(): string
    {
        return match ($this) {
            self::Membership => 'Membership',
            self::PersonalTraining => 'Personal Training',
        };
    }
}
