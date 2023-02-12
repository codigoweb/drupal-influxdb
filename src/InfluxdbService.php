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
   * @param $influxdb_id
   * @param $dbname
   * @param $measurement
   * @param $name
   * @param $channel
   * @param $start_date
   * @param $end_date
   * @return array
   */
  public function getBetweenValue($influxdb_id, $dbname, $measurement, $name, $channel, $start_date, $end_date) {

    $body = $this->getCommonBody($dbname, $measurement, $name, $channel, $start_date, $end_date);
    $values = $this->getResults($influxdb_id, $body);
    $first_value = 0;
    $last_value = 0;
    $consumption = 0;
    foreach ($values as $key => $value) {
      if ($first_value === 0) {
        $first_value = $value;
      }
      if ($value < $last_value) {
        $consumption += $last_value - $first_value;
        $first_value = $value;
      }
      $last_value = $value;
    }
    $consumption += $last_value - $first_value;
    return $consumption;
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

    $i = 0;
    while( $current < $last ) {
      $dates[$i]['begin'] = $current->format(DateTime::RFC3339);
      if ($current === $first) {
        $current->setTime(0,0,0);
      }
      $current->add($interval);
      $dates[$i]['end'] = $current->format(DateTime::RFC3339);
      $i++;
    }
    // actualizamos la ultima hora
    $dates[$i-1]['end'] = $last->format(DateTime::RFC3339);

    return $dates;
  }

  /**
   * @param NodeInterface $pitch
   * @param DateTime $start_date
   * @param DateTime $end_date
   *
   * @return false|string
   * @throws \Exception
   */
  public function getConsumption(NodeInterface $pitch, DateTime $start_date, DateTime $end_date) {

    // Recuperamos los datos del Pitch y del Grupo
    $group_relationships = GroupRelationship::loadByEntity($pitch);
    $group_relationship = reset($group_relationships);
    $group = $group_relationship->getGroup();
    if (!$group instanceof GroupInterface) {
      return '';
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
    $values = [];
    foreach ($dates as $id => $date) {
      $datepart = substr($date['begin'], 0, 10);
      $values[$id]['date'] = $datepart;
      $values[$id]['consumption'] = $this->getBetweenValue($influxdb_id, $dbname, $measurement, $name, $channel, $date["begin"], $date["end"]);
    }

    $consumption = 0;
    foreach ($values as $value) {
      $partial_consumption = round($value["consumption"] / 1000, 3);
      $consumption += $partial_consumption;
      $comsuptions[$value["date"]] = $partial_consumption;

    }
    $comsuptions['total'] = $consumption;
    $comsuptions['pvp_kwh'] = $pvp_kwh;
    $comsuptions['importe'] = round($consumption * $pvp_kwh, 2);

    return Json::encode($comsuptions);
  }
}
