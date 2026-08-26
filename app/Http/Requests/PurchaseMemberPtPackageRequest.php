<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\TrainerStatus;
use App\Models\MemberPtPackage;
use App\Models\PtPackage;
use App\Models\Trainer;
use App\Support\GymContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseMemberPtPackageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', MemberPtPackage::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(GymContext $gymContext): array
    {
        return [
            'pt_package_id' => [
                'required',
                'integer',
                Rule::exists(PtPackage::class, 'id')->where(fn ($query) => $query
                    ->where('gym_id', $gymContext->gymId())
                    ->where('is_active', true)),
            ],
            'trainer_id' => [
                'required',
                'integer',
                Rule::exists(Trainer::class, 'id')->where(fn ($query) => $query
                    ->where('gym_id', $gymContext->gymId())
                    ->where('status', TrainerStatus::Active->value)),
            ],
            'start_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => $this->filled('notes')
                ? trim((string) $this->input('notes'))
                : null,
        ]);
    }
}
