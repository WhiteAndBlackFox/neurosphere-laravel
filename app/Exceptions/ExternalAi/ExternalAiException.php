<?php

declare(strict_types=1);

namespace App\Exceptions\ExternalAi;

use RuntimeException;

/**
 * Thrown when the external AI provider fails to generate a result.
 */
final class ExternalAiException extends RuntimeException {}
