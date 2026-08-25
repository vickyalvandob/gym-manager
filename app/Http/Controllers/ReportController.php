<?php

namespace App\Http\Controllers;

use App\Enums\ReportPeriod;
use App\Http\Requests\IndexReportRequest;
use App\Support\GymContext;
use App\Support\ReportDateRange;
use App\Support\ReportSnapshot;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ReportSnapshot $reportSnapshot,
    ) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(IndexReportRequest $request): Response
    {
        $period = ReportPeriod::from((string) $request->validated('period'));
        $dateFrom = $request->validated('date_from');
        $dateTo = $request->validated('date_to');
        $range = ReportDateRange::make(
            $period,
            $this->gymContext->gym()->timezone,
            is_string($dateFrom) ? $dateFrom : null,
            is_string($dateTo) ? $dateTo : null,
        );

        return Inertia::render('reports/index', [
            'filters' => [
                'period' => $period->value,
                'date_from' => $period === ReportPeriod::Custom ? $range->start->toDateString() : '',
                'date_to' => $period === ReportPeriod::Custom ? $range->end->toDateString() : '',
            ],
            'range' => $range->toArray(),
            'periodOptions' => array_map(
                fn (ReportPeriod $option): array => [
                    'value' => $option->value,
                    'label' => $option->label(),
                ],
                ReportPeriod::cases(),
            ),
            'report' => Inertia::defer(
                fn (): array => $this->reportSnapshot->make($range),
                'reports',
                rescue: true,
            ),
        ]);
    }
}
