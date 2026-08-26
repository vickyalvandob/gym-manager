<?php

namespace App\Http\Requests;

use App\Concerns\TrainerValidationRules;
use App\Models\Trainer;
use App\Support\GymContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainerRequest extends FormRequest
{
    use TrainerValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Trainer::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(GymContext $gymContext): array
    {
        return $this->trainerRules(
            $gymContext,
            (int) $this->route('trainer'),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->prepareTrainerForValidation();
    }
}
