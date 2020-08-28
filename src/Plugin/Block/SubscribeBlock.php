<?php

namespace Drupal\anonymous_subscriptions\Plugin\Block;

use Drupal\anonymous_subscriptions\Form\SettingsForm;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'SubscribeBlock' block.
 *
 * @Block(
 *  id = "subscribe_block",
 *  admin_label = @Translation("Anonymous subscription"),
 * )
 */
class SubscribeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Anonymous subscription settings configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->settings = $container->get('config.factory')->get(SettingsForm::$configName);
    $instance->formBuilder = $container->get('form_builder');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'node_type',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $valid_types = $this->settings->get('anonymous_subscriptions_node_types');
    $types = node_type_get_names();
    foreach ($types as $type_name => $type_label) {
      if (empty($valid_types[$type_name])) {
        unset($types[$type_name]);
      }
    }

    $form['node_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Node type'),
      '#description' => $this->t('Select node type for subscription'),
      '#options' => $types,
      '#default_value' => empty($this->configuration['node_type']) ? NULL : $this->configuration['node_type'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['node_type'] = $form_state->getValue('node_type');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'subscribe_block';
    $type = empty($this->configuration['node_type']) ? NULL : $this->configuration['node_type'];
    $form = $this->formBuilder->getForm('\Drupal\anonymous_subscriptions\Form\SubscribeForm', $type);
    $build['form'][] = $form;

    return $build;
  }

}
