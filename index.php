<?php

require_once "vendor/autoload.php";
require_once "util.php";
require_once "radar_client.php";

$valid_radars = ["wuj","xpg","xss","xsi","xbe","whk","xfw","xra","xbu","www","xsm","xwl","whn","wbi","xdr","wso","xft","wkr","wgj","xti","xni","xla","wmn","xam","wvy","wmb","xnc","xgo","wtp","xme","xmb","can","pac","wrn","ont","que","ern"];

$radar_id = request_param('id', $valid_radars, 'xft');
$radar_type = request_param('type', ['short', 'long'], 'short');
$radar = new RadarClient($radar_id);

$meta = $radar->meta();

if (empty($meta[$radar_type])) {
  response_error(416, 'No data for radar type: '. $radar_type);
}

$frame_data = $meta[$radar_type];
$request_id = implode([$radar_id, $radar_type, $frame_data[0]['timestamp']], '_');
$combined_frames = $radar->frames($frame_data);
$frame_count = count($combined_frames);

if ($frame_count) {
 $durations = array_fill(0, $frame_count-1, 40);
 $durations[$frame_count-1] = 80;

 $gc = new GifCreator\GifCreator();
 $gc->create($combined_frames, $durations, 0);

 header('Content-type: image/gif');
 header('Content-Disposition: filename="'. $request_id .'_radar.gif"');
 echo $gc->getGif();

} else {
 http_response_code(416);
 echo "Error fetching radar.";
}

