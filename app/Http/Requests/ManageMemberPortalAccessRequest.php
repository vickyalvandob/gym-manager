<?php

namespace App\Http\Requests;

use App\Enums\MemberPortalAccessMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ManageMemberPortalAccessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'access_method' => ['required', Rule::enum(MemberPortalAccessMethod::class)],
            'password' => [
                Rule::requiredIf($this->accessMethodIsPassword()),
                'nullable',
                'string',
                Password::default(),
                'confirmed',
            ],
            'password_confirmation' => ['nullable', 'string'],
        ];
    }

    public function accessMethod(): MemberPortalAccessMethod
    {
        return MemberPortalAccessMethod::from($this->string('access_method')->toString());
    }

    public function portalPassword(): ?string
    {
        if (! $this->accessMethodIsPassword()) {
            return null;
        }

        return $this->string('password')->toString();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'access_method.required' => 'Pilih cara pemberian akses portal.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'access_method' => 'cara pemberian akses',
            'password' => 'password baru',
        ];
    }

    private function accessMethodIsPassword(): bool
    {
        return $this->string('access_method')->toString() === MemberPortalAccessMethod::Password->value;
    }
}
