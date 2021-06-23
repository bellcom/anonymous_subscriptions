<?php

namespace Drupal\anonymous_subscriptions\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'SubscribeTaxonomyTermsBlock' block.
 *
 * @Block(
 *  id = "subscribe_taxonomy_terms_block",
 *  admin_label = @Translation("Anonymous taxonomy terms subscription"),
 * )
 */
class SubscribeTaxonomyTermsBlock extends SubscribeBlockBase {

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Anonymous subscription service.
   *
   * @var \Drupal\anonymous_subscriptions\DefaultService
   */
  protected $subscriptionService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->subscriptionService = $container->get('anonymous_subscriptions.default');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'vid',
      'description_top',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $vids = \Drupal::entityQuery('taxonomy_vocabulary')->execute();
    foreach ($vids as $vid => &$label) {
      if ($this->subscriptionService->isVocabularyAllowed($vid)) {
        $label = Vocabulary::load($vid)->label();
      }
      else {
        unset($vids[$vid]);
      }
    }

    $form['vid'] = [
      '#type' => 'radios',
      '#title' => $this->t('Vocabulary'),
      '#description' => $this->t('Select taxonomy vocabulary for subscription'),
      '#options' => $vids,
      '#default_value' => empty($this->configuration['vid']) ? NULL : $this->configuration['vid'],
    ];

    $form['description_top'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description top text'),
      '#description' => $this->t('This text will appear right after block title.'),
      '#default_value' => $this->configuration['description_top'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['vid'] = $form_state->getValue('vid');
    $this->configuration['description_top'] = $form_state->getValue('description_top');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'subscribe_taxonomy_terms_block';
    $vid = empty($this->configuration['vid']) ? NULL : $this->configuration['vid'];
    if (!$this->subscriptionService->isVocabularyAllowed($vid)) {
      $build['markup'] = ['#markup' => $this->t('Subscription is disabled for this vocabulary')];
      return $build;
    }

    $build['description_top'] = [
      '#markup' => '<p class="description-top">' . Html::escape($this->configuration['description_top']) . '</p>',
      '#weight' => -10,
    ];
    $form = $this->formBuilder->getForm('\Drupal\anonymous_subscriptions\Form\SubscribeTaxonomyTermsForm', $vid);
    $build['form'][] = $form;
    return $build;
  }

}
