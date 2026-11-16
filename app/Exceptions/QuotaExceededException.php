<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a user has no remaining generation quota for the current period.
 */
final class QuotaExceededException extends RuntimeException {}
