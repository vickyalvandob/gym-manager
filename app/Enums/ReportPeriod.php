<?php

namespace App\Enums;

enum ReportPeriod: string
{
    case Today = 'today';
    case Yesterday = 'yesterday';
    case ThisWeek = 'this_week';
    case ThisMonth = 'this_month';
    case LastMonth = 'last_month';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Today => 'Hari ini',
            self::Yesterday => 'Kemarin',
            self::ThisWeek => 'Minggu ini',
            self::ThisMonth => 'Bulan ini',
            self::LastMonth => 'Bulan lalu',
            self::Custom => 'Tanggal kustom',
        };
    }
}
