<?php

namespace Drupal\dead_letter_queue_ui;

use Drupal\queue_ui\QueueUIInterface;

interface DeadLetterQueueUiInterface extends QueueUIInterface
{
    public function getDeadLetters(string $queueName): array;

    public function getNumberOfDeadLetters(string $queueName): int;
}
