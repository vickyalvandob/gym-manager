<?php

namespace App\Http\Requests;

use App\Concerns\MembershipPlanValidationRules;
use App\Models\MembershipPlan;
use App\Support\GymContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMembershipPlanRequest extends FormRequest
{
    use MembershipPlanValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', MembershipPlan::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(GymContext $gymContext): array
    {
        return $this->membershipPlanRules($gymContext->gymId());
    }

    protected function prepareForValidation(): void
    {
        $this->prepareMembershipPlanForValidation();
    }
}
