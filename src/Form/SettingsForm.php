<?php

namespace Drupal\anonymous_subscriptions\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings forms class.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Form config name.
   *
   * @var string
   */
  public static $configName = 'anonymous_subscriptions.settings';

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [SettingsForm::$configName];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'anonymous_subscription_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(SettingsForm::$configName);

    $form['subscription_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Subscription settings'),
    ];

    $form['subscription_fieldset']['anonymous_subscriptions_verify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Emails must be verified'),
      '#default_value' => $config->get('anonymous_subscriptions_verify'),
    ];

    $form['subscription_fieldset']['anonymous_subscriptions_send_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send by default'),
      '#default_value' => $config->get('anonymous_subscriptions_send_default'),
      '#description' => $this->t('Tick this is you want the default option to be send emails.'),
    ];

    $form['subscription_fieldset']['anonymous_subscriptions_limit_window'] = [
      '#type' => 'select',
      '#title' => $this->t('How long to block flood attempts.'),
      '#default_value' => $config->get('anonymous_subscriptions_limit_window'),
      '#options' => [
        60 => '1 minute',
        300 => '5 minutes',
        600 => '10 minutes',
      ],
    ];

    $form['subscription_fieldset']['anonymous_subscriptions_ip_limit'] = [
      '#type' => 'select',
      '#title' => $this->t('How many attempts before blocking a user from a single IP.'),
      '#default_value' => $config->get('anonymous_subscriptions_ip_limit'),
      '#options' => [
        5 => '5 attempts',
        10 => '10 attempts',
        20 => '20 attempts',
      ],
    ];

    $form['subscription_fieldset']['anonymous_subscriptions_node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Node types enabled for subscription notifications'),
      '#options' => node_type_get_names(),
      '#default_value' => $config->get('anonymous_subscriptions_node_types') ?: [],
    ];

    $form['subscription_fieldset']['anonymous_subscriptions_taxonomy_terms_depth'] = [
      '#type' => 'number',
      '#title' => $this->t('Max depth for taxonomy terms tree'),
      '#description' => $this->t('Empty value will show all terms.'),
      '#default_value' => $config->get('anonymous_subscriptions_taxonomy_terms_depth'),
    ];

    $form['subscription_fieldset']['anonymous_subscriptions_lookup_parent_taxonomy_terms'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send notification on parent taxonomy terms subscriptions'),
      '#description' => $this->t('Subscription notification would be sent if there were updated content grouped by one of the child terms.'),
      '#default_value' => $config->get('anonymous_subscriptions_lookup_parent_taxonomy_terms'),
    ];

    $form['subscription_fieldset']['anonymous_subscriptions_user_consent_page'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('User consent page'),
      '#description' => $this->t('Providing the page here will force user to agree to user consent first before submiting the subscription form.'),
      '#target_type' => 'node',
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['os2web_page'],
      ],
      '#default_value' => ($config->get('anonymous_subscriptions_user_consent_page')) ? Node::load($config->get('anonymous_subscriptions_user_consent_page')) : NULL,
    ];

    $form['email_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General email settings'),
    ];

    $form['email_fieldset']['anonymous_subscriptions_sender'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender'),
      '#description' => $this->t('Subscription send from. If empty default site email will be used.'),
      '#default_value' => $config->get('anonymous_subscriptions_sender'),
    ];

    $form['email_fieldset']['anonymous_subscriptions_subject_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject text'),
      '#description' => $this->t('Tokens are available'),
      '#default_value' => $config->get('anonymous_subscriptions_subject_text'),
    ];

    $form['email_fieldset']['anonymous_subscriptions_body_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body text'),
      '#description' => $this->t('Tokens are available'),
      '#default_value' => $config->get('anonymous_subscriptions_body_text'),
    ];

    if ($this->moduleHandler->moduleExists('token')) {
      $form['email_fieldset']['token_help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['site', 'node'],
      ];
    }

    $nodeTypes = $config->get('anonymous_subscriptions_node_types');
    foreach ($nodeTypes as $typeMachineName => $enabled) {
      if ($enabled) {
        $form["email_fieldset_type_$typeMachineName"] = [
          '#type' => 'details',
          '#title' => $this->t('Email settings override for: %type', ['%type' => $typeMachineName]),
        ];

        $form["email_fieldset_type_$typeMachineName"]["anonymous_subscriptions_subject_text_$typeMachineName"] = [
          '#type' => 'textfield',
          '#title' => $this->t('Subject text'),
          '#description' => $this->t('Leave empty to use default setting. Tokens are available'),
          '#default_value' => $config->get("anonymous_subscriptions_subject_text_$typeMachineName"),
        ];

        $form["email_fieldset_type_$typeMachineName"]["anonymous_subscriptions_body_text_$typeMachineName"] = [
          '#type' => 'textarea',
          '#title' => $this->t('Body text'),
          '#description' => $this->t('Leave empty to use default setting. Tokens are available'),
          '#default_value' => $config->get("anonymous_subscriptions_body_text_$typeMachineName"),
        ];

        if ($this->moduleHandler->moduleExists('token')) {
          $form["email_fieldset_type_$typeMachineName"]['token_help'] = [
            '#theme' => 'token_tree_link',
            '#token_types' => ['site', 'node'],
          ];
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $confObject = $this->config(SettingsForm::$configName);
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'anonymous_subscriptions_') !== 0) {
        continue;
      }
      $confObject->set($key, $value);
    }
    $confObject->save();
    parent::submitForm($form, $form_state);
  }

}
