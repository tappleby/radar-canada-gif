<?php

use GuzzleHttp\Promise;
use PHPImageWorkshop\ImageWorkshop;

function dd() {
  call_user_func_array('var_dump', func_get_args());
  die;
}

function request_param($id, $valid_options, $default) {
	global $_GET;


	if (!empty($_GET[$id]) && in_array($_GET[$id], $valid_options)) {
		$res = $_GET[$id];
	} else {
		$res = $default;
	}

	return $res;
}

function response_error($code=416, $message = 'Error fetching radar.') {
	http_response_code($code);
	echo $message;
	exit;
}

function promises_to_layers($promises) {
  return array_map(
    function ($response) {
      return ImageWorkshop::initFromString($response->getBody());
    },
    Promise\unwrap($promises)
  );
}