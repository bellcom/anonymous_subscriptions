<?php

namespace Drupal\anonymous_subscriptions\Plugin\Block;

use Drupal\anonymous_subscriptions\Form\SettingsForm;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract class for subscribe block.
 */
abstract class SubscribeBlockBase extends BlockBase implements ContainerFactoryPluginInterface {

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

}
