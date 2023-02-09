<?php

namespace Drupal\influxdb;

use DateInterval;
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
   * @param $url
   * @param $body
   * @param $token
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

  /**
   * @param $dbname
   * @param $measurement
   * @param $name
   * @param $channel
   * @param $start_date
   * @param $end_date
   * @return string
   */
  public function getCommonBody( $dbname, $measurement, $name, $channel, $start_date, $end_date) {

    return 'from(bucket: "'. $dbname. '")
  |> range(start: '. $start_date .', stop: '. $end_date .')
  |> filter(fn: (r) => r._measurement == "' . $measurement . '")
  |> filter(fn: (r) => r._field =="name" or r._field =="channel" or r._field =="value")
  |> pivot(rowKey:["_time"], columnKey: ["_field"], valueColumn: "_value")
  |> filter(fn: (r) => r.name == "' . $name . '")
  |> filter(fn: (r) => r.channel == ' . $channel . ')';
  }

  /**
   * @param $influxdb_id
   * @param $body
   * @return array
   */
  public function getResults($influxdb_id, $body) {

    $influxdbConnection = InfluxdbConnection::load($influxdb_id);
    if ($influxdbConnection instanceof InfluxdbConnectionInterface) {
      $url = $influxdbConnection->getUrlApiQueryV2();
      $token = $influxdbConnection->getToken();
    }

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

  /**
   * @param $influxdb_id
   * @param $dbname
   * @param $measurement
   * @param $name
   * @param $channel
   * @param $start_date
   * @param $end_date
   * @return array
   */
  public function getFirstValue($influxdb_id, $dbname, $measurement, $name, $channel, $start_date, $end_date) {

    $body = $this->getCommonBody($dbname, $measurement, $name, $channel, $start_date, $end_date);
    $body .= '  |> limit(n: 1)';
    return $this->getResults($influxdb_id, $body);
  }

  /**
   * @param $influxdb_id
   * @param $dbname
   * @param $measurement
   * @param $name
   * @param $channel
   * @param $start_date
   * @param $end_date
   * @return array
   */
  public function getLastValue($influxdb_id, $dbname, $measurement, $name, $channel, $start_date, $end_date) {

    $body = $this->getCommonBody($dbname, $measurement, $name, $channel, $start_date, $end_date);
    $body .= '  |> last(column: "_time")';
    return $this->getResults($influxdb_id, $body);
  }

  /**
   * Date range
   *
   * @param DateTime $first
   * @param DateTime $last
   * @param string $step
   * @param string $format
   * @return array
   */
  public function getDateRange( DateTime $first, DateTime $last, $step = '+1 day') {
    $dates = [];
    $current = $first;
    $interval = new DateInterval('P1D');
    $interval2 = new DateInterval('PT1H');

    $i = 0;
    while( $current < $last ) {
      $dates[$i]['begin'] = $current->format(DateTime::RFC3339);
      $current->add($interval2);
      $dates[$i]['end'] = $current->format(DateTime::RFC3339);
      if ($current === $first) {
        $current->setTime(0,0,0);
      }
      $current->add($interval);
      $i++;
    }
    $dates[$i]['end'] = $last->format(DateTime::RFC3339);
    $last->sub($interval2);
    $dates[$i]['begin'] = $last->format(DateTime::RFC3339);

    return $dates;
  }

  /**
   * @param NodeInterface $pitch
   * @param DateTime $start_date
   * @param DateTime $end_date
   * @return array
   * @throws \Exception
   */
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

    // Adjust the dates to UTC time
    $interval =new DateInterval('PT' . $start_date->getOffset() . 'S');
    $start_date->add($interval);
    $end_date->add($interval);
    $start_date->setTimezone(new \DateTimeZone('UTC'));
    $end_date->setTimezone(new \DateTimeZone('UTC'));

    $dates = $this->getDateRange($start_date, $end_date);
    $end_date_rfc = $end_date->format(DateTime::RFC3339);
    $values = [];
    foreach ($dates as $id => $date) {
      if ($date['begin'] !== $end_date_rfc) {
        $datepart = substr($date['begin'], 0, 10);
        $values[$id]['date'] = $datepart;
        $initial_value = $this->getFirstValue($influxdb_id, $dbname, $measurement, $name, $channel, $date['begin'], $date['end']);
        $n = 1;
        while( empty($initial_value)) {
          $initial_value = $this->getFirstValue($influxdb_id, $dbname, $measurement, $name, $channel, $date['begin'], $dates[$id+$n]["begin"]);
          $n++;
        }
        $values[$id]['initial_value'] = reset($initial_value);
        if ($id !== 0) {
          $values[$id-1]['end_value'] = $values[$id]['initial_value'];
          $values[$id-1]['consumption'] = $values[$id-1]['end_value'] - $values[$id-1]['initial_value'];
        }
      }
      else {
        $end_value = $this->getLastValue($influxdb_id, $dbname, $measurement, $name, $channel, $date['begin'], $date['end']);
        if (empty($end_value)) {
          $end_value = $this->getLastValue($influxdb_id, $dbname, $measurement, $name, $channel, $dates[$id-1]["end"], $date['end']);
        }
        $values[$id-1]['end_value'] = reset($end_value);;
        $values[$id-1]['consumption'] = $values[$id-1]['end_value'] - $values[$id-1]['initial_value'];
      }
    }
    $consumption = 0;
    foreach ($values as $value) {
      $consumption += $value["consumption"];
      $comsuptions[$value["date"]] = round($value["consumption"], 4);

    }
    $comsuptions['total'] = round($consumption, 4);
    $comsuptions['pvp_kwh'] = $pvp_kwh;
    $comsuptions['importe'] = round($consumption / 1000 * $pvp_kwh, 2);

    return Json::encode($comsuptions);
  }
}
