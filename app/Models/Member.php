<?php

namespace App\Models;

use App\Enums\MemberGender;
use App\Enums\MemberStatus;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $gym_id
 * @property string $member_number
 * @property string $name
 * @property string $phone
 * @property string|null $email
 * @property MemberGender|null $gender
 * @property Carbon|null $birth_date
 * @property string|null $address
 * @property string|null $photo
 * @property string|null $emergency_contact
 * @property MemberStatus $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'gym_id',
    'member_number',
    'name',
    'phone',
    'email',
    'gender',
    'birth_date',
    'address',
    'photo',
    'emergency_contact',
    'status',
    'notes',
])]

class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => MemberStatus::Active->value,
    ];

    /**
     * @return BelongsTo<Gym, $this>
     */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /** @return HasMany<MemberMembership, $this> */
    public function memberships(): HasMany
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

    /** @return BelongsToMany<Trainer, $this> */
    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(Trainer::class, 'trainer_members')
            ->wherePivot('is_active', true)
            ->withPivot([
                'gym_id',
                'assigned_at',
                'ended_at',
                'is_active',
                'assigned_by',
                'ended_by',
                'notes',
            ])
            ->withTimestamps();
    }

    /** @return BelongsToMany<Trainer, $this> */
    public function trainerHistory(): BelongsToMany
    {
        return $this->belongsToMany(Trainer::class, 'trainer_members')
            ->withPivot([
                'gym_id',
                'assigned_at',
                'ended_at',
                'is_active',
                'assigned_by',
                'ended_by',
                'notes',
            ])
            ->withTimestamps();
    }

    /** @return HasMany<MemberPtPackage, $this> */
    public function ptPackages(): HasMany
    {
        return $this->hasMany(MemberPtPackage::class);
    }

    /** @return HasMany<PtSession, $this> */
    public function ptSessions(): HasMany
    {
        return $this->hasMany(PtSession::class);
    }

    /** @return HasOne<CheckIn, $this> */
    public function latestCheckIn(): HasOne
    {
        return $this->hasOne(CheckIn::class)->ofMany([
            'checked_in_at' => 'max',
            'id' => 'max',
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => MemberGender::class,
            'birth_date' => 'date',
            'status' => MemberStatus::class,
        ];
    }
}
