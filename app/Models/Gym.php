<?php

namespace App\Models;

use App\Enums\GymStatus;
use Database\Factories\GymFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property GymStatus $status
 * @property string $timezone
 * @property string $currency
 * @property int $membership_expiry_warning_days
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'slug',
    'status',
    'timezone',
    'currency',
    'membership_expiry_warning_days',
])]
class Gym extends Model
{
    /** @use HasFactory<GymFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => GymStatus::Active->value,
        'timezone' => 'Asia/Jakarta',
        'currency' => 'IDR',
        'membership_expiry_warning_days' => 7,
    ];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GymStatus::class,
            'membership_expiry_warning_days' => 'integer',
        ];
    }
}
