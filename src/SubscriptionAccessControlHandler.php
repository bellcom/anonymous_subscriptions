<?php

namespace Drupal\anonymous_subscriptions;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Subscription entity.
 *
 * @see \Drupal\anonymous_subscriptions\Entity\Subscription.
 */
class SubscriptionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    switch ($operation) {
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete anonymous_subscription entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

}
