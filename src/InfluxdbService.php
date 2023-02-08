<?php

namespace Drupal\influxdb;

use DateTime;
use Drupal;
use Drupal\Component\Serialization\Json;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationship;
use Drupal\influxdb\Entity\InfluxdbConnection;
use Drupal\influxdb\Entity\InfluxdbConnectionInterface;
use Drupal\node\NodeInterface;

class InfluxdbService {

  /**
   * The influxdb module config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a influxdb object.
   *
   */
  public function __construct() {
    $this->config =  Drupal::config('influxdb.settings');
  }

  /**
   * POST Document.
   *
   * @return array
   *   The service response.
   */
  public function post($url, $body, $token) {

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => $body,
      CURLOPT_HTTPHEADER => array(
        'Accept: application/csv',
        'Content-Type: application/vnd.flux',
        'Authorization: Token ' . $token
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    return $response;
  }

  public function getRangeValues($influxdb_id, $dbname, $measurement, $name, $channel, $start_date, $end_date) {

    $influxdbConnection = InfluxdbConnection::load($influxdb_id);
    if ($influxdbConnection instanceof InfluxdbConnectionInterface) {
      $url = $influxdbConnection->getUrlApiQueryV2();
      $token = $influxdbConnection->getToken();
    }

    $body = 'from(bucket: "'. $dbname. '")
  |> range(start: '. $start_date .', stop: '. $end_date .')
  |> filter(fn: (r) => r._measurement == "' . $measurement . '")
  |> filter(fn: (r) => r._field =="name" or r._field =="channel" or r._field =="value")
  |> pivot(rowKey:["_time"], columnKey: ["_field"], valueColumn: "_value")
  |> filter(fn: (r) => r.name == "' . $name . '")
  |> filter(fn: (r) => r.channel == ' . $channel . ')
';

    $result = [];
    $messages = $this->post($url, $body, $token);
    $lines = explode(PHP_EOL, $messages);
    $header = reset($lines);
    $header_array = explode(',', $header);
    $key = array_search('_time', $header_array);
    array_shift($lines);

    foreach ($lines as $line) {
      $msg_array = explode(',', $line);
      $value = preg_replace('/[^A-Za-z0-9-.]/', ' ', end($msg_array));
      if (!empty($msg_array[$key])) {
        $time = $msg_array[$key];
        if (!empty($time)) {
          $result[$time] = (float) $value;
        }
      }
    }
    return $result;

  }

  public function getConsumption(NodeInterface $pitch, DateTime $start_date, DateTime $end_date) {

    // Recuperamos los datos del Pitch y del Grupo
    $group_relationships = GroupRelationship::loadByEntity($pitch);
    $group_relationship = reset($group_relationships);
    $group = $group_relationship->getGroup();
    if (!$group instanceof GroupInterface) {
      return [];
    }
    $name = $pitch->get('field_mqtt_topic')->value;
    $channel = $pitch->get('field_channel')->value;
    $dbname = $group->get('field_influxdb_bucket')->value;
    $measurement = $group->get('field_topic_sufix')->value;
    $pvp_kwh = (float) $group->get('field_pvp_kwh')->value;
    $influxdb_id = $group->get('field_influxdb_connection')->target_id;

    $start_date->setTimezone(new \DateTimeZone('UTC'));
    $end_date->setTimezone(new \DateTimeZone('UTC'));
    $start_date_rfc = $start_date->format(DateTime::RFC3339);
    $end_date_rfc = $end_date->format(DateTime::RFC3339);

    // Recogemos todos los datos entre las fechas señaladas
    $data = $this->getRangeValues($influxdb_id, $dbname, $measurement, $name, $channel, $start_date_rfc, $end_date_rfc);
    $start_value = reset($data);
    $start_date = array_key_first($data);
    $end_value = end($data);
    $end_date = array_key_last($data);
    // inicializamos las variables para realizar los cálculos
    $previous_value = 0;
    $compare_partial_value = $start_value;
    $consumption = 0;
    $consumption_partial = 0;
    $compare_value = $start_value;
    $compare_date = substr($start_date, 0, 10);

    foreach ($data as $date => $value) {
      // Si hay cortes de luz, el contador empieza de 0
      if ($value < $previous_value) {
        $consumption += $previous_value - $compare_value;
        $compare_value = $value;
        $compare_partial_value = $value;
      }
      $previous_value = $value;

      // Calculamos los datos de cada día por comparación con el día anterior (menos el último)
      $partial_date = substr($date, 0, 10);
      if ($partial_date <> $compare_date) {
        $comsuptions[$compare_date] = round($value - $compare_partial_value, 4);
        $compare_partial_value = $value;
        $consumption_partial += $comsuptions[$compare_date];
        $compare_date = $partial_date;
      }
    }

    // acumulamos la diferencia cuando cambia el valor recibido
    if ($compare_value !== $start_value) {
      $consumption += $end_value - $compare_value;
    }
    // Si el valor es 0 es que ha habido corte de luz y
    if ($consumption === 0) {
      $consumption = $end_value - $start_value;
    }

    // Calculamos los datos del último día
    $last_date = substr($end_date, 0, 10);
    if ($last_date === $compare_date) {
      $comsuptions[$last_date] = round($consumption - $consumption_partial, 4);
    }
    // Añadimos el consumo total al array devuelto
    $comsuptions['total'] = round($consumption, 4);
    $comsuptions['pvp_kwh'] = $pvp_kwh;
    $comsuptions['importe'] = round($consumption / 1000 * $pvp_kwh, 2);

    return Json::encode($comsuptions);

  }



}
