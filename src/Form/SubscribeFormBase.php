<?php

namespace Drupal\anonymous_subscriptions\Form;

use Drupal\anonymous_subscriptions\DefaultService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract class for subscribe forms.
 */
abstract class SubscribeFormBase extends FormBase {

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
   * Anonymous subscription settings configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * The flood instance.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $window = $this->settings->get('anonymous_subscriptions_limit_window');
    $limit = $this->settings->get('anonymous_subscriptions_ip_limit');
    if (!$this->flood->isAllowed('failed_subscribe_attempt_ip', $limit, $window)) {
      $form_state->setError($form['email'], $this->t('You have made too many attempts to subscribe.'));
      return;
    }
    $this->flood->register('failed_subscribe_attempt_ip', $window);
  }

  public function appendUserConsentCheckbox(&$form) {
    if ($this->settings->get('anonymous_subscriptions_user_consent_page')) {
      $node = Node::load($this->settings->get('anonymous_subscriptions_user_consent_page'));
      $url = $node->toUrl('canonical', ['attributes' => ['target' => '_blank']])->setAbsolute();

      $form['user_consent'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Check the box to give consent to %link', ['%link' => Link::fromTextAndUrl($node->label(), $url)->toString()]),
        '#required' => TRUE,
      ];
    }
  }
}
