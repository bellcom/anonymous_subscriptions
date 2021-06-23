<?php

namespace Drupal\anonymous_subscriptions\Form;

use Drupal\anonymous_subscriptions\DefaultService;
use Drupal\anonymous_subscriptions\Entity\Subscription;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Subscribe node form.
 */
class SubscribeNodeForm extends SubscribeFormBase {

  /**
   * The entity_field.manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\anonymous_subscriptions\DefaultService $subscription_service
   *   The anonymous subscription service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The entity_field.manager service.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DefaultService $subscription_service, EntityTypeManager $entityTypeManager, EntityFieldManager $entityFieldManager, FloodInterface $flood) {
    parent::__construct($config_factory, $subscription_service, $entityTypeManager, $flood);
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('anonymous_subscriptions.default'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('flood')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymous_subscriptions_subscribe_node_form';
  }

  /**
   * Title callback.
   */
  public function getTitle(NodeInterface $node = NULL) {
    return $node ? $this->t('Subscription on @title', [
      '@title' => $node->getTitle(),
    ]) : '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    if (empty($node)) {
      throw new NotFoundHttpException();
    }
    $config = $this->configFactory->get(SettingsForm::$configName);
    $valid_types = $config->get('anonymous_subscriptions_node_types') ?: [];
    if (empty($valid_types[$node->getType()])) {
      throw new NotFoundHttpException();
    }

