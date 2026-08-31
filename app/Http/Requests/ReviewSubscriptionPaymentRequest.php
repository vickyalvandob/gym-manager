<?php

namespace App\Http\Requests;

use App\Enums\SubscriptionPaymentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewSubscriptionPaymentRequest extends FormRequest
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
            'decision' => [
                'required',
                Rule::in([
                    SubscriptionPaymentStatus::Approved->value,
                    SubscriptionPaymentStatus::Rejected->value,
                ]),
            ],
            'review_notes' => [
                Rule::requiredIf($this->string('decision')->toString() === SubscriptionPaymentStatus::Rejected->value),
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
