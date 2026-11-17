<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GenerationStatus;
use Database\Factories\GenerationTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property int $user_id
 * @property GenerationStatus $status
 * @property Carbon|null $quota_refunded_at
 */
#[Fillable([
    'user_id',
    'type',
    'prompt',
    'variations',
    'status',
    'external_request_id',
    'error_message',
    'quota_refunded_at',
])]
class GenerationTask extends Model
{
    /** @use HasFactory<GenerationTaskFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GenerationStatus::class,
            'quota_refunded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<GeneratedAsset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(GeneratedAsset::class);
    }
}
