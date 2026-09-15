<?php

namespace App\Http\Requests;

use App\Models\Member;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberPortalContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $member = $this->route('member');

        return $member instanceof Member
            && ($this->user()?->can('viewPortal', $member) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s().]{8,30}$/'],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()?->getKey()),
            ],
            'address' => ['nullable', 'string', 'max:1000'],
            'emergency_contact' => ['nullable', 'string', 'max:120'],
            'current_password' => [
                Rule::requiredIf(fn (): bool => $this->emailChanged()),
                'nullable',
                'string',
                'current_password',
            ],
        ];
    }

    /** @return array{phone: string, email: string, address: string|null, emergency_contact: string|null} */
    public function contactAttributes(): array
    {
        return [
            'phone' => $this->string('phone')->toString(),
            'email' => $this->string('email')->toString(),
            'address' => $this->filled('address')
                ? $this->string('address')->toString()
                : null,
            'emergency_contact' => $this->filled('emergency_contact')
                ? $this->string('emergency_contact')->toString()
                : null,
        ];
    }

    private function emailChanged(): bool
    {
        return mb_strtolower((string) $this->input('email'))
            !== mb_strtolower((string) $this->user()?->email);
    }
}
