<?php

namespace App\Http\Requests;

use App\Concerns\PtPackageValidationRules;
use App\Models\PtPackage;
use App\Support\GymContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePtPackageRequest extends FormRequest
{
    use PtPackageValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', PtPackage::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(GymContext $gymContext): array
    {
        return $this->ptPackageRules($gymContext->gymId());
    }

    protected function prepareForValidation(): void
    {
        $this->preparePtPackageForValidation();
    }
}
