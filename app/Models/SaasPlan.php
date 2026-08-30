<?php

namespace App\Models;

use App\Enums\SaasPlanInterval;
use Database\Factories\SaasPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $price
 * @property string $currency
 * @property SaasPlanInterval $billing_interval
 * @property int $trial_days
 * @property int|null $max_gyms
 * @property int|null $max_members
 * @property int|null $max_staff
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'slug',
    'description',
    'price',
    'currency',
    'billing_interval',
    'trial_days',
    'max_gyms',
    'max_members',
    'max_staff',
    'is_active',
    'sort_order',
])]
class SaasPlan extends Model
{
    /** @use HasFactory<SaasPlanFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'currency' => 'IDR',
        'billing_interval' => SaasPlanInterval::Monthly->value,
        'trial_days' => 14,
        'is_active' => true,
        'sort_order' => 0,
    ];

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'billing_interval' => SaasPlanInterval::class,
            'trial_days' => 'integer',
            'max_gyms' => 'integer',
            'max_members' => 'integer',
            'max_staff' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
