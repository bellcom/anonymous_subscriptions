<?php

namespace Drupal\anonymous_subscriptions\Form;

use Drupal\anonymous_subscriptions\DefaultService;
use Drupal\anonymous_subscriptions\Entity\Subscription;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use League\Container\Exception\NotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Subscribe form.
 */
class SubscribeForm extends FormBase {

  /**
   * Anonymous subscription service.
   *
   * @var \Drupal\anonymous_subscriptions\DefaultService
   */
  protected $subscriptionService;

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The flood instance.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * Anonymous subscription settings configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\anonymous_subscriptions\DefaultService $subscription_service
   *   The anonymous subscription service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DefaultService $subscription_service, EntityTypeManager $entityTypeManager, FloodInterface $flood) {
    $this->setConfigFactory($config_factory);
    $this->settings = $config_factory->get(SettingsForm::$configName);
    $this->subscriptionService = $subscription_service;
    $this->entityTypeManager = $entityTypeManager;
    $this->flood = $flood;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('anonymous_subscriptions.default'),
      $container->get('entity_type.manager'),
      $container->get('flood')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymous_subscriptions_subscribe_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle($type = NULL) {
    if ($type) {
      $type_label = $this->entityTypeManager
        ->getStorage('node_type')
        ->load($type)
        ->label();
      return $this->t('@type content subscription', [
        '@type' => $type_label,
      ]);
    }
    else {
      return $this->t('Subscription');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL) {
    $config = $this->configFactory->get(SettingsForm::$configName);
    $valid_types = $config->get('anonymous_subscriptions_node_types') ?: [];
    if (!empty($type)) {
      $existing_types = node_type_get_names();
      if (empty($valid_types[$type]) || empty($existing_types[$type])) {
        throw new NotFoundHttpException();
      }
    }

    if ($type) {
      $type_label = $this->entityTypeManager
        ->getStorage('node_type')
        ->load($type)
        ->label();
      $description = $this->t('Subscribe for updates on content type @type.', [
        '@type' => $type_label,
      ]);
    }
    else {
      $description = $this->t('Subscribe for updates on all content.');
    }
    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $description . '</p>',
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Subscribe'),
    ];

    $form['type'] = [
      '#type' => 'hidden',
      '#default_value' => $type,
    ];

    $form['#tree'] = TRUE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $window = $this->settings->get('anonymous_subscriptions_limit_window');
    $limit = $this->settings->get('anonymous_subscriptions_ip_limit');
    if (!$this->flood->isAllowed('failed_subscribe_attempt_ip', $limit, $window)) {
      $form_state->setError($form['email'], $this->t('You have made too many attempts to subscribe.'));
      return;
    }
    $this->flood->register('failed_subscribe_attempt_ip', $window);
    $email = $form_state->getValue('email');
    $type = $form_state->getValue('type');

    $query = \Drupal::entityQuery('anonymous_subscription')
      ->condition('email', $email);
    if (!empty($type)) {
      $query->condition('type', $type);
    }
    $ids = $query->execute();
    if (!empty(Subscription::loadMultiple($ids))) {
      $form_state->setError($form['email'], $this->t('Email address @email already subscribed.', [
        '@email' => $email,
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    $type = $form_state->getValue('type');
    $verification_required = $this->settings->get('anonymous_subscriptions_verify');

    /** @var \Drupal\anonymous_subscriptions\Entity\Subscription $subscription */
    $subscription = Subscription::create([
      'email' => $email,
      'code' => Crypt::randomBytesBase64(20),
      'type' => $type,
      'verified' => !$verification_required,
    ]);
    $subscription->save();

    if ($verification_required) {
      $this->subscriptionService->sendVerificationMail($subscription);
      $status_message = $this->t('Thanks for subscribing, in order to receive updates, you will need to verify your email address by clicking on a link in an email we just sent you.');
    }
    else {
      $this->subscriptionService->sendConfirmationMail($subscription);
      $status_message = $this->t('You are now subscribed to receive updates.');
    }

    $this->messenger()->addStatus($status_message);
  }

}
