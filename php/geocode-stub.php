<?php
/*
 * Local development stand-in for the Google Geocoding API, which now
 * requires an API key. Returns a response in Google's format with fixed
 * coordinates (Helsinki). Only used when the site runs on localhost,
 * see js/kattellaan-dev.js.
 */

header("Content-Type: application/json; charset=utf-8");

$lat = 60.1699;
$lng = 24.9384;

if (!empty($_GET['latlng'])) {
	$parts = explode(",", $_GET['latlng']);
	if (count($parts) == 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
		$lat = (float)$parts[0];
		$lng = (float)$parts[1];
	}
}

echo json_encode(array(
	"status" => "OK",
	"results" => array(array(
		"formatted_address" => isset($_GET['address']) ? str_replace("+", " ", $_GET['address']) : "Helsinki, Finland",
		"geometry" => array("location" => array("lat" => $lat, "lng" => $lng)),
		"address_components" => array(
			array("long_name" => "Helsinki", "short_name" => "Helsinki", "types" => array("locality", "political")),
			array("long_name" => "Finland", "short_name" => "FI", "types" => array("country", "political"))
		)
	))
));
