<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when the AI provider cannot produce a usable lesson plan:
 * missing credentials, network/timeout failures, provider errors,
 * or malformed/empty responses.
 */
class AiLessonPlannerException extends RuntimeException {}
