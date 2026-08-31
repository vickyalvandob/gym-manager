<?php

namespace App\Models;

use Database\Factories\PlatformBillingSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $bank_name
 * @property string|null $account_name
 * @property string|null $account_number
 * @property string|null $instructions
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $updatedBy
 */
#[Fillable([
    'bank_name',
    'account_name',
    'account_number',
    'instructions',
    'updated_by',
])]
class PlatformBillingSetting extends Model
{
    /** @use HasFactory<PlatformBillingSettingFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isConfigured(): bool
    {
        return filled($this->bank_name)
            && filled($this->account_name)
            && filled($this->account_number);
    }
}
