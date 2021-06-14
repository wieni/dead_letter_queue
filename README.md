Dead Letter Queue
======================

[![Latest Stable Version](https://poser.pugx.org/wieni/dead_letter_queue/v/stable)](https://packagist.org/packages/wieni/dead_letter_queue)
[![Total Downloads](https://poser.pugx.org/wieni/dead_letter_queue/downloads)](https://packagist.org/packages/wieni/dead_letter_queue)
[![License](https://poser.pugx.org/wieni/dead_letter_queue/license)](https://packagist.org/packages/wieni/dead_letter_queue)

> A Drupal 8 module for separating queue items that can't be processed successfully.

## Why?
This package requires PHP 7.1 and Drupal 8.5 or higher. It can be installed using Composer:

```bash
 composer require wieni/dead_letter_queue
```

## How does it work?
### Configuration
To enable dead letter queue by default for all queues, add the following to settings.php:
```php
$settings['queue_default'] = 'dead_letter_queue.queue.database';
```

To enable dead letter queue for a specific queue instead, add the following to settings.php
```php
$settings['queue_service_[QUEUE_ID]'] = 'wmsubscription_dead_letter_queue.queue.database';
```

### Handling dead letters in queue workers
If you want to act upon dead letters in a custom queue workers, you can implement
[`DeadLetterQueueWorkerInterface`](src/Queue/DeadLetterQueueWorkerInterface.php). Inside `handleDeadLetter`, there are
a few things you can do to change the outcome of the queue item that is about to become a dead letter:
- throw a `DiscardDeadLetterException` to discard the queue item.
- throw a `RestoreDeadLetterException` to reset the amount of tries on the queue item.
- throw a `RequeueException` to immediately requeue the queue item.
- throw a `SuspendQueueException` to indicate there is a problem with the whole queue,
  releasing the item and skipping to the next queue.

If none of the above exceptions are thrown, the queue item will be placed in the dead letter queue and will not be processed again,
unless it's manually released.

Any other kind of exception is logged, but will not change the outcome of the queue item.

### Inspecting dead letters
Inspecting dead letters is possible using the `dead_letter_queue_ui` submodule. This module intergrates with the [Queue UI](https://www.drupal.org/project/queue_ui) module and allows you to see an overview of all dead letters per queue.

### Setting the maximum amount of tries
The maximum amount of tries can be set in the queue worker annotation:

```php
<?php

namespace Drupal\your_module\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * @QueueWorker(
 *   id = \Drupal\your_module\Plugin\QueueWorker\SomeQueueWorker::ID,
 *   title = @Translation("Some queue worker"),
 *   cron = {"time" = 60, "max_tries" = 10}
 * )
 */
class SomeQueueWorker extends QueueWorkerBase
{
    public const ID = 'your_module.some_queue_worker';

    public function processItem($data): void
    {
    }
}
```

This setting can also be changed through the interface when using the `dead_letter_queue_ui` submodule.

## Limitations
Only an implementation for database queues is provided. If you're using third party queue services like
[Redis](https://www.drupal.org/project/redis) or [AWS SQS](https://www.drupal.org/project/aws_sqs), this will not work.

You can't install the module and enable the new queue service in a single deploy. A
[Drupal core issue](https://www.drupal.org/project/drupal/issues/3208556) has been created, if you need to do this you
can always patch your project using the merge request from that issue.

## Changelog
All notable changes to this project will be documented in the
[CHANGELOG](CHANGELOG.md) file.

## Security
If you discover any security-related issues, please email
[security@wieni.be](mailto:security@wieni.be) instead of using the issue
tracker.

## License
Distributed under the MIT License. See the [LICENSE](LICENSE.md) file
for more information.
