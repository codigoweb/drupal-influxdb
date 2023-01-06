<?php

namespace Drupal\influxdb;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Influxdb Connection entities.
 *
 * @ingroup influxdb
 */
class InfluxdbConnListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Influxdb Connection ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\influxdb\Entity\InfluxdbConnection $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.influxdb_connection.canonical',
      ['influxdb_connection' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
