<?php

namespace Drupal\dead_letter_queue\Queue;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueDatabaseFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;

class DeadLetterQueueDatabaseFactory extends QueueDatabaseFactory
{
    /** @var QueueWorkerManagerInterface */
    protected $queueManager;
    /** @var ConfigFactoryInterface */
    protected $configFactory;
    /** @var LoggerChannelInterface */
    protected $logger;

    public function __construct(
        Connection $connection,
        QueueWorkerManagerInterface $queueManager,
        ConfigFactoryInterface $configFactory,
        LoggerChannelInterface $logger
    ) {
        parent::__construct($connection);
        $this->queueManager = $queueManager;
        $this->configFactory = $configFactory;
        $this->logger = $logger;
    }

    public function get($name)
    {
        return new DeadLetterDatabaseQueue(
            $name,
            $this->connection,
            $this->queueManager,
            $this->configFactory,
            $this->logger
        );
    }
}
