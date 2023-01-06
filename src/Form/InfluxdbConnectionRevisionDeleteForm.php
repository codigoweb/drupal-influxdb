<?php

namespace Drupal\influxdb\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Influxdb Connections revision.
 *
 * @ingroup influxdb
 */
class InfluxdbConnectionRevisionDeleteForm extends ConfirmFormBase {

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
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->influxdbConnectionStorage = $container->get('entity_type.manager')->getStorage('influxdb_connection');
    $instance->connection = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'influxdb_connection_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => format_date($this->revision->getRevisionCreationTime()),
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
    return $this->t('Delete');
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
    $this->InfluxdbConnectionStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Influxdb Connections: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()->addMessage(t('Revision from %revision-date of Influxdb Connections %title has been deleted.', ['%revision-date' => format_date($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.influxdb_connection.canonical',
       ['influxdb_connection' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {influxdb_connection_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.influxdb_connection.version_history',
         ['influxdb_connection' => $this->revision->id()]
      );
    }
  }

}
