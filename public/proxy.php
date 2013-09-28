<?php
$path = __DIR__ . '/../../basekit';

set_include_path(get_include_path() . PATH_SEPARATOR . $path);

spl_autoload_register(
    function ($class) use ($path) {
        if (false !== strpos($class, '_')) {
            $file = $path . '/library/' . str_replace('_', '/', $class) . '.php';
            require $file;
            return;
        }

        $file = $path . '/library/' . str_replace('\\', '/', $class) . '.php';
        require $file;
    }
);

$role = 'internal-developer';
$roles = array();

if (file_exists(__DIR__ . '/../config/roles.php')) {
    $roles = require __DIR__ . '/../config/roles.php';
}

$client = \BaseKit\Api\Client\BaseKitClient::factory(
    $roles[$role]
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
    $output['response'] = print_r($e->getResponse()->json(), 1);
} catch (Guzzle\Http\Exception\ServerErrorResponseException $e) {
    $output['responseHeaders'] = $e->getResponse()->getRawHeaders();
    $output['response'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($output);
