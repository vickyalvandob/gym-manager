<?php

namespace App\Support;

use App\Enums\ReportPeriod;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class ReportDateRange
{
    private function __construct(
        public readonly ReportPeriod $period,
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    public static function make(
        ReportPeriod $period,
        string $timezone,
        ?string $customStart = null,
        ?string $customEnd = null,
    ): self {
        $today = CarbonImmutable::today($timezone);

        [$start, $end] = match ($period) {
            ReportPeriod::Today => [$today, $today],
            ReportPeriod::Yesterday => [$today->subDay(), $today->subDay()],
            ReportPeriod::ThisWeek => [
                $today->startOfWeek(CarbonInterface::MONDAY),
                $today,
            ],
            ReportPeriod::ThisMonth => [$today->startOfMonth(), $today],
            ReportPeriod::LastMonth => [
                $today->subMonthNoOverflow()->startOfMonth(),
                $today->subMonthNoOverflow()->endOfMonth()->startOfDay(),
            ],
            ReportPeriod::Custom => [
                CarbonImmutable::parse((string) $customStart, $timezone)->startOfDay(),
                CarbonImmutable::parse((string) $customEnd, $timezone)->startOfDay(),
            ],
        };

        return new self($period, $start, $end);
    }

    public function startUtc(): CarbonImmutable
    {
        return $this->start->utc();
    }

    public function endUtcExclusive(): CarbonImmutable
    {
        return $this->end->addDay()->startOfDay()->utc();
    }

    public function days(): int
    {
        return (int) $this->start->diffInDays($this->end) + 1;
    }

    /** @return array{period: string, label: string, date_from: string, date_to: string, days: int} */
    public function toArray(): array
    {
        return [
            'period' => $this->period->value,
            'label' => $this->period->label(),
            'date_from' => $this->start->toDateString(),
            'date_to' => $this->end->toDateString(),
            'days' => $this->days(),
        ];
    }
}
