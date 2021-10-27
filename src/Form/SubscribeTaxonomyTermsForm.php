<?php

namespace Drupal\anonymous_subscriptions\Form;

use Drupal\anonymous_subscriptions\Entity\Subscription;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TaxonomyTermsSubscriptionForm.
 */
class SubscribeTaxonomyTermsForm extends SubscribeFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymous_subscription_taxonomy_terms_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $vid = NULL) {

    if (!$this->subscriptionService->isVocabularyAllowed($vid)) {
      $this->getLogger('anonymous_subscriptions')->warning('There is no fields in allowed content types that refers to taxonomy term from vocabulary @vid.', [
        '@vid' => $vid,
      ]);
      throw new NotFoundHttpException();
    }

    $max_depth = $this->settings->get('anonymous_subscriptions_taxonomy_terms_depth');
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vid, 0, $max_depth ?: NULL, TRUE);
    if (empty($terms)) {
      $this->messenger()->addWarning($this->t('There is no taxonomy terms in this vocabulary'));
      return $form;
    }

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email'),
      '#required' => TRUE,
    ];

    $form['vid'] = [
      '#type' => 'hidden',
      '#default_value' => $vid,
    ];

    $form['subscription_terms_container'] = [
      '#type' => 'container',
    ];

    $vocabulary = Vocabulary::load($vid);
    $anonymous_subscription_disabled_terms = $this->settings->get('anonymous_subscription_disabled_terms');
    $options = [];
    $depthClasses = [];
    foreach ($terms as $term) {
      if (in_array($term->id(), $anonymous_subscription_disabled_terms)) {
        continue;
      }
      $options[$term->id()] = $term->getName();
      $depthClasses[$term->id()] = [
        '#wrapper_attributes' => [
          'class' => ['term-depth-' . $term->depth],
        ],
      ];
    }

    if (!empty($options)) {
      $form['terms'] = [
        '#type' => 'checkboxes',
        '#options' => $options,
        '#title' => $this->t('Subscribe to @name', ['@name' => $vocabulary->get('name')]),
        '#attached' => [
          'library' => [
            'anonymous_subscriptions/styles',
          ],
        ],
      ];
      $form['terms'] += $depthClasses;
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Subscribe'),
    ];

    $form['#tree'] = TRUE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (empty(array_filter($form_state->getValue('terms')))) {
      $form_state->setError($form['terms'], $this->t('Select at least one taxonomy term from list.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    $vid = $form_state->getValue('vid');
    $tids = array_filter($form_state->getValue('terms'));

    $query = \Drupal::entityQuery('anonymous_subscription')
      ->condition('email', $email)
      ->condition('entity_type', 'taxonomy_term')
      ->condition('entity_bundle', $vid)
      ->condition('entity_id', $tids, 'IN');

    $ids = $query->execute();
    if (!empty($existing_subscriptions = Subscription::loadMultiple($ids))) {
      $subscribed_terms_names = [
        '#theme' => 'item_list',
        '#items' => [],
      ];
      foreach ($existing_subscriptions as $subscription) {
        if (empty($term = Term::load($subscription->entity_id->value)) || empty($tids[$subscription->entity_id->value])) {
          continue;
        }
        $subscribed_terms_names['#items'][] = $term->label();
        unset($tids[$term->id()]);
      }
      $this->messenger()->addStatus($this->t('Email address @email already subscribed for taxonomy terms: @terms Creating of new subscriptions was these terms skipped.', [
        '@email' => $email,
        '@terms' => Markup::create(\Drupal::service('renderer')->renderPlain($subscribed_terms_names)),
      ]));
    }

    // Do nothing if there is no new subscriptions needed.
    if (empty($tids)) {
      return;
    }

    $verification_required = $this->settings->get('anonymous_subscriptions_verify');
    foreach (Term::loadMultiple($tids) as $term) {
      /** @var \Drupal\anonymous_subscriptions\Entity\Subscription $subscription */
      $subscription = Subscription::create([
        'email' => $email,
        'code' => Crypt::randomBytesBase64(20),
        'entity_id' => $term->id(),
        'entity_bundle' => $vid,
        'entity_type' => 'taxonomy_term',
        'verified' => !$verification_required,
      ]);
      $subscription->save();

      if ($verification_required) {
        $this->subscriptionService->sendVerificationMail($subscription);
      }
      else {
        $this->subscriptionService->sendConfirmationMail($subscription);
      }
    }

    $status_message = $this->t('You are now subscribed to receive updates.');
    if ($verification_required) {
      $status_message = $this->t('Thanks for subscribing, in order to receive updates, you will need to verify your email address by clicking on a link in an email we just sent you.');
    }

    $this->messenger()->addStatus($status_message);
  }

  /**
   * Title callback.
   */
  public function getTitle($vid = NULL) {
    if ($this->subscriptionService->isVocabularyAllowed($vid)) {
      $type_label = $this->entityTypeManager
        ->getStorage('taxonomy_vocabulary')
        ->load($vid)
        ->label();
      return $this->t('@type taxonomy subscription', [
        '@type' => $type_label,
      ]);
    }
    return $this->t('Subscription');
  }

}
