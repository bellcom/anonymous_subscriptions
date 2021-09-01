<?php

namespace Drupal\anonymous_subscriptions\EventSubscriber;

use Drupal\anonymous_subscriptions\DefaultService;
use Drupal\scheduler\SchedulerEvent;
use Drupal\scheduler\SchedulerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handle scheduler events.
 */
class SchedulerEventSubscriber implements EventSubscriberInterface {

  /**
   * Anonymous subscription service.
   *
   * @var \Drupal\anonymous_subscriptions\DefaultService
   */
  protected $subscriptionService;

  /**
   * Constructs a new SubscriptionController.
   *
   * @param \Drupal\anonymous_subscriptions\DefaultService $subscriptionService
   *   Default subscription service.
   */
  public function __construct(DefaultService $subscriptionService) {
    $this->subscriptionService = $subscriptionService;
  }

  /**
   * Operations to perform after Scheduler publishes a node immediately.
   *
   * This is during the edit process, not via cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The event being acted on.
   */
  public function publish(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    $this->subscriptionService->addPendingEmails($node);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // The values in the arrays give the function names above.
    $events = [];
    if (\Drupal::service('module_handler')->moduleExists('scheduler')) {
      $events[SchedulerEvents::PUBLISH][] = ['publish'];
    }
    return $events;
  }

}
