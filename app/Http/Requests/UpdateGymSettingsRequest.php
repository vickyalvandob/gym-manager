<?php

namespace App\Http\Requests;

use App\Support\GymContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateGymSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(GymContext $gymContext): bool
    {
        return $this->user()?->can('update', $gymContext->gym()) ?? false;
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
            'logo' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(2 * 1024)
                    ->dimensions(Rule::dimensions()->maxWidth(2000)->maxHeight(2000)),
            ],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s().]{8,30}$/'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'membership_expiry_warning_days' => ['required', 'integer', 'min:1', 'max:90'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::squish((string) $this->input('name')),
            'phone' => $this->nullableSquishedString('phone'),
            'email' => $this->nullableLowercaseString('email'),
            'address' => $this->nullableTrimmedString('address'),
        ]);
    }

    private function nullableSquishedString(string $key): ?string
    {
        $value = Str::squish((string) $this->input($key));

        return $value === '' ? null : $value;
    }

    private function nullableLowercaseString(string $key): ?string
    {
        $value = Str::lower(trim((string) $this->input($key)));

        return $value === '' ? null : $value;
    }

    private function nullableTrimmedString(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
