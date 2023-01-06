<?php

namespace Drupal\influxdb;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\influxdb\Entity\InfluxdbConnectionInterface;

/**
 * Defines the storage handler class for Influxdb Connection entities.
 *
 * This extends the base storage class, adding required special handling for
 * Influxdb Connection entities.
 *
 * @ingroup influxdb
 */
class InfluxdbConnectionStorage extends SqlContentEntityStorage implements InfluxdbConnectionStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(InfluxdbConnectionInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {influxdb_connection_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {influxdb_connection_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(InfluxdbConnectionInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {influxdb_connection_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('influxdb_connection_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
