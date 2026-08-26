<?php

namespace App\Http\Requests;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\Trainer;
use App\Support\GymContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTrainerMemberRequest extends FormRequest
{
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
        return [
            'member_id' => [
                'required',
                'integer',
                Rule::exists(Member::class, 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->where('gym_id', $gymContext->gymId())
                        ->where('status', MemberStatus::Active->value),
                ),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'member_id.exists' => 'Member aktif tidak ditemukan pada gym ini.',
        ];
    }
}
