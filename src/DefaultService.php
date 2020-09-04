<?php

namespace Drupal\anonymous_subscriptions;

use Drupal\anonymous_subscriptions\Entity\Subscription;
use Drupal\anonymous_subscriptions\Form\SettingsForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;

/**
 * Class DefaultService.
 */
class DefaultService {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * Subclasses should use the self::config() method, which may be overridden to
   * address specific needs when loading config, rather than this property
   * directly. See \Drupal\Core\Form\ConfigFormBase::config() for an example of
   * this.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * Drupal\mailsystem\MailsystemManager definition.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * PrivateTempStoreFactory service.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStore;

  /**
   * Anonymous subscription settings configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * Queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new DefaultService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Mail\MailManagerInterface $manager_mail
   *   The Mail manager objects.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager object.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStore
   *   The logger service.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The Queue factory service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MailManagerInterface $manager_mail, LanguageManagerInterface $languageManager, LoggerInterface $logger, Token $token, PrivateTempStoreFactory $tempStore, QueueFactory $queueFactory, EntityTypeManager $entityTypeManager) {
    $this->configFactory = $config_factory;
    $this->settings = $config_factory->get(SettingsForm::$configName);
    $this->mailManager = $manager_mail;
    $this->logger = $logger;
    $this->languageManager = $languageManager;
    $this->token = $token;
    $this->tempStore = $tempStore->get('anonymous_subscriptions');
    $this->queueFactory = $queueFactory;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Sends confirmation mail to subscriber.
   *
   * @param \Drupal\anonymous_subscriptions\Entity\Subscription $subscription
   *   Subscription entity.
   */
  public function sendConfirmationMail(Subscription $subscription) {
    $subject = $this->t('Subscription on content updates from @site_name', [
      '@site_name' => $this->configFactory->get('system.site')->get('name'),
    ]);
    $replacements = [
      '@unsubscribe_url' => $this->getUnsubscribeUrl($subscription),
    ];
    if ($subscription->type->value) {
      $type_label = $this->entityTypeManager
        ->getStorage('node_type')
        ->load($subscription->type->value)
        ->label();
      $replacements['@type'] = $type_label;
      $body = $this->t("You are subscribed to receive updates on content of @type.\r\nTo unsubscribe please visit @unsubscribe_url", $replacements);
    }
    else {
      $body = $this->t("You are subscribed to receive updates on all content.\r\nTo unsubscribe please visit @unsubscribe_url", $replacements);
    }
    $this->sendMail([
      'to' => $subscription->email->value,
      'subject' => $subject,
      'body' => (string) $body,
    ]);
  }

  /**
   * Sends verification mail to subscriber.
   *
   * @param \Drupal\anonymous_subscriptions\Entity\Subscription $subscription
   *   Subscription entity.
   */
  public function sendVerificationMail(Subscription $subscription) {
    $subject = $this->t('Confirm your subscription request on @site_name', [
      '@site_name' => $this->configFactory->get('system.site')->get('name'),
    ]);

    $replacements = [
      '@confirm_url' => $this->getConfirmUrl($subscription),
      '@unsubscribe_url' => $this->getUnsubscribeUrl($subscription),
    ];
    if ($subscription->type->value) {
      $type_label = $this->entityTypeManager
        ->getStorage('node_type')
        ->load($subscription->type->value)
        ->label();
      $replacements['@type'] = $type_label;
      $body = $this->t("You have requested subscription to get updates on content type @type.\r\nTo confirm your subscription please visit the following url @confirm_url\r\nTo reject you subscription request use url @unsubscribe_url", $replacements);
    }
    else {
      $body = $this->t("You have requested subscription to get updates on all content.\r\nTo confirm your subscription please visit the following url @confirm_url\r\nTo reject you subscription request use url @unsubscribe_url", $replacements);
    }

    $this->sendMail([
      'to' => $subscription->email->value,
      'subject' => $subject,
      'body' => (string) $body,
    ]);
  }

