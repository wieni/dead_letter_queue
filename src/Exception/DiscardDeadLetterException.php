<?php

namespace Drupal\dead_letter_queue\Exception;

/**
 * Throw this exception to discard the item instead of storing it as a dead letter.
 */
class DiscardDeadLetterException extends \RuntimeException
{
}
