<?php

/**
 * @file
 * Contains influxdb_connection.page.inc.
 *
 * Page callback for Influxdb Connection entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Influxdb Connection templates.
 *
 * Default template: influxdb_connection.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_influxdb_connection(array &$variables) {
  // Fetch InfluxdbConnection Entity Object.
  $influxdb_connection = $variables['elements']['#influxdb_connection'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
