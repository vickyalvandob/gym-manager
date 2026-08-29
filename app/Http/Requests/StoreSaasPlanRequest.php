<?php

namespace App\Http\Requests;

use App\Concerns\SaasPlanValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSaasPlanRequest extends FormRequest
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
        return $this->saasPlanRules();
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSaasPlanForValidation();
    }
}
