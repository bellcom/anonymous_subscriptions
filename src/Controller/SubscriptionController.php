<?php

namespace Drupal\anonymous_subscriptions\Controller;

use Drupal\anonymous_subscriptions\DefaultService;
use Drupal\anonymous_subscriptions\Entity\Subscription;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SubscriptionController.
 */
class SubscriptionController extends ControllerBase {

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('anonymous_subscriptions.default')
    );
  }

  /**
   * Subscription verifying page title callback.
   */
  public function verifyTitle() {
    return $this->t('Subscription verifying');
  }

  /**
   * {@inheritdoc}
   */
  public function verify(Subscription $subscription, $code) {
    if ($subscription->verified->value == 1) {
      $status = $this->t('Your subscritpion already verified');
    }
    elseif (strcmp($subscription->code->value, $code) === 0) {
      $subscription->set('verified', TRUE);
      $subscription->save();
      $status = $this->t('Your subscription is confirmed');
      $this->subscriptionService->sendConfirmationMail($subscription);
    }
    else {
      $status = $this->t('We could not confirm your subscription');
    }
    return [
      '#theme' => 'anonymous_subscriptions_message',
      '#attributes' => ['class' => ['text']],
      '#message' => $status,
      '#subscription' => $subscription,
      '#link' => Link::fromTextAndUrl($this->t('Click here to return to homepage'), Url::fromRoute('<front>')),
    ];
  }

}
