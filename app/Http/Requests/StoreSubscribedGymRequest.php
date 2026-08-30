<?php

namespace App\Http\Requests;

use App\Enums\GymRole;
use App\Support\GymContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubscribedGymRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(GymContext $gymContext): bool
    {
        return $this->user() !== null
            && $gymContext->role() === GymRole::Owner;
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
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => $this->string('name')->squish()->toString()]);
    }
}
