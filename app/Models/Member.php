<?php

namespace App\Models;

use App\Enums\MemberGender;
use App\Enums\MemberStatus;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $gym_id
 * @property string $member_number
 * @property string $name
 * @property string $phone
 * @property string|null $email
 * @property MemberGender|null $gender
 * @property Carbon|null $birth_date
 * @property string|null $address
 * @property string|null $photo
 * @property string|null $emergency_contact
 * @property MemberStatus $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'gym_id',
    'member_number',
    'name',
    'phone',
    'email',
    'gender',
    'birth_date',
    'address',
    'photo',
    'emergency_contact',
    'status',
    'notes',
])]

class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => MemberStatus::Active->value,
    ];

    /**
     * @return BelongsTo<Gym, $this>
     */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => MemberGender::class,
            'birth_date' => 'date',
            'status' => MemberStatus::class,
        ];
    }
}
