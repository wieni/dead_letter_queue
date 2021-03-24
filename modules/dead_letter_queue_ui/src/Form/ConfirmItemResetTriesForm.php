<?php

namespace Drupal\dead_letter_queue_ui\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Url;
use Drupal\dead_letter_queue\Queue\DeadLetterQueueInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConfirmItemResetTriesForm extends ConfirmFormBase
{
    /** @var string */
    protected $queueName;
    /** @var string */
    protected $queueItem;
    /** @var QueueFactory */
    protected $queueFactory;

    public static function create(ContainerInterface $container)
    {
        $instance = parent::create($container);
        $instance->messenger = $container->get('messenger');
        $instance->queueFactory = $container->get('queue');

        return $instance;
    }

    public function getQuestion()
    {
        return $this->t('Are you sure you want to reset the amount of tries of queue item %queue_item?', ['%queue_item' => $this->queueItem]);
    }

    public function getCancelUrl()
    {
        return Url::fromRoute('queue_ui.inspect', ['queue_name' => $this->queueName]);
    }

    public function getFormId()
    {
        return 'queue_ui_confirm_item_reset_tries_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $queue_name = false, $queue_item = false)
    {
        $this->queueName = $queue_name;
        $this->queueItem = $queue_item;

        $queueUi = $this->queueFactory->get($this->queueName);

        if (!$queueUi instanceof DeadLetterQueueInterface) {
            throw new NotFoundHttpException();
        }

        return parent::buildForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $formState): void
    {
        /** @var DeadLetterQueueInterface $queue */
        $queue = $this->queueFactory->get($this->queueName);
        $queue->resetItemTries($this->queueItem);

        $this->messenger->addMessage(sprintf('Reset tries of queue item %s', $this->queueItem));
        $formState->setRedirectUrl(Url::fromRoute('queue_ui.inspect', ['queue_name' => $this->queueName]));
    }
}
