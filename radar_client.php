<?php

require_once "util.php";

use GuzzleHttp\Client as GuzzleClient;
use PHPImageWorkshop\ImageWorkshop;

class RadarClient {
  protected $client;

  public function __construct($radar_id) {
    $this->client = new GuzzleClient(['base_uri' => 'https://weather.gc.ca']);
    $this->radar_id = $radar_id;
  }

  public function meta() {
    $resp = $this->client->get('/radar/xhr.php', [
      'headers' => [
        'X-Requested-With' => 'XMLHttpRequest'
      ],
      'query' => [
        'action' => 'retrieve',
        'target' => 'images',
        'region' => strtoupper($this->radar_id),
        'product' => 'precip_rain',
        'lang' => 'en-CA',
        'format' => 'json',
        'rand' => rand() / getrandmax(),
      ]
    ]);

    return json_decode($resp->getBody(), true);
  }

  public function overlay() {
    $layers = promises_to_layers([
      $this->client->getAsync("/cacheable/images/radar/layers_detailed/roads/".strtoupper($this->radar_id)."_roads.gif"),
      $this->client->getAsync("/cacheable/images/radar/layers_detailed/radar_circle/radar_circle.gif"),
      $this->client->getAsync("/cacheable/images/radar/layers_detailed/default_cities/". $this->radar_id ."_towns.gif")
    ]);

    $base_layer = $layers[0];

    foreach (array_slice($layers, 1) as $scratch_layer) {
      imagecopy($base_layer->getImage(), $scratch_layer->getImage(), 0, 0, 0, 0, $scratch_layer->getWidth(), $scratch_layer->getHeight());
      $scratch_layer->delete();
    }

    return $base_layer;
  }

  public function frames($frame_data) {
    $overlay_layer = $this->overlay();
    $frame_layers = $this->radar_frames($frame_data);

    return array_map(function ($frame) use ($overlay_layer) {
      $base = ImageWorkshop::initVirginLayer($frame->getWidth(), $frame->getHeight());
      $base->addLayerOnTop($frame);
      $base->addLayerOnTop($overlay_layer);

      return $base->getResult();
    }, $frame_layers);
  }

  function radar_frames($frame_data) {
    $requests = [];

    foreach ($frame_data as $fd) {
      $requests[] = $this->client->getAsync($fd['src']);
    }

    return promises_to_layers($requests);
  }
}