<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $gym_id
 * @property int $trainer_id
 * @property int $member_id
 * @property Carbon|null $assigned_at
 * @property Carbon|null $ended_at
 * @property bool $is_active
 * @property int|null $assigned_by
 * @property int|null $ended_by
 * @property string|null $notes
 */
#[Fillable([
    'gym_id',
    'trainer_id',
    'member_id',
    'assigned_at',
    'ended_at',
    'is_active',
    'assigned_by',
    'ended_by',
    'notes',
])]
class TrainerMember extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'member_id';

    /** @return BelongsTo<Gym, $this> */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /** @return BelongsTo<Trainer, $this> */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'ended_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
