<?php

namespace App\Models;

use App\Enums\MembershipDurationUnit;
use Database\Factories\MembershipPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $gym_id
 * @property string $name
 * @property int $duration
 * @property MembershipDurationUnit $duration_unit
 * @property string $price
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'gym_id',
    'name',
    'duration',
    'duration_unit',
    'price',
    'description',
    'is_active',
])]
class MembershipPlan extends Model
{
    /** @use HasFactory<MembershipPlanFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'duration_unit' => MembershipDurationUnit::Month->value,
        'is_active' => true,
    ];

    /** @return BelongsTo<Gym, $this> */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'duration' => 'integer',
            'duration_unit' => MembershipDurationUnit::class,
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
