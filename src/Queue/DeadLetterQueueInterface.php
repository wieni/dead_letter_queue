<?php

namespace Drupal\dead_letter_queue\Queue;

use Drupal\Core\Queue\QueueInterface;

interface DeadLetterQueueInterface extends QueueInterface
{
    public function resetItemTries(int $itemId): void;

    public function getMaxTries(): int;
}
