<?php

namespace Drupal\dead_letter_queue_ui\Plugin\QueueUI;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\dead_letter_queue_ui\DeadLetterQueueUiInterface;
use Drupal\queue_ui\Plugin\QueueUI\DatabaseQueue;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @QueueUI(
 *     id = "dead_letter_database_queue",
 *     class_name = "DeadLetterDatabaseQueue"
 * )
 */
class DeadLetterDatabaseQueueUi extends DatabaseQueue implements DeadLetterQueueUiInterface
{
    /** @var Connection */
    protected $database;
    /** @var QueueFactory */
    protected $queueFactory;

    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        /** @var self $instance */
        $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
        $instance->database = $container->get('database');
        $instance->queueFactory = $container->get('queue');

        return $instance;
    }

    public function getOperations()
    {
        return [
            'view' => $this->t('View'),
            'release' => $this->t('Release'),
            'delete' => $this->t('Delete'),
            'reset_tries' => $this->t('Reset tries'),
        ];
    }

    public function getItems($queue_name)
    {
        $maxTries = $this->queueFactory->get($queue_name)->getMaxTries();

        $query = $this->database->select('queue', 'q');
        $query->addField('q', 'item_id');
        $query->addField('q', 'expire');
        $query->addField('q', 'created');
        $query->addField('q', 'tries');
        $query->condition('q.name', $queue_name);
        $query->condition('q.tries', $maxTries, '<');
        $query = $query->extend(PagerSelectExtender::class);
        $query = $query->limit(25);

        return $query->execute();
    }

    public function getDeadLetters(string $queueName): array
    {
        $query = $this->getDeadLettersQuery($queueName);
        $query = $query->extend(PagerSelectExtender::class);
        $query = $query->limit(25);

        return $query->execute()->fetchAll();
    }

    public function getNumberOfDeadLetters(string $queueName): int
    {
        return (int) $this->getDeadLettersQuery($queueName)
            ->countQuery()
            ->execute()
            ->fetch(\PDO::FETCH_COLUMN);
    }

    public function loadItem($item_id)
    {
        // Load the specified queue item from the queue table.
        $query = $this->database->select('queue', 'q')
            ->fields('q', ['item_id', 'name', 'data', 'expire', 'created', 'tries'])
            ->condition('q.item_id', $item_id)
            ->range(0, 1); // item id should be unique

        return $query->execute()->fetchObject();
    }

    protected function getDeadLettersQuery(string $queueName): SelectInterface
    {
        $maxTries = $this->queueFactory->get($queueName)->getMaxTries();

        $query = $this->database->select('queue', 'q');
        $query->addField('q', 'item_id');
        $query->addField('q', 'expire');
        $query->addField('q', 'created');
        $query->addField('q', 'tries');
        $query->condition('q.name', $queueName);
        $query->condition('q.tries', $maxTries, '>=');

        return $query;
    }
}
