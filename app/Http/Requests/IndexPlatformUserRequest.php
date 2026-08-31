<?php

namespace App\Http\Requests;

use App\Models\SaasPlan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPlatformUserRequest extends FormRequest
{
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
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'account_type' => ['nullable', Rule::in(['subscriber', 'staff', 'platform_admin'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'plan_id' => ['nullable', 'integer', Rule::exists(SaasPlan::class, 'id')],
            'billing_status' => ['nullable', Rule::in(['pending'])],
            'per_page' => ['nullable', 'integer', Rule::in([15, 30, 50])],
        ];
    }
}
