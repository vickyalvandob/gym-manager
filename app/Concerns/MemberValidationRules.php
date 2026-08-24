<?php

namespace App\Concerns;

use App\Enums\MemberGender;
use App\Enums\MemberStatus;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

trait MemberValidationRules
{
    /**
     * @return array<string, array<mixed>>
     */
    protected function memberRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s().]{8,30}$/'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'gender' => ['nullable', Rule::enum(MemberGender::class)],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'address' => ['nullable', 'string', 'max:1000'],
            'photo' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(2 * 1024)
                    ->dimensions(Rule::dimensions()->maxWidth(2000)->maxHeight(2000)),
            ],
            'emergency_contact' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::enum(MemberStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
