<?php

namespace App\Http\Requests;

use App\Concerns\ProfileValidationRules;
use App\Enums\GymUserStatus;
use App\Support\GymContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateFrontDeskRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(GymContext $gymContext): bool
    {
        return $this->user()?->can('manageUsers', $gymContext->gym()) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules((int) $this->route('user')),
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
            'status' => ['required', Rule::enum(GymUserStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::squish((string) $this->input('name')),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'password' => $this->filled('password') ? $this->input('password') : null,
        ]);
    }
}
