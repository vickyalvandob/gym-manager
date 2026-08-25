<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $gym_id
 * @property int $member_id
 * @property int $member_membership_id
 * @property string $invoice_number
 * @property string $amount
 * @property PaymentMethod|null $method
 * @property PaymentStatus $status
 * @property Carbon|null $paid_at
 * @property string|null $notes
 * @property int|null $received_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'gym_id',
    'member_id',
    'member_membership_id',
    'invoice_number',
    'amount',
    'method',
    'status',
    'paid_at',
    'notes',
    'received_by_id',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
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

    /** @return BelongsTo<MemberMembership, $this> */
    public function memberMembership(): BelongsTo
    {
        return $this->belongsTo(MemberMembership::class);
    }

    /** @return BelongsTo<User, $this> */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }
}
