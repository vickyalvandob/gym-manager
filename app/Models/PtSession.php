<?php

namespace App\Models;

use App\Enums\PtSessionStatus;
use Database\Factories\PtSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $gym_id
 * @property int $member_pt_package_id
 * @property int $member_id
 * @property int $trainer_id
 * @property Carbon $scheduled_at
 * @property int $duration_minutes
 * @property PtSessionStatus $status
 * @property Carbon|null $completed_at
 * @property string|null $notes
 * @property string|null $cancellation_reason
 * @property bool $quota_consumed
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'gym_id',
    'member_pt_package_id',
    'member_id',
    'trainer_id',
    'scheduled_at',
    'duration_minutes',
    'status',
    'completed_at',
    'notes',
    'cancellation_reason',
    'quota_consumed',
    'created_by',
])]
class PtSession extends Model
{
    /** @use HasFactory<PtSessionFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => PtSessionStatus::Scheduled->value,
        'quota_consumed' => false,
    ];

    /** @return BelongsTo<Gym, $this> */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /** @return BelongsTo<MemberPtPackage, $this> */
    public function memberPtPackage(): BelongsTo
    {
        return $this->belongsTo(MemberPtPackage::class);
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return BelongsTo<Trainer, $this> */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'status' => PtSessionStatus::class,
            'completed_at' => 'datetime',
            'quota_consumed' => 'boolean',
        ];
    }
}
