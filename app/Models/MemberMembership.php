<?php

namespace App\Models;

use App\Enums\MemberMembershipStatus;
use App\Enums\MembershipDurationUnit;
use Carbon\CarbonInterface;
use Database\Factories\MemberMembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $gym_id
 * @property int $member_id
 * @property int $membership_plan_id
 * @property int|null $renewed_from_id
 * @property string $plan_name
 * @property int $duration
 * @property MembershipDurationUnit $duration_unit
 * @property string $price
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'gym_id',
    'member_id',
    'membership_plan_id',
    'renewed_from_id',
    'plan_name',
    'duration',
    'duration_unit',
    'price',
    'start_date',
    'end_date',
])]

class MemberMembership extends Model
{
    /** @use HasFactory<MemberMembershipFactory> */
    use HasFactory;

    /** @return BelongsTo<Gym, $this> */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return BelongsTo<MembershipPlan, $this> */
    public function membershipPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class);
    }

    /** @return BelongsTo<MemberMembership, $this> */
    public function renewedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewed_from_id');
    }

    /** @return HasMany<MemberMembership, $this> */
    public function renewals(): HasMany
    {
        return $this->hasMany(self::class, 'renewed_from_id');
    }

    /** @return HasOne<Payment, $this> */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function statusOn(CarbonInterface $date): MemberMembershipStatus
    {
        $currentDate = $date->toDateString();

        if ($this->start_date->toDateString() > $currentDate) {
            return MemberMembershipStatus::Upcoming;
        }

        if ($this->end_date->toDateString() < $currentDate) {
            return MemberMembershipStatus::Expired;
        }

        return MemberMembershipStatus::Active;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'duration' => 'integer',
            'duration_unit' => MembershipDurationUnit::class,
            'price' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
