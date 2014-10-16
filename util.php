<?php

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