<?php

namespace Drupal\influxdb\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Influxdb Connection entities.
 */
class InfluxdbConnectionViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
