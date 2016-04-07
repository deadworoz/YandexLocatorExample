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


/**
 * @param Request $request
 * @return string
 *
 * Есть RFC для Forwarded HTTP, заголовок Forwarded: for <ip>
 * @link https://tools.ietf.org/html/rfc7239
 * @link http://stackoverflow.com/questions/7445592/what-is-the-difference-between-http-client-ip-and-http-x-forwarded-for
 *
 * Можно определять внешний IP-адрес клиента с помощью нестандартизированных заголовков:
 * X-Forwarded-For, X-Client-IP, X-Real-IP
 * @link http://stackoverflow.com/questions/527638/getting-the-client-ip-address-remote-addr-http-x-forwarded-for-what-else-coul
 */
function getClientIP(Request$request) {
    $ipValidatorOptions = [FILTER_FLAG_IPV4, FILTER_FLAG_IPV6, FILTER_FLAG_NO_PRIV_RANGE, FILTER_FLAG_NO_RES_RANGE];

    $ip = $request->headers->get('X-Client-IP');
    if (filter_var($ip, FILTER_VALIDATE_IP, $ipValidatorOptions) !== false) {
        return $ip;
    }

    $ip = $request->headers->get('X-Real-IP');
    if (filter_var($ip, FILTER_VALIDATE_IP, $ipValidatorOptions) !== false) {
        return $ip;
    }

    // @todo: вообще-то, нужно обработать случай с несколькими IP
    $ip = $request->headers->get('X-Forwarded-For');
    if (filter_var($ip, FILTER_VALIDATE_IP, $ipValidatorOptions) !== false) {
        return $ip;
    }

    // -- О ужас! Ничего не получилось,мы все пропали!
    // -- Помолимся братья и сёстры нашему древнему богу REMOTE_ADDR!
    return $_SERVER['REMOTE_ADDR'];
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


