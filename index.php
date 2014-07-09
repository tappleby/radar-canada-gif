<?php

require_once "vendor/autoload.php";

use GifCreator\GifCreator;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Message\ResponseInterface;

$valid_radars = ["wuj","xpg","xss","xsi","xbe","whk","xfw","xra","xbu","www","xsm","xwl","whn","wbi","xdr","wso","xft","wkr","wgj","xti","xni","xla","wmn","xam","wvy","wmb","xnc","xgo","wtp","xme","xmb","can","pac","wrn","ont","que","ern"];

if (!empty($_GET['id']) && in_array($_GET['id'], $valid_radars)) {
	$RADAR_ID = $_GET['id'];
} else {
	$RADAR_ID = 'xft';
}

$client = new Client();
$crawler = $client->request('GET', 'http://weather.gc.ca/radar/index_e.html?id='.$RADAR_ID);


$links = $crawler->filter('.image-list-ol > li > a')->each(function (\Symfony\Component\DomCrawler\Crawler $node) {
		$parsed_url =  parse_url($node->attr('href'));
		parse_str($parsed_url["query"], $parsed_url["query"]);
		return $parsed_url;
});

$links = array_filter($links, function ($link) {
	return $link["query"]["duration"] == "short";
});

$links = array_map(function ($link) use ($RADAR_ID) {
	$query = [
		"base" => $link["query"]["display"],
		"overlays" => [
			"/cacheable/images/radar/layers_detailed/roads/".strtoupper($RADAR_ID)."_roads.gif",
			"/cacheable/images/radar/layers_detailed/radar_circle/radar_circle.gif",
			"/cacheable/images/radar/layers_detailed/default_cities/". $RADAR_ID ."_towns.gif"
		]
	];

	$query = urldecode(http_build_query($query));
	return "http://weather.gc.ca/radar/image.php?". $query;
}, $links);


$guzzleClient = new GuzzleClient();

$requests = [];
foreach ($links as $idx => $link) {
	$req = $guzzleClient->createRequest("GET", $link);
	$req->getQuery()->setEncodingType(false);
	$requests[] = $req;
}

$results = GuzzleHttp\batch($guzzleClient, $requests);
$frames = [];

foreach ($results as $request) {
	$result = $results[$request];
	if ($result instanceof ResponseInterface) {
		$frames[] = imagecreatefromstring($result->getBody());
	} else {
	}
}

$frames = array_reverse($frames);
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
	header('Content-Disposition: filename="'. $RADAR_ID .'_radar.gif"');
	print $gifBinary;
} else {
	http_response_code(416);
	echo "Error fetching radar.";
}

