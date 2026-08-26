<?php

namespace App\Http\Requests;

use App\Concerns\TrainerValidationRules;
use App\Models\Trainer;
use App\Models\User;
use App\Support\GymContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $trainerId = (int) $this->route('trainer');
        $linkedUserId = $gymContext->gym()->trainers()
            ->whereKey($trainerId)
            ->value('user_id');
        $rules = $this->trainerRules($gymContext, $trainerId);
        $uniqueUserEmail = Rule::unique(User::class, 'email');

        if (is_numeric($linkedUserId)) {
            $uniqueUserEmail->ignore((int) $linkedUserId);
        }

        $rules['email'][0] = 'required';
        $rules['email'][] = $uniqueUserEmail;

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareTrainerForValidation();
    }
}
