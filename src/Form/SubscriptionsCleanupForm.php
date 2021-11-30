<?php

namespace Drupal\anonymous_subscriptions\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\system\Form\PrepareModulesEntityUninstallForm;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Subscriptions cleanup form class.
 */
class SubscriptionsCleanupForm extends PrepareModulesEntityUninstallForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    return parent::buildForm($form, $form_state, 'anonymous_subscription');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymous_subscriptions_entities_uninstall';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('anonymous_subscriptions.settings_form');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type_id = $form_state->getValue('entity_type_id');

    $entity_type_plural = $this->entityTypeManager->getDefinition($entity_type_id)->getPluralLabel();
    $batch = [
      'title' => $this->t('Deleting @entity_type_plural', [
        '@entity_type_plural' => $entity_type_plural,
      ]),
      'operations' => [
        [
          [__CLASS__, 'deleteContentEntities'], [$entity_type_id],
        ],
      ],
      'finished' => [__CLASS__, 'moduleBatchFinished'],
      'progress_message' => '',
    ];
    batch_set($batch);
  }

  /**
   * Implements callback_batch_finished().
   *
   * Finishes the module batch, redirect to itself output the
   * successful data deletion message.
   */
  public static function moduleBatchFinished($success, $results, $operations) {
    $entity_type_plural = \Drupal::entityTypeManager()->getDefinition($results['entity_type_id'])->getPluralLabel();
    \Drupal::messenger()->addStatus(new TranslatableMarkup('All @entity_type_plural have been deleted.', ['@entity_type_plural' => $entity_type_plural]));

    return new RedirectResponse(Url::fromRoute('anonymous_subscriptions.settings_form')->setAbsolute()->toString());
  }

}
