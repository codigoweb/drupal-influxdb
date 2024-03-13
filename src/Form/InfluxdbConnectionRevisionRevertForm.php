<?php

namespace Drupal\influxdb\Form;

use Drupal;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\influxdb\Entity\InfluxdbConnectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a Influxdb Connections revision.
 *
 * @ingroup influxdb
 */
class InfluxdbConnectionRevisionRevertForm extends ConfirmFormBase {

  /**
   * The Influxdb Connections revision.
   *
   * @var \Drupal\influxdb\Entity\InfluxdbConnectionInterface
   */
  protected $revision;

  /**
   * The Influxdb Connections storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $influxdbConnectionStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->influxdbConnectionStorage = $container->get('entity_type.manager')->getStorage('influxdb_connection');
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'influxdb_connection_revision_revert_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to revert to the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.influxdb_connection.version_history', ['influxdb_connection' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $influxdb_connection_revision = NULL) {
    $this->revision = $this->InfluxdbConnectionStorage->loadRevision($influxdb_connection_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $original_revision_timestamp = $this->revision->getRevisionCreationTime();

    $this->revision = $this->prepareRevertedRevision($this->revision, $form_state);
    $this->revision->revision_log = $this->t('Copy of the revision from %date.', [
      '%date' => $this->dateFormatter->format($original_revision_timestamp),
    ]);
    $this->revision->save();

    $this->logger('content')->notice('Influxdb Connections: reverted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()->addMessage(t('Influxdb Connections %title has been reverted to the revision from %revision-date.', ['%title' => $this->revision->label(), '%revision-date' => $this->dateFormatter->format($original_revision_timestamp)]));
    $form_state->setRedirect(
      'entity.influxdb_connection.version_history',
      ['influxdb_connection' => $this->revision->id()]
    );
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\influxdb\Entity\InfluxdbConnectionInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\influxdb\Entity\InfluxdbConnectionInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(InfluxdbConnectionInterface $revision, FormStateInterface $form_state) {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);
    $request_time = Drupal::time()->getRequestTime();
    $revision->setRevisionCreationTime($request_time);

    return $revision;
  }

}
