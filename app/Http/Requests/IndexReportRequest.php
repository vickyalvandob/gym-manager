<?php

namespace App\Http\Requests;

use App\Enums\ReportPeriod;
use App\Support\GymContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class IndexReportRequest extends FormRequest
{
    private ?string $gymTimezone = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(GymContext $gymContext): bool
    {
        $this->gymTimezone = $gymContext->gym()->timezone;

        return $this->user()?->can('viewReports', $gymContext->gym()) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period' => ['required', Rule::enum(ReportPeriod::class)],
            'date_from' => [
                'nullable',
                Rule::requiredIf($this->string('period')->toString() === ReportPeriod::Custom->value),
                'date_format:Y-m-d',
            ],
            'date_to' => [
                'nullable',
                Rule::requiredIf($this->string('period')->toString() === ReportPeriod::Custom->value),
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (
                    $this->string('period')->toString() !== ReportPeriod::Custom->value
                    || $validator->errors()->hasAny(['date_from', 'date_to'])
                ) {
                    return;
                }

                $timezone = $this->gymTimezone ?? 'UTC';
                $dateFrom = CarbonImmutable::parse($this->string('date_from')->toString(), $timezone);
                $dateTo = CarbonImmutable::parse($this->string('date_to')->toString(), $timezone);

                if ($dateTo->isAfter(CarbonImmutable::today($timezone))) {
                    $validator->errors()->add('date_to', 'Tanggal akhir laporan tidak boleh melewati hari ini.');
                }

                if ($dateFrom->diffInDays($dateTo) > 365) {
                    $validator->errors()->add('date_to', 'Rentang laporan maksimal 366 hari.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('period')) {
            $this->merge(['period' => ReportPeriod::ThisMonth->value]);
        }
    }
}
