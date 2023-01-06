<?php

namespace Drupal\influxdb\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Influxdb Connection entity.
 *
 * @ingroup influxdb
 *
 * @ContentEntityType(
 *   id = "influxdb_connection",
 *   label = @Translation("Influxdb Connection"),
 *   handlers = {
 *     "storage" = "Drupal\influxdb\InfluxdbConnectionStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\influxdb\InfluxdbConnListBuilder",
 *     "views_data" = "Drupal\influxdb\Entity\InfluxdbConnectionViewsData",
 *     "translation" = "Drupal\influxdb\InfluxdbConnectionTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\influxdb\Form\InfluxdbConnectionForm",
 *       "add" = "Drupal\influxdb\Form\InfluxdbConnectionForm",
 *       "edit" = "Drupal\influxdb\Form\InfluxdbConnectionForm",
 *       "delete" = "Drupal\influxdb\Form\InfluxdbConnectionDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\influxdb\InfluxdbConnectionHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\influxdb\InfluxdbConnAccessControlHandler",
 *   },
 *   base_table = "influxdb_connection",
 *   data_table = "influxdb_connection_field_data",
 *   revision_table = "influxdb_connection_revision",
 *   revision_data_table = "influxdb_connection_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer influxdb influxdb_connection entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/influxdb/influxdb_connection/{influxdb_connection}",
 *     "add-form" = "/admin/content/influxdb/influxdb_connection/add",
 *     "edit-form" = "/admin/content/influxdb/influxdb_connection/{influxdb_connection}/edit",
 *     "delete-form" = "/admin/content/influxdb/influxdb_connection/{influxdb_connection}/delete",
 *     "version-history" = "/admin/content/influxdb/influxdb_connection/{influxdb_connection}/revisions",
 *     "revision" = "/admin/content/influxdb/influxdb_connection/{influxdb_connection}/revisions/{influxdb_connection_revision}/view",
 *     "revision_revert" = "/admin/content/influxdb/influxdb_connection/{influxdb_connection}/revisions/{influxdb_connection_revision}/revert",
 *     "revision_delete" = "/admin/content/influxdb/influxdb_connection/{influxdb_connection}/revisions/{influxdb_connection_revision}/delete",
 *     "translation_revert" = "/admin/content/influxdb/influxdb_connection/{influxdb_connection}/revisions/{influxdb_connection_revision}/revert/{langcode}",
 *     "collection" = "/admin/content/influxdb/influxdb_connection",
 *   },
 *   field_ui_base_route = "influxdb_connection.settings"
 * )
 */
class InfluxdbConnection extends EditorialContentEntityBase implements InfluxdbConnectionInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly,
    // make the influxdb_connection owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHost() {
    return $this->get('connection_address')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPort() {
    return $this->get('connection_port')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getToken() {
    return $this->get('connection_token')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrg() {
    return $this->get('connection_organization')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlApiQueryV2() {
    return $this->getHost() . ':' . $this->getPort() . '/api/v2/query?org=' . $this->getOrg();
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the influxdb influxdb_connection entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Connection Name'))
      ->setDescription(t('Connection name.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['connection_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Host address'))
      ->setDescription(t('The host address for the influxdb influxdb_connection.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 100,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['connection_port'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Port'))
      ->setDescription(t('The port used to connect to the influxdb influxdb_connection.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 5,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['connection_organization'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Organization'))
      ->setDescription(t('Organization defined in the InfluxDB Server. (Optional)'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 100,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    $fields['connection_token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Token Connection'))
      ->setDescription(t('Token to connect to the InfluxDB Server. (Optional)'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 200,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    $fields['connection_username'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Username'))
      ->setDescription(t('Username to connect to the InfluxDB Server. (Optional)'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    $fields['connection_password'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Password'))
      ->setDescription(t('Password to connect to the InfluxDB Server. (Optional)'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    $fields['status']->setDescription(t('A boolean indicating whether the connection is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -9,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

}
