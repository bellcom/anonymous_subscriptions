<?php

namespace Drupal\anonymous_subscriptions\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Subscription entity.
 *
 * @ingroup subscription
 * @ContentEntityType(
 *   id = "anonymous_subscription",
 *   label = @Translation("Subscription"),
 *   base_table = "anonymous_subscription",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "email" = "email",
 *   },
 * )
 */
class Subscription extends ContentEntityBase implements ContentEntityInterface {

  /**
   * Determines the schema for the base_table property defined above.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Subscription entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Subscription entity.'))
      ->setReadOnly(TRUE);

    // Email field for the subscription.
    $fields['email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Email'))
      ->setDescription(t('The email address of the user.'))
      ->setSettings([
        'max_length' => 255,
        'not null' => TRUE,
      ]);

    // Entity type field.
    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subscribed entity type'))
      ->setDescription(t('The type of entity for subscription.'))
      ->setSettings([
        'max_length' => 255,
      ]);

    // Entity node type field.
    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subscribed node type'))
      ->setDescription(t('The node type for subscription.'))
      ->setSettings([
        'max_length' => 255,
      ]);

    // Token field for the subscription.
    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Code'))
      ->setDescription(t('Code to verify and unsubscribe.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 128,
      ]);

    // Confirmed field for the subscription.
    $fields['verified'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Verified'))
      ->setDescription(t('Whether subscription is verified.'))
      ->setDefaultValue(FALSE);

    // The changed field type automatically updates the timestamp every time the
    // entity is saved.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the subscription was created.'));

    // The changed field type automatically updates the timestamp every time the
    // entity is saved.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the subscription was last edited.'));

    return $fields;
  }

}
