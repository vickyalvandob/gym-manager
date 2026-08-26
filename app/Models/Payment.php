<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
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
 * @property PaymentType $type
 * @property int|null $member_membership_id
 * @property int|null $member_pt_package_id
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
    'type',
    'member_membership_id',
    'member_pt_package_id',
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

    /** @var array<string, mixed> */
    protected $attributes = [
        'type' => PaymentType::Membership->value,
        'status' => PaymentStatus::Pending->value,
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

    /** @return BelongsTo<MemberMembership, $this> */
    public function memberMembership(): BelongsTo
    {
        return $this->belongsTo(MemberMembership::class);
    }

    /** @return BelongsTo<MemberPtPackage, $this> */
    public function memberPtPackage(): BelongsTo
    {
        return $this->belongsTo(MemberPtPackage::class);
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
            'type' => PaymentType::class,
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }
}
