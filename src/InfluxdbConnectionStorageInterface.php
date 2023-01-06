<?php

namespace Drupal\influxdb;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface InfluxdbConnectionStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Influxdb Connection revision IDs for a specific Influxdb Connection.
   *
   * @param \Drupal\influxdb\Entity\InfluxdbConnectionInterface $entity
   *   The Influxdb Connection entity.
   *
   * @return int[]
   *   Influxdb Connection revision IDs (in ascending order).
   */
  public function revisionIds(InfluxdbConnectionInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Influxdb Connection author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Influxdb Connection revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\influxdb\Entity\InfluxdbConnectionInterface $entity
   *   The Influxdb Connection entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(InfluxdbConnectionInterface $entity);

  /**
   * Unsets the language for all Influxdb Connection with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
