<?php

namespace App\Http\Requests;

use App\Enums\GymRole;
use App\Support\GymContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CompleteOnboardingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $gymContext = app(GymContext::class);

        return $gymContext->hasGym() && $gymContext->role() === GymRole::Owner;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'timezone' => ['required', 'timezone:all'],
            'currency' => ['required', 'string', 'size:3'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->string('name')->squish()->toString(),
            'timezone' => $this->string('timezone')->trim()->toString(),
            'currency' => $this->string('currency', 'IDR')->upper()->toString(),
            'phone' => $this->filled('phone') ? $this->string('phone')->trim()->toString() : null,
            'email' => $this->filled('email') ? $this->string('email')->lower()->trim()->toString() : null,
            'address' => $this->filled('address') ? $this->string('address')->trim()->toString() : null,
        ]);
    }
}
