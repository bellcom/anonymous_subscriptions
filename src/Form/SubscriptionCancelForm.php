<?php

namespace Drupal\anonymous_subscriptions\Form;

use Drupal\anonymous_subscriptions\DefaultService;
use Drupal\anonymous_subscriptions\Entity\Subscription;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Subscription cancel form.
 */
class SubscriptionCancelForm extends ConfirmFormBase {

  /**
   * Anonymous subscription service.
   *
   * @var \Drupal\anonymous_subscriptions\DefaultService
   */
  protected $subscriptionService;

  /**
   * Subscription entity.
   *
   * @var \Drupal\anonymous_subscriptions\Entity\Subscription
   */
  protected $subscription;

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SubscriptionCancelForm object.
   *
   * @param \Drupal\anonymous_subscriptions\DefaultService $subscription_service
   *   Subscription server.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(DefaultService $subscription_service, EntityTypeManager $entityTypeManager) {
    $this->subscriptionService = $subscription_service;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('anonymous_subscriptions.default'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Subscription $subscription = NULL, $code = NULL) {
    if (strcmp($subscription->code->value, $code) === 0) {
      $this->subscription = $subscription;
      $form = parent::buildForm($form, $form_state);
      $form['description'] = [
        '#type' => 'container',
        'markup' => [
          '#markup' => '<p>' . $this->getDescription() . '</p>',
        ],
      ];

      $form['actions']['cancel']['#attributes'][] = 'button';
      return $form;
    }
    else {
      $url = Url::fromRoute('<front>');
      $response = new RedirectResponse($url->toString());
      $response->send();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->subscription->delete();
    $url = Url::fromRoute('<front>');
    $response = new RedirectResponse($url->toString());
    $response->send();
    $message = $this->t('Your email was removed from subscription for updates on all content.');
    if ($this->subscription->type->value) {
      $type_label = $this->entityTypeManager
        ->getStorage('node_type')
        ->load($this->subscription->type->value)
        ->label();
      $message = $this->t('Your email was removed from subscription for updates on content type @type', [
        '@type' => $type_label,
      ]);
    }
    $this->messenger()->addStatus($message);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "anonymous_subscriptions_confirm_cancel";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->getTitle();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $description = $this->t('Do you want to cancel your subscription on all content?');
    if ($this->subscription->type->value) {
      $type_label = $this->entityTypeManager
        ->getStorage('node_type')
        ->load($this->subscription->type->value)
        ->label();
      $description = $this->t('Do you want to cancel your subscription on content type @type?', [
        '@type' => $type_label,
      ]);
    }
    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t('Cancelling subscription');
  }

}
