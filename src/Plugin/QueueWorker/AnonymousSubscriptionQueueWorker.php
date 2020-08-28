<?php

namespace Drupal\anonymous_subscriptions\Plugin\QueueWorker;

use Drupal\anonymous_subscriptions\DefaultService;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
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
    $this->subscriptionService->sendMail($data);
  }

}
