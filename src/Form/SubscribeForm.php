<?php

namespace Drupal\anonymous_subscriptions\Form;

use Drupal\anonymous_subscriptions\Entity\Subscription;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Subscribe form.
 */
class SubscribeForm extends SubscribeFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymous_subscriptions_subscribe_form';
  }

  /**
   * Title callback.
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
    parent::validateForm($form, $form_state);

    $email = $form_state->getValue('email');
    $type = $form_state->getValue('type');

    $query = \Drupal::entityQuery('anonymous_subscription')
      ->condition('email', $email)
      ->condition('entity_type', 'node');
    if (empty($type)) {
      $query->notExists('entity_bundle');
    }
    else {
      $query->condition('entity_bundle', $type);
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
      'entity_bundle' => $type,
      'entity_type' => 'node',
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
