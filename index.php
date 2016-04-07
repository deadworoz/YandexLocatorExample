<?php

require_once ("config.php");
require_once ("vendor/autoload.php");

use \Symfony\Component\HttpFoundation\Request;
use \Silex\Application;

$app = new Application();
//$app['debug'] = true;

$app->get('/', function(Request $request) use ($app) {
    try {
        $clientIP = getClientIP($request);
        $location = getLocation($clientIP);
        $response = $app->json($location);
    } catch (Exception $e) {
        $response = $e->getMessage();
    }
    return $response;
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
    $ipValidatorOptions = [FILTER_FLAG_IPV4, FILTER_FLAG_NO_PRIV_RANGE, FILTER_FLAG_NO_RES_RANGE];

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

use GuzzleHttp\Client;
/**
 * @param string $clientIp
 * @return array
 * @throws Exception
 */
function getLocation($clientIp) {
    $client = new Client([
        'base_uri' => 'http://api.lbs.yandex.net'
    ]);
    $jsonData = new stdClass();
    $jsonData->common = new stdClass();
    $jsonData->common->version = '1.0';
    $jsonData->common->api_key = YANDEX_LOCATOR_KEY;
    $jsonData->ip = new stdClass();
    $jsonData->ip->address_v4 = $clientIp;
    $response = $client->post('/geolocation',[
        'form_params' => [
            'json' => json_encode($jsonData)
        ]
    ]);
    $code = $response->getStatusCode();
    if ($code != 200) {
        // @todo: ошибки должны быть более специфичными
        throw new Exception('Беда, насалькика!' . PHP_EOL . $response->getReasonPhrase());
    }
    $location = json_decode($response->getBody()->getContents());
    if (!empty($location->error)) {
        // @todo: ошибки должны быть более специфичными
        throw new Exception('Беда, насалькика!' . PHP_EOL . $location->error);
    }

    return [
        'lat' => $location->position->latitude,
        'lon' => $location->position->longitude,
        'additional_message' => $location->position->precision >= 100000 ? 'Reliably locate failed' : ''
    ];
}


