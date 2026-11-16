<?php

declare(strict_types=1);

namespace App\Services\ExternalAi;

use App\Exceptions\ExternalAi\ExternalAiException;
use App\Models\GenerationTask;

interface ExternalAiClientInterface
{
    /**
     * Generate the requested number of variations for the given task.
     *
     * @throws ExternalAiException
     */
    public function generate(GenerationTask $task): ExternalGenerationResult;
}
