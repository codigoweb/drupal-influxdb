<?php

namespace Drupal\influxdb\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\influxdb\Entity\InfluxdbConnectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class InfluxdbConnectionController.
 *
 *  Returns responses for Influxdb Connection routes.
 */
class InfluxdbConnectionController extends ControllerBase {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Displays a Influxdb Connection revision.
   *
   * @param int $influxdb_connection_revision
   *   The Influxdb Connection revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($influxdb_connection_revision) {
    $influxdb_connection = $this->entityTypeManager()->getStorage('influxdb_connection')
      ->loadRevision($influxdb_connection_revision);
    if ($influxdb_connection instanceof InfluxdbConnectionInterface) {
      $view_builder = $this->entityTypeManager()->getViewBuilder('influxdb_connection');
      return $view_builder->view($influxdb_connection);
    }
    return [];
  }

  /**
   * Page title callback for a Influxdb Connection revision.
   *
   * @param int $influxdb_connection_revision
   *   The Influxdb Connection revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($influxdb_connection_revision) {
    $influxdb_connection = $this->entityTypeManager()->getStorage('influxdb_connection')
      ->loadRevision($influxdb_connection_revision);
    if ($influxdb_connection instanceof InfluxdbConnectionInterface) {
      return $this->t('Revision of %title from %date', [
        '%title' => $influxdb_connection->label(),
        '%date' => $this->dateFormatter->format($influxdb_connection->getRevisionCreationTime()),
      ]);
    }
    return '';
  }

  /**
   * Generates an overview table of older revisions of a Influxdb Connection.
   *
   * @param InfluxdbConnectionInterface $influxdb_connection
   *   A Influxdb Connection object.
   *
   * @return array
   *   An array as expected by drupal_render().
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionOverview(InfluxdbConnectionInterface $influxdb_connection) {
    $account = $this->currentUser();
    $influxdb_connection_storage = $this->entityTypeManager()->getStorage('influxdb_connection');

    $langcode = $influxdb_connection->language()->getId();
    $langname = $influxdb_connection->language()->getName();
    $languages = $influxdb_connection->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $influxdb_connection->label()]) : $this->t('Revisions for %title', ['%title' => $influxdb_connection->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all influxdb connection revisions") || $account->hasPermission('administer influxdb connection entities')));
    $delete_permission = (($account->hasPermission("delete all influxdb connection revisions") || $account->hasPermission('administer influxdb connection entities')));

    $rows = [];

    $vids = $influxdb_connection_storage->revisionIds($influxdb_connection);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var InfluxdbConnectionInterface $revision */
      $revision = $influxdb_connection_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $influxdb_connection->getRevisionId()) {
          $link = $this->l($date, new Url('entity.influxdb_connection.revision', [
            'influxdb_connection' => $influxdb_connection->id(),
            'influxdb_connection_revision' => $vid,
          ]));
        }
        else {
          $link = $influxdb_connection->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.influxdb_connection.translation_revert', [
                'influxdb_connection' => $influxdb_connection->id(),
                'influxdb_connection_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.influxdb_connection.revision_revert', [
                'influxdb_connection' => $influxdb_connection->id(),
                'influxdb_connection_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.influxdb_connection.revision_delete', [
                'influxdb_connection' => $influxdb_connection->id(),
                'influxdb_connection_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['influxdb_connection_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
