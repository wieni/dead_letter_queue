<?php

namespace Drupal\dead_letter_queue_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\dead_letter_queue_ui\DeadLetterQueueUiInterface;
use Drupal\queue_ui\QueueUIManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DeadLettersForm extends FormBase
{
    /** @var QueueUIManager */
    protected $queueUIManager;

    public static function create(ContainerInterface $container)
    {
        $instance = parent::create($container);
        $instance->queueUIManager = $container->get('plugin.manager.queue_ui');

        return $instance;
    }

    public function getFormId()
    {
        return 'dead_letter_queue_ui_dead_letters_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $queue_name = false)
    {
        $queueUi = $this->queueUIManager->fromQueueName($queue_name);

        if (!$queueUi instanceof DeadLetterQueueUiInterface) {
            throw new NotFoundHttpException();
        }

        $rows = [];
        foreach ($queueUi->getDeadLetters($queue_name) as $item) {
            $operations = [];

            foreach ($queueUi->getOperations() as $op => $title) {
                $operations[] = [
                    'title' => $title,
                    'url' => Url::fromRoute('queue_ui.inspect.' . $op, ['queue_name' => $queue_name, 'queue_item' => $item->item_id]),
                ];
            }

            $rows[] = [
                'id' => $item->item_id,
                'expires' => ($item->expire ? date(DATE_RSS, $item->expire) : $item->expire),
                'created' => date(DATE_RSS, $item->created),
                'tries' => $item->tries,
                'operations' => [
                    'data' => [
                        '#type' => 'dropbutton',
                        '#links' => $operations,
                    ],
                ],
            ];
        }

        return [
            'table' => [
                '#type' => 'table',
                '#header' => [
                    'id' => $this->t('Item ID'),
                    'expires' => $this->t('Expires'),
                    'created' => $this->t('Created'),
                    'tries' => $this->t('Tries'),
                    'operations' => $this->t('Operations'),
                ],
                '#rows' => $rows,
                '#empty' => $this->t("This queue doesn't have any dead letters."),
            ],
            'pager' => [
                '#type' => 'pager',
            ],
        ];
    }

    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
    }
}
