<?php

namespace Drupal\influxdb;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the MQTT Subscription entity.
 *
 * @see \Drupal\influxdb\Entity\InfluxdbConnection.
 */
class InfluxdbConnAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\influxdb\Entity\InfluxdbConnectionInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished Influxdb connection entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published Influxdb connection entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit Influxdb connection entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete Influxdb connection entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add Influxdb connection entities');
  }


}
