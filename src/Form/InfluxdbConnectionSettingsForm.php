<?php

namespace Drupal\influxdb\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class InfluxdbConnectionSettingsForm.
 *
 * @ingroup influxdb
 */
class InfluxdbConnectionSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'influxdb.influxdbconnection.settings',
    ];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'influxdbconnection_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('influxdb.influxdbconnection.settings')
      ->set('influxdb_id', $form_state->getValue('influxdb_id'))
      ->save();
  }

  /**
   * Defines the settings form for Influxdb Connections entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('influxdb.influxdbconnection.settings');

    $form['influxdb_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Influxdb ID'),
      '#description' => $this->t('Provide an id to use when polling Influxdb Connections'),
      '#default_value' => $config->get('influxdb_id'),
    ];
    return parent::buildForm($form, $form_state);
  }

}
