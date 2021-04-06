<?php

namespace Drupal\dead_letter_queue\Queue;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\dead_letter_queue\Exception\DiscardDeadLetterException;
use Drupal\dead_letter_queue\Exception\RestoreDeadLetterException;

class DeadLetterDatabaseQueue extends DatabaseQueue implements DeadLetterQueueInterface
{
    /** @var QueueWorkerManagerInterface */
    protected $queueManager;
    /** @var ConfigFactoryInterface */
    protected $configFactory;
    /** @var LoggerChannelInterface */
    protected $logger;

    public function __construct(
        $name,
        Connection $connection,
        QueueWorkerManagerInterface $queueManager,
        ConfigFactoryInterface $configFactory,
        LoggerChannelInterface $logger
    ) {
        parent::__construct($name, $connection);
        $this->queueManager = $queueManager;
        $this->configFactory = $configFactory;
        $this->logger = $logger;
    }

    public function numberOfItems()
    {
        $maxTries = $this->getMaxTries();
        $query = 'SELECT COUNT(item_id) FROM {' . static::TABLE_NAME . '} WHERE name = :name AND tries < :max_tries';

        try {
            return (int) $this->connection->query($query, [':name' => $this->name, ':max_tries' => $maxTries])
                ->fetchField();
        } catch (\Exception $e) {
            $this->catchException($e);
            // If there is no table there cannot be any items.
            return 0;
        }
    }

    public function claimItem($lease_time = 30)
    {
        $maxTries = $this->getMaxTries();
        $queueWorker = $this->queueManager->createInstance($this->name);
        $isDeadLetter = $queueWorker instanceof DeadLetterQueueWorkerInterface;

        // Claim an item by updating its expire fields. If claim is not successful
        // another thread may have claimed the item in the meantime. Therefore loop
        // until an item is successfully claimed or we are reasonably sure there
        // are no unclaimed items left.
        while (true) {
            try {
                $query = 'SELECT data, created, item_id, tries FROM {' . static::TABLE_NAME . '} q WHERE expire = 0 AND name = :name AND tries < :max_tries ORDER BY created, item_id ASC';
                $item = $this->connection->queryRange($query, 0, 1, [':name' => $this->name, ':max_tries' => $maxTries])->fetchObject();
            } catch (\Exception $e) {
                $this->catchException($e);
            }

            // If the table does not exist there are no items currently available to
            // claim.
            if (empty($item)) {
                return false;
            }

            // Try to update the item. Only one thread can succeed in UPDATEing the
            // same row. We cannot rely on REQUEST_TIME because items might be
            // claimed by a single consumer which runs longer than 1 second. If we
            // continue to use REQUEST_TIME instead of the current time(), we steal
            // time from the lease, and will tend to reset items before the lease
            // should really expire.
            $update = $this->connection->update(static::TABLE_NAME)
                ->fields([
                    'expire' => time() + $lease_time,
                    'tries' => $item->tries + 1,
                ])
                ->condition('item_id', $item->item_id)
                ->condition('expire', 0);
            // If there are affected rows, this update succeeded.
            if ($update->execute()) {
                $item->data = unserialize($item->data);

                if ($isDeadLetter && $item->tries + 1 >= $maxTries) {
                    $this->logger->error('Queue item @queueItemId from queue %queueName was moved to the dead letter queue after @tries tries.', [
                        '@queueItemId' => $item->item_id,
                        '%queueName' => $this->name,
                        '@tries' => $item->tries,
                    ]);

                    try {
                        $queueWorker->handleDeadLetter($item->data);
                    } catch (DiscardDeadLetterException $e) {
                        $this->deleteItem($item);
                    } catch (RestoreDeadLetterException $e) {
                        $this->resetItemTries($item->item_id);
                    } catch (RequeueException $e) {
                        // The worker requested the task be immediately requeued.
                        $this->releaseItem($item);
                    } catch (SuspendQueueException $e) {
                        // If the worker indicates there is a problem with the whole queue,
                        // release the item and skip to the next queue.
                        $this->releaseItem($item);

                        watchdog_exception('cron', $e);

                        // Skip to the next queue.
                        return false;
                    } catch (\Exception $e) {
                        // In case of any other kind of exception, log it and leave the item
                        // in the queue to be processed again later.
                        watchdog_exception('cron', $e);
                    }

                    continue;
                }

                return $item;
            }
        }
    }

    public function releaseItem($item)
    {
        try {
            $update = $this->connection->update(static::TABLE_NAME)
                ->fields([
                    'expire' => 0,
                    'tries' => $item->tries - 1,
                ])
                ->condition('item_id', $item->item_id);
            return $update->execute();
        } catch (\Exception $e) {
            $this->catchException($e);
            // If the table doesn't exist we should consider the item released.
            return true;
        }
    }

    public function schemaDefinition()
    {
        $definition = parent::schemaDefinition();
        $definition['fields']['tries'] = [
            'type' => 'int',
            'not null' => true,
            'default' => 0,
            'description' => 'Amount of times processing has been attempted.',
        ];

        return $definition;
    }

    public function resetItemTries(int $itemId): void
    {
        $this->connection->update('queue')
            ->condition('item_id', $itemId)
            ->fields(['tries' => 0])
            ->execute();
    }

    public function getMaxTries(): int
    {
        $definition = $this->queueManager->getDefinition($this->name);
        $config = $this->configFactory->get('dead_letter_queue.settings');

        foreach ($config->get('max_tries') ?? [] as $info) {
            if (!isset($info['queue_name'], $info['max_tries'])) {
                continue;
            }

            if ($info['queue_name'] === $this->name) {
                return $info['max_tries'];
            }
        }

        if (isset($definition['cron']['max_tries']) && is_int($definition['cron']['max_tries'])) {
            return $definition['cron']['max_tries'];
        }

        return 10;
    }
}
