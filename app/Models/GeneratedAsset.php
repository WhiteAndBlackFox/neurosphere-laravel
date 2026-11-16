<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['generation_task_id', 'external_id', 'url', 'mime_type', 'size_bytes'])]
class GeneratedAsset extends Model
{
    /**
     * @return BelongsTo<GenerationTask, $this>
     */
    public function generationTask(): BelongsTo
    {
        return $this->belongsTo(GenerationTask::class);
    }
}
