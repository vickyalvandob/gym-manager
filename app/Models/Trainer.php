<?php

namespace App\Models;

use App\Enums\TrainerStatus;
use Database\Factories\TrainerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $gym_id
 * @property int|null $user_id
 * @property string|null $trainer_code
 * @property string $name
 * @property string $phone
 * @property string|null $email
 * @property string|null $specialization
 * @property string|null $bio
 * @property TrainerStatus $status
 * @property Carbon|null $joined_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'gym_id',
    'user_id',
    'trainer_code',
    'name',
    'phone',
    'email',
    'specialization',
    'bio',
    'status',
    'joined_at',
    'notes',
])]
class Trainer extends Model
{
    /** @use HasFactory<TrainerFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => TrainerStatus::Active->value,
    ];

    /** @return BelongsTo<Gym, $this> */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Member, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'trainer_members')
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

    /** @return BelongsToMany<Member, $this> */
    public function memberHistory(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'trainer_members')
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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => TrainerStatus::class,
            'joined_at' => 'date',
        ];
    }
}
