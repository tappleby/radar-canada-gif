<?php

require_once "vendor/autoload.php";
require_once "util.php";

use GifCreator\GifCreator;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Message\ResponseInterface;


$valid_radars = ["wuj","xpg","xss","xsi","xbe","whk","xfw","xra","xbu","www","xsm","xwl","whn","wbi","xdr","wso","xft","wkr","wgj","xti","xni","xla","wmn","xam","wvy","wmb","xnc","xgo","wtp","xme","xmb","can","pac","wrn","ont","que","ern"];

$RADAR_ID = request_param('id', $valid_radars, 'xft');
$RADAR_TYPE = request_param('type', ['short', 'long'], 'short');


$client = new GuzzleClient();

$resp = $client->get('http://weather.gc.ca/radar/xhr.php', [
	'headers' => [
		'X-Requested-With' => 'XMLHttpRequest'
	],
	'query' => [
		'action' => 'retrieve',
		'target' => 'images',
		'region' => strtoupper($RADAR_ID),
		'product' => 'precip_rain',
		'lang' => 'en-CA',
		'format' => 'json',
		'rand' => rand() / getrandmax(),
	]
])->json();

if (empty($resp[$RADAR_TYPE])) {
	response_error(416, 'No data for radar type: '. $RADAR_TYPE);
}

$frameData = $resp[$RADAR_TYPE];
$initialTimestamp = $frameData[0]['timestamp'];
$requestId = implode(compact('RADAR_ID', 'RADAR_TYPE', 'initialTimestamp'), '_');

$links = array_map(function ($fd) use ($RADAR_ID) {
	$query = [
		"base" => $fd['src'],
		"overlays" => [
			"/cacheable/images/radar/layers_detailed/roads/".strtoupper($RADAR_ID)."_roads.gif",
			"/cacheable/images/radar/layers_detailed/radar_circle/radar_circle.gif",
			"/cacheable/images/radar/layers_detailed/default_cities/". $RADAR_ID ."_towns.gif"
		]
	];

	$query = urldecode(http_build_query($query));
	return "http://weather.gc.ca/radar/image.php?". $query;
}, $frameData);


$requests = [];
foreach ($links as $idx => $link) {
	$req = $client->createRequest("GET", $link);
	$req->getQuery()->setEncodingType(false);
	$requests[] = $req;
}

$results = GuzzleHttp\batch($client, $requests);
$frames = [];

foreach ($results as $request) {
	$result = $results[$request];
	if ($result instanceof ResponseInterface) {
		$frames[] = imagecreatefromstring($result->getBody()->getContents());
	} else {
	}
}

$count = count($frames);

if ($count) {
	$durations = array_fill(0, $count-1, 40);
	$durations[$count-1] = 80;

	// Initialize and create the GIF !
	$gc = new GifCreator();
	$gc->create($frames, $durations, 0);

	foreach ($frames as $frame) {
		imagedestroy($frame);
	}

	$gifBinary = $gc->getGif();

	header('Content-type: image/gif');
	header('Content-Disposition: filename="'. $requestId .'_radar.gif"');
	print $gifBinary;
} else {
	http_response_code(416);
	echo "Error fetching radar.";
}

