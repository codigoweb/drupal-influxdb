<?php

namespace Drupal\influxdb\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Influxdb Connection entities.
 *
 * @ingroup influxdb
 */
interface InfluxdbConnectionInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Influxdb Connection name.
   *
   * @return string
   *   Name of the Influxdb Connection.
   */
  public function getName();

  /**
   * Gets the Influxdb Connection Host.
   *
   * @return string
   *   Host Address of the Influxdb Connection.
   */
  public function getHost();

  /**
   * Gets the Influxdb Connection port.
   *
   * @return string
   *   Port of the Influxdb Connection.
   */
  public function getPort();

  /**
   * Gets the Influxdb Connection token.
   *
   * @return string
   *   Token of the Influxdb Connection.
   */
  public function getToken();

  /**
   * Gets the Influxdb Connection Organization.
   *
   * @return string
   *   Organization of the Influxdb Connection.
   */
  public function getOrg();

  /**
   * Gets the Influxdb Connection Url Api Query.
   *
   * @return string
   *   Api Query for the Influxdb Connection.
   */
  public function getUrlApiQueryV2();

  /**
   * Sets the Influxdb Connection name.
   *
   * @param string $name
   *   The Influxdb Connection name.
   *
   * @return \Drupal\influxdb\Entity\InfluxdbConnectionInterface
   *   The called Influxdb Connection entity.
   */
  public function setName($name);

  /**
   * Gets the Influxdb Connection creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Influxdb Connection.
   */
  public function getCreatedTime();

  /**
   * Sets the Influxdb Connection creation timestamp.
   *
   * @param int $timestamp
   *   The Influxdb Connection creation timestamp.
   *
   * @return \Drupal\influxdb\Entity\InfluxdbConnectionInterface
   *   The called Influxdb Connection entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Influxdb Connection revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Influxdb Connection revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\influxdb\Entity\InfluxdbConnectionInterface
   *   The called Influxdb Connection entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Influxdb Connection revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Influxdb Connection revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\influxdb\Entity\InfluxdbConnectionInterface
   *   The called Influxdb Connection entity.
   */
  public function setRevisionUserId($uid);

}
