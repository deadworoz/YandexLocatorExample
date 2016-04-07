<?php

require_once ("config.php");
require_once ("vendor/autoload.php");

use \Symfony\Component\HttpFoundation\Request;
use \Silex\Application;

use GuzzleHttp\Psr7\Request as GuzzleRequest;

$app = new Application();

$app->get('/', function(Request $request) use ($app) {
    $clientIP = getClientIP($request);
    $location = getLocation($clientIP);
    return $app->json($location);
});

$app->run();


function getClientIP(Request$request) {
    return "127.0.0.1";
}

/**
 * @param string $clientIp
 * @return array
 */
function getLocation($clientIp) {
    //$guzzleRequest = new GuzzleRequest();
    return [
        'lat' => 666,
        'lon' => 666
    ];
}
