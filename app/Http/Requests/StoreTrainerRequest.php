<?php

namespace App\Http\Requests;

use App\Concerns\TrainerValidationRules;
use App\Models\Trainer;
use App\Models\User;
use App\Support\GymContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTrainerRequest extends FormRequest
{
    use TrainerValidationRules {
        messages as trainerMessages;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Trainer::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(GymContext $gymContext): array
    {
        $rules = $this->trainerRules($gymContext);
        $rules['email'][0] = 'required';
        $rules['email'][] = Rule::unique(User::class, 'email');
        $rules['password'] = [
            'required',
            'string',
            Password::default(),
            'confirmed',
        ];

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareTrainerForValidation();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            ...$this->trainerMessages(),
            'email.required' => 'Email akun login PT wajib diisi.',
            'password.required' => 'Kata sandi akun login PT wajib diisi.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            ...$this->trainerAttributes(),
            'password' => 'kata sandi',
        ];
    }
}
