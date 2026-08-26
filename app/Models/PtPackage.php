<?php

namespace App\Models;

use Database\Factories\PtPackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $gym_id
 * @property string $name
 * @property int $session_count
 * @property int|null $validity_days
 * @property string $price
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'gym_id',
    'name',
    'session_count',
    'validity_days',
    'price',
    'description',
    'is_active',
])]
class PtPackage extends Model
{
    /** @use HasFactory<PtPackageFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'is_active' => true,
    ];

    /** @return BelongsTo<Gym, $this> */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /** @return HasMany<MemberPtPackage, $this> */
    public function memberPtPackages(): HasMany
    {
        return $this->hasMany(MemberPtPackage::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'session_count' => 'integer',
            'validity_days' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