  /**
   * Simply send mail function.
   *
   * @param array $message
   *   Array with email values.
   *
   * @return bool
   *   Sending status.
   */
  public function sendMail(array $message) {
    $siteName = $this->configFactory->get('system.site')->get('name');

    $to = $message['to'];
    $from = empty($message['from']) ? $this->getSender() : $message['from'];
    $params['from'] = $siteName . ' <' . $from . '>';
    $params['subject'] = $message['subject'];
    $params['body'] = $message['body'];

    $langcode = $this->languageManager->getDefaultLanguage()->getId();
    $result = $this->mailManager->mail('anonymous_subscriptions', 'anonymous_subscriptions_key', $to, $langcode, $params);

    if ($result['result'] !== TRUE) {
      $this->logger->warning(t('There was a problem sending email to %email', ['%email' => $to]));
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Adds pending emails to the queue.
   *
   * Sending email will be processed via cron at a later time.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object that specifies to the email.
   */
  public function addPendingEmails(NodeInterface $node) {
    $send_mail = $this->tempStore->get('send_mail:' . $node->getType() . ':' . $node->id());
    $type = $node->getType();
    $valid_types = $this->settings->get('anonymous_subscriptions_node_types') ?: [];

    // Emails will be not send when:
    // - node type is not allowed to get updates.
    // - node is not published.
    // - $send_email flag is false.
    if (empty($valid_types[$type])
      || !$node->isPublished()
      || !$send_mail
    ) {
      return;
    }

    $query = \Drupal::entityQuery('anonymous_subscription');
    $query->condition('verified', 1);
    $group = $query->orConditionGroup()
      ->notExists('type')
      ->condition('type', $node->getType());
    $query->condition($group);
    $ids = $query->execute();
    $subscriptions = Subscription::loadMultiple($ids);
    $queue = $this->queueFactory->get('anonymous_subscriptions_queue');

    $original_subject = $this->settings->get('anonymous_subscriptions_subject_text');
    $original_body = $this->settings->get('anonymous_subscriptions_body_text');

    $count = 0;
    /** @var \Drupal\anonymous_subscriptions\Entity\Subscription $subscription */
    foreach ($subscriptions as $subscription) {
      $email = $subscription->email->value;
      $subject = $this->token->replace($original_subject, ['node' => $node]);
      $body = $this->token->replace($original_body, ['node' => $node]);
      $body .= "\n\n";
      $body .= $this->t("To unsubscribe please visit url @unsubscribe_url\r\nTo remove all your subscription visit url @unsubscribe_all_url", [
        '@unsubscribe_url' => $this->getUnsubscribeUrl($subscription),
        '@unsubscribe_all_url' => $this->getUnsubscribeUrl($subscription, TRUE),
      ]);

      $fields = [
        'to' => $email,
        'subject' => $subject,
        'body' => $body,
        'nid' => $node->id(),
      ];

      $queue->createItem($fields);
      $log_text = t("Adding pending email to :to with subject :subject for nid :nid", [
        ':to' => $fields['to'],
        ':subject' => $fields['subject'],
        ':nid' => $fields['nid'],
      ]);
      $this->logger->notice($log_text);
      $count++;
    }

    if ($count > 0) {
      $message = t('Queuing @count emails to be sent to your subscribers.', [
        '@count' => $count,
      ]);
    }
    else {
      $message = t("No emails to be sent, there are no subscribers.");
    }
    \Drupal::messenger()->addMessage($message);
  }

  /**
   * Get unsubscribe url for subscription.
   *
   * @param \Drupal\anonymous_subscriptions\Entity\Subscription $subscription
   *   Subscription entity.
   * @param bool $all
   *   Flag to determine type of unsubscribe Url.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   *   Unsubscribe Url.
   */
  public function getUnsubscribeUrl(Subscription $subscription, bool $all = FALSE) {
    if ($all) {
      return Url::fromRoute('anonymous_subscriptions.cancel_all_subscriptions', [
        'subscription' => $subscription->id(),
        'code' => $subscription->code->value,
      ])->setAbsolute()->toString();

    }
    return Url::fromRoute('anonymous_subscriptions.cancel_subscription', [
      'subscription' => $subscription->id(),
      'code' => $subscription->code->value,
    ])->setAbsolute()->toString();
  }

  /**
   * Gets confirm Url for subscription.
   *
   * @param \Drupal\anonymous_subscriptions\Entity\Subscription $subscription
   *   Subscription entity.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   *   Confirm Url.
   */
  public function getConfirmUrl(Subscription $subscription) {
    return Url::fromRoute('anonymous_subscriptions.verify_subscription', [
      'subscription' => $subscription->id(),
      'code' => $subscription->code->value,
    ])->setAbsolute()->toString();
  }

  /**
   * Gets sender email.
   *
   * @return string
   *   Sender email.
   */
  public function getSender() {
    $site_email = $this->configFactory->get('system.site')->get('email');
    $sender_email = $this->settings->get('anonymous_subscriptions_sender');
    return empty($sender_email) ? $site_email : $sender_email;
  }

}
