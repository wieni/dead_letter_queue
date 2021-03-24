<?php

namespace Drupal\dead_letter_queue\Exception;

/**
 * Throw this exception to restore the item instead to the regular queue.
 */
class RestoreDeadLetterException extends \RuntimeException
{
}
