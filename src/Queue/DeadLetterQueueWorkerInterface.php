<?php

namespace Drupal\dead_letter_queue\Queue;

use Drupal\Core\Queue\QueueWorkerInterface;

interface DeadLetterQueueWorkerInterface extends QueueWorkerInterface
{
    public function handleDeadLetter($data);
}