    $description = $this->t('Subscribe for updates on new content.', [
      '@title' => $node->getTitle(),
    ]);

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $description . '</p>',
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email'),
      '#required' => TRUE,
    ];

    $form['page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Updates on this page.'),
    ];
    $form['node_type'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Updates on new content of type @type.', [
        '@type' => $node->getType(),
      ]),
    ];

    $form['nid'] = [
      '#type' => 'hidden',
      '#default_value' => $node->id(),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Subscribe'),
      '#weight' => 10,
    ];

    // Checking page content type definition against term references.
    $field_definitions = array_filter($this->entityFieldManager->getFieldDefinitions('node', $node->getType()), function ($field_definition) {
      // Checking all field against Taxonomy term reference field.
      return $this->subscriptionService->isFieldEnabled($field_definition);
    });

    if (empty($field_definitions)) {
      return $form;
    }

    /** @var \Drupal\taxonomy\TermStorage $termStorage */
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $depthClasses = [];
    $lookup_parent_terms = $this->settings->get('anonymous_subscriptions_lookup_parent_taxonomy_terms');
    foreach ($field_definitions as $field_definition) {
      $terms = $node->get($field_definition->getName())->referencedEntities();
      /** @var \Drupal\taxonomy\Entity\Term $term */
      foreach ($terms as $term) {
        $termParents = $termStorage->loadAllParents($term->id());
        if ($lookup_parent_terms) {
          $depth = 0;
          foreach (array_reverse($termParents) as $termTrail) {
            $options[$termTrail->id()] = $termTrail->label();
            $depthClasses[$termTrail->id()] = [
              '#wrapper_attributes' => [
                'class' => ['term-depth-' . $depth],
              ],
            ];
            $depth++;
          }
        }
        else {
          $labelTrail = [];
          foreach (array_reverse($termParents) as $termTrail) {
            $labelTrail[] = $termTrail->label();
          }
          $options[$termTrail->id()] = implode('<span class="delimiter">&raquo;</span>', $labelTrail);
        }
      }
    }

    $form['terms'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => $this->t('Updates on new content grouped by taxonomy terms:'),
      '#description' => $this->t('Select taxonomy terms which you would like to get updates on.'),
      '#attributes' => ['class' => ['taxonomy-terms-anonymous-subscriptions']],
      '#attached' => [
        'library' => [
          'anonymous_subscriptions/styles',
        ],
      ],
    ] + $depthClasses;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (empty(array_filter($form_state->getValue('terms'))) && empty($form_state->getValue('node_type')) && empty($form_state->getValue('page'))) {
      $form_state->setError($form['terms'], $this->t('Select subscription at least on one of proposed options.'));
      return;
    }

    $email = $form_state->getValue('email');
    $type = $form_state->getValue('node_type');
    $page = $form_state->getValue('page');
    $nid = $form_state->getValue('nid');
    $node = Node::load($nid);
    $ids = \Drupal::entityQuery('anonymous_subscription')
      ->condition('email', $email)
      ->condition('entity_type', 'node')
      ->condition('entity_bundle', $node->getType())
      ->execute();
    foreach (Subscription::loadMultiple($ids) as $subscription) {
      if ($page && $subscription->entity_id->value == $nid) {
        $form_state->setError($form['page'], $this->t('Email address @email already subscribed for update on this page.', [
          '@email' => $email,
        ]));
      }
      elseif ($type && empty($subscription->entity_id->value)) {
        $form_state->setError($form['node_type'], $this->t('Email address @email already subscribed for updates on content type @type.', [
          '@email' => $email,
          '@type' => $node->getType(),
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    $node_type = $form_state->getValue('node_type');
    $page = $form_state->getValue('page');
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::load($form_state->getValue('nid'));
    $verification_required = $this->settings->get('anonymous_subscriptions_verify');
    $new_subscriptions = FALSE;

    // Creating subscriptions for page.
    if ($page && $node) {
      /** @var \Drupal\anonymous_subscriptions\Entity\Subscription $subscription */
      $subscription = Subscription::create([
        'email' => $email,
        'code' => Crypt::randomBytesBase64(20),
        'entity_bundle' => $node->getType(),
        'entity_id' => $node->id(),
        'entity_type' => 'node',
        'verified' => !$verification_required,
      ]);
      $subscription->save();
      $new_subscriptions = TRUE;

      if ($verification_required) {
        $this->subscriptionService->sendVerificationMail($subscription);
      }
      else {
        $this->subscriptionService->sendConfirmationMail($subscription);
      }
    }

    // Creating subscriptions for content type.
    if ($node_type && $node) {
      /** @var \Drupal\anonymous_subscriptions\Entity\Subscription $subscription */
      $subscription = Subscription::create([
        'email' => $email,
        'code' => Crypt::randomBytesBase64(20),
        'entity_bundle' => $node->getType(),
        'entity_type' => 'node',
        'verified' => !$verification_required,
      ]);
      $subscription->save();
      $new_subscriptions = TRUE;

      if ($verification_required) {
        $this->subscriptionService->sendVerificationMail($subscription);
      }
      else {
        $this->subscriptionService->sendConfirmationMail($subscription);
      }
    }

    // Creating subscriptions for taxonomy term references.
    $tids = array_filter($form_state->getValue('terms'));
    $ids = empty($tids) ? [] : \Drupal::entityQuery('anonymous_subscription')
      ->condition('email', $email)
      ->condition('entity_type', 'taxonomy_term')
      ->condition('entity_id', $tids, 'IN')
      ->execute();

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
        '@terms' => Markup::create(\Drupal::service('renderer')->renderPlain($subscribed_terms_names)),
      ]));
    }

    $verification_required = $this->settings->get('anonymous_subscriptions_verify');
    /** @var \Drupal\taxonomy\Entity\Term $term */
    foreach (Term::loadMultiple($tids) as $term) {
      /** @var \Drupal\anonymous_subscriptions\Entity\Subscription $subscription */
      $subscription = Subscription::create([
        'email' => $email,
        'code' => Crypt::randomBytesBase64(20),
        'entity_id' => $term->id(),
        'entity_bundle' => $term->bundle(),
        'entity_type' => 'taxonomy_term',
        'verified' => !$verification_required,
      ]);
      $subscription->save();
      $new_subscriptions = TRUE;

      if ($verification_required) {
        $this->subscriptionService->sendVerificationMail($subscription);
      }
      else {
        $this->subscriptionService->sendConfirmationMail($subscription);
      }
    }

    // Show message if there were added new subscriptions.
    if ($new_subscriptions) {
      $status_message = $this->t('You are now subscribed to receive updates.');
      if ($verification_required) {
        $status_message = $this->t('Thanks for subscribing, in order to receive updates, you will need to verify your email address for every subscription by clicking on a link in an emails we just sent you.');
      }
      $this->messenger()->addStatus($status_message);
    }
  }

}
