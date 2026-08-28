<?php

namespace App\Models;

use App\Enums\GymStatus;
use Database\Factories\GymFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $logo
 * @property GymStatus $status
 * @property string $timezone
 * @property string $currency
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property int $membership_expiry_warning_days
 * @property bool $count_pt_no_show_as_used_session
 * @property int $next_member_sequence
 * @property int $next_invoice_sequence
 * @property int $next_trainer_sequence
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'slug',
    'logo',
    'status',
    'timezone',
    'currency',
    'phone',
    'email',
    'address',
    'membership_expiry_warning_days',
    'count_pt_no_show_as_used_session',
    'next_member_sequence',
    'next_invoice_sequence',
    'next_trainer_sequence',
])]
class Gym extends Model
{
    /** @use HasFactory<GymFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => GymStatus::Active->value,
        'timezone' => 'Asia/Jakarta',
        'currency' => 'IDR',
        'membership_expiry_warning_days' => 7,
        'count_pt_no_show_as_used_session' => true,
        'next_member_sequence' => 1,
        'next_invoice_sequence' => 1,
        'next_trainer_sequence' => 1,
    ];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * @return HasMany<Member, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /** @return HasMany<MembershipPlan, $this> */
    public function membershipPlans(): HasMany
    {
        return $this->hasMany(MembershipPlan::class);
    }

    /** @return HasMany<MemberMembership, $this> */
    public function memberMemberships(): HasMany
    {
        return $this->hasMany(MemberMembership::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<CheckIn, $this> */
    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    /** @return HasMany<Trainer, $this> */
    public function trainers(): HasMany
    {
        return $this->hasMany(Trainer::class);
    }

    /** @return HasMany<PtPackage, $this> */
    public function ptPackages(): HasMany
    {
        return $this->hasMany(PtPackage::class);
    }

    /** @return HasMany<MemberPtPackage, $this> */
    public function memberPtPackages(): HasMany
    {
        return $this->hasMany(MemberPtPackage::class);
    }

    /** @return HasMany<PtSession, $this> */
    public function ptSessions(): HasMany
    {
        return $this->hasMany(PtSession::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GymStatus::class,
            'membership_expiry_warning_days' => 'integer',
            'count_pt_no_show_as_used_session' => 'boolean',
            'next_member_sequence' => 'integer',
            'next_invoice_sequence' => 'integer',
            'next_trainer_sequence' => 'integer',
        ];
    }
}
