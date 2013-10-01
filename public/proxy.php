<?php
use Guzzle\Common\Collection;
use Guzzle\Plugin\Oauth\OauthPlugin;
use Guzzle\Service\Client;
use Guzzle\Service\Description\ServiceDescription;

require __DIR__ . '/../vendor/autoload.php';

$config = array();
$roles = array();

$configPath = __DIR__ . '/../config/config.php';

if (!file_exists($configPath)) {
    throw new Exception(sprintf('Config file does %s does not exist', $configPath));
}

$config = require $configPath;

$required = array(
    'base_url',
    'consumer_key',
    'consumer_secret',
    'token',
    'token_secret',
);

$default = array();

$role = $config['defaultRole'];

$guzzleConfig = Collection::fromConfig($config['roles'][$role], $default, $required);
$client = new Guzzle\Service\Client($guzzleConfig->get('base_url'), $guzzleConfig);
$client->addSubscriber(new OauthPlugin($guzzleConfig->toArray()));
$client->setDescription(
    ServiceDescription::factory(
        $config['serviceDescriptionPath']
    )
);

$proxiedRequest = json_decode(file_get_contents('php://input'), true);
$method = strtolower($proxiedRequest['method']);

$output = array();
$body = null;
$headers = array();

$uri = $proxiedRequest['uri'];

if (in_array($method, array('post', 'put'))) {
    $headers = array('Content-Type' => 'application/json');
    $body = json_encode($proxiedRequest['params']);
} elseif ($method === 'get' && count($proxiedRequest['params']) !== 0) {
    $uri .= '?' . http_build_query($proxiedRequest['params']);
}

try {
    $request = call_user_func_array(array($client, $method), array($uri, $headers, $body));

    $output['requestHeaders'] = $request->getRawHeaders();

    if ($request instanceof Guzzle\Http\Message\EntityEnclosingRequest) {
        $output['request'] = (string) $request->getBody();
    }

    $response = $request->send();
    $output['responseHeaders'] = $response->getRawHeaders();

    try {
        $output['response'] = print_r($response->json(), 1);
    } catch (Guzzle\Common\Exception\RuntimeException $e) {
        $output['response'] = $response->getBody(true);
    }
} catch (Guzzle\Http\Exception\ClientErrorResponseException $e) {
    $output['responseHeaders'] = $e->getResponse()->getRawHeaders();

    try {
        $output['response'] = print_r($e->getResponse()->json(), 1);
    } catch (Guzzle\Common\Exception\RuntimeException $runtimeException) {
        $output['response'] = $e->getResponse()->getBody(true);
    }
} catch (Guzzle\Http\Exception\ServerErrorResponseException $e) {
    $output['responseHeaders'] = $e->getResponse()->getRawHeaders();
    $output['response'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($output);
