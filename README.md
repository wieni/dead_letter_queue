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

## Known issues
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
