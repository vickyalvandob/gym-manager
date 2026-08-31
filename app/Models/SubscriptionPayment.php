<?php

namespace App\Models;

use App\Enums\SaasPlanInterval;
use App\Enums\SubscriptionPaymentStatus;
use Database\Factories\SubscriptionPaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $subscription_id
 * @property int $saas_plan_id
 * @property string $plan_name
 * @property string $amount
 * @property string $currency
 * @property SaasPlanInterval $billing_interval
 * @property string $reference_number
 * @property string $proof_path
 * @property SubscriptionPaymentStatus $status
 * @property Carbon $submitted_at
 * @property Carbon|null $reviewed_at
 * @property int|null $reviewed_by
 * @property string|null $review_notes
 * @property Carbon|null $period_starts_at
 * @property Carbon|null $period_ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Subscription $subscription
 * @property-read SaasPlan $plan
 * @property-read User|null $reviewer
 */
#[Fillable([
    'subscription_id',
    'saas_plan_id',
    'plan_name',
    'amount',
    'currency',
    'billing_interval',
    'reference_number',
    'proof_path',
    'status',
    'submitted_at',
    'reviewed_at',
    'reviewed_by',
    'review_notes',
    'period_starts_at',
    'period_ends_at',
])]
class SubscriptionPayment extends Model
{
    /** @use HasFactory<SubscriptionPaymentFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => SubscriptionPaymentStatus::Pending->value,
    ];

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @return BelongsTo<SaasPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SaasPlan::class, 'saas_plan_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'billing_interval' => SaasPlanInterval::class,
            'status' => SubscriptionPaymentStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'period_starts_at' => 'datetime',
            'period_ends_at' => 'datetime',
        ];
    }
}
