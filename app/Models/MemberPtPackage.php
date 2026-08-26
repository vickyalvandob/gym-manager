<?php

namespace App\Models;

use App\Enums\MemberPtPackageStatus;
use App\Enums\PaymentStatus;
use App\Enums\PtSessionStatus;
use Carbon\CarbonInterface;
use Database\Factories\MemberPtPackageFactory;
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
 * @property int $trainer_id
 * @property int $pt_package_id
 * @property int $total_sessions
 * @property int $used_sessions
 * @property Carbon $start_date
 * @property Carbon|null $expires_at
 * @property string $price
 * @property MemberPtPackageStatus $status
 * @property PaymentStatus $payment_status
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'gym_id',
    'member_id',
    'trainer_id',
    'pt_package_id',
    'total_sessions',
    'used_sessions',
    'start_date',
    'expires_at',
    'price',
    'status',
    'payment_status',
    'notes',
    'created_by',
])]
class MemberPtPackage extends Model
{
    /** @use HasFactory<MemberPtPackageFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'used_sessions' => 0,
        'status' => MemberPtPackageStatus::Pending->value,
        'payment_status' => PaymentStatus::Pending->value,
    ];

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

    /** @return BelongsTo<Trainer, $this> */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    /** @return BelongsTo<PtPackage, $this> */
    public function ptPackage(): BelongsTo
    {
        return $this->belongsTo(PtPackage::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<PtSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(PtSession::class);
    }

    /** @return HasOne<Payment, $this> */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function effectiveStatusOn(CarbonInterface $date): MemberPtPackageStatus
    {
        if (in_array($this->status, [
            MemberPtPackageStatus::Completed,
            MemberPtPackageStatus::Cancelled,
        ], true)) {
            return $this->status;
        }

        if ($this->expires_at !== null && $this->expires_at->toDateString() < $date->toDateString()) {
            return MemberPtPackageStatus::Expired;
        }

        return $this->status;
    }

    public function availableSessions(int $scheduledSessions): int
    {
        return max(0, $this->total_sessions - $this->used_sessions - $scheduledSessions);
    }

    public function scheduledSessionsCount(): int
    {
        return $this->sessions()
            ->where('status', PtSessionStatus::Scheduled->value)
            ->count();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'total_sessions' => 'integer',
            'used_sessions' => 'integer',
            'start_date' => 'date',
            'expires_at' => 'date',
            'price' => 'decimal:2',
            'status' => MemberPtPackageStatus::class,
            'payment_status' => PaymentStatus::class,
        ];
    }
}
