<?php

namespace Drupal\anonymous_subscriptions\Plugin\QueueWorker;

use Drupal\anonymous_subscriptions\DefaultService;
use Drupal\anonymous_subscriptions\Form\SettingsForm;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Anonymous subscription queue worker.
 *
 * @QueueWorker(
 * id = "anonymous_subscriptions_queue",
 * title = "Anonymous subscription email sending queue worker",
 * cron = {"time" = 10}
 * )
 */
class AnonymousSubscriptionQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Anonymous subscription service.
   *
   * @var \Drupal\anonymous_subscriptions\DefaultService
   */
  protected $subscriptionService;

  /**
   * Hour of the day, when subscriptions will be sent.
   *
   * @var int
   */
  protected $scheduledHour;

  /**
   * Constructs a new ScheduledTransitionJob.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\anonymous_subscriptions\DefaultService $subscriptionService
   *   Default subscription service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, DefaultService $subscriptionService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->subscriptionService = $subscriptionService;

    $this->scheduledHour = \Drupal::config(SettingsForm::$configName)->get('anonymous_subscriptions_scheduled_hour');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('anonymous_subscriptions.default')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function processItem($data) {
    $lastCheck = \Drupal::state()->get('anonymous_subscriptions.last_check');
    $hourNow = date('H');
    $scheduledHour = $this->scheduledHour;

    // Run is now is scheduled hours, or if now is after scheduled hours, but the last check was more than 1h ago.
    if ($scheduledHour == -1 || $hourNow == $scheduledHour || ($hourNow > $scheduledHour && time() - $lastCheck > 3600)) {
      $this->subscriptionService->sendMail($data);
    }
    else {
      \Drupal::state()->set('anonymous_subscriptions.last_check', \Drupal::time()->getRequestTime());
      throw new SuspendQueueException(t('Queue will not be processed. Current hour: @current_hour, scheduled hour:
        @scheduled_hour', ['@current_hour' => $hourNow, '@scheduled_hour' => $scheduledHour]));
    }
  }
}
