<?php

namespace App\Http\Requests;

use App\Models\Member;
use App\Models\MembershipPlan;
use App\Support\GymContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignMemberMembershipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Member::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(GymContext $gymContext): array
    {
        return [
            'membership_plan_id' => [
                'required',
                'integer',
                Rule::exists(MembershipPlan::class, 'id')
                    ->where(fn (Builder $query): Builder => $query
                        ->where('gym_id', $gymContext->gymId())
                        ->where('is_active', true)),
            ],
            'start_date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
