<?php

namespace App\Http\Requests;

use App\Concerns\SaasPlanValidationRules;
use App\Models\SaasPlan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSaasPlanRequest extends FormRequest
{
    use SaasPlanValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->is_platform_admin === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $plan = $this->route('saas_plan');

        return $this->saasPlanRules($plan instanceof SaasPlan ? $plan : null);
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSaasPlanForValidation();
    }
}
