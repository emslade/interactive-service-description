<?php
namespace BaseKit;

use Guzzle\Service\Description\ServiceDescription;

class Generator
{
    private $twig;

    public function __construct($twig)
    {
        $this->twig = $twig;
    }

    public function generate($path, $outputPath)
    {
        if (!file_exists($outputPath . '/api')) {
            mkdir($outputPath . '/api');
        }

        $filter = new \Twig_SimpleFilter('uriToPath', array(__CLASS__, 'normalise'));
        $this->twig->addFilter($filter);

        $typehead = array();
        $output = array();

        $description = ServiceDescription::factory($path);

        $related = array();

        $directory = array();

        foreach ($description->getOperations() as $name => $operation) {
            $related[$operation->getUri()][$operation->getHttpMethod()] = array('method' => $operation->getHttpMethod(), 'name' => $name, 'link' => '/api/' . self::normalise($name) . '.html');
            $directory[self::getBaseResource($operation->getUri())][$operation->getUri()][] = $operation;
        }

        foreach ($description->getOperations() as $name => $operation) {
            $parts = explode('/', ltrim($operation->getUri(), '/'));

            $tokens = array_filter($parts, function($part) {
                return !empty($part) && false === strpos($part, '{');
            });

            $tokens[] = $operation->getUri();
            $tokens[] = $operation->getHttpMethod();

            $commandPath = '/api/' . self::normalise($name) . '.html';

            $relatedMethods = array();

            if (isset($related[$operation->getUri()])) {
                $relatedMethods = $related[$operation->getUri()];
                $relatedMethods = array_filter($relatedMethods, function ($relatedMethod) use ($operation) {
                    return $relatedMethod['method'] !== $operation->getHttpMethod();
                });
            }

            $formattedUri = preg_replace('~(\{[^\}]+\})~', '<span class="uri-param">$1</span>', $operation->getUri());

            $params = array_map(function ($param) { return $param->toArray(); }, $operation->getParams());
            $uriParams = array_filter($params, function ($param) { return isset($param['location']) && $param['location'] === 'uri'; });
            $nonUriParams = array_filter($params, function ($param) { return isset($param['location']) && $param['location'] !== 'uri'; });
            $requiredParams = array_filter($nonUriParams, function ($param) { return isset($param['required']) && $param['required'] === true; });
            $optionalParams = array_filter($nonUriParams, function ($param) { return !isset($param['required']) || (isset($param['required']) && $param['required'] === false); });

            $typeahead[] = array(
                'value' => $operation->getHttpMethod() . ' ' . $operation->getUri(),
                'tokens' => array_values($tokens),
                'path' => $commandPath,
                'related' => $relatedMethods,
                'uri' => $operation->getUri(),
                'params' => array_merge($uriParams, $requiredParams, $optionalParams),
                'method' => $operation->getHttpMethod(),
                'summary' => $operation->getSummary(),
                'formattedUri' => $formattedUri,
            );

            file_put_contents($outputPath . $commandPath, $this->twig->render('command.twig', $typeahead[count($typeahead) - 1]));

            $output[] = 'file';
        }

        $context = array();

        $dataPath = $outputPath . '/data';

        if (!file_exists($dataPath)) {
            mkdir($dataPath);
        }

        usort($typeahead, function($a, $b) {
            return (strlen($a['uri']) < strlen($b['uri'])) ? -1 : 1;
        });

        file_put_contents($dataPath . '/commands.json', json_encode($typeahead));

        $index = $this->twig->render('commands.twig', $context);

        file_put_contents($outputPath . '/index.html', $index);

        ksort($directory);

        $context = array();

        foreach ($directory as $dir => $uris) {
            if (count($uris) === 1) {
                foreach ($uris as $uri => $methods) {
                    if (count($methods) === 1) {
                        $context['operations'][] = $methods;
                        continue 2;
                    }
                }
            }

            $context['baseResources'][] = $dir;
        }

        if (!file_exists($outputPath . '/directory')) {
            mkdir($outputPath . '/directory');
        }

        file_put_contents($outputPath . '/directory/index.html', $this->twig->render('directory.twig', $context));

        foreach ($directory as $dir => $operations) {
            uksort($operations, function($a, $b) {
                return (strlen($a) < strlen($b)) ? -1 : 1;
            });

            file_put_contents(
                $outputPath . '/directory/' . $dir . '.html',
                $this->twig->render(
                    'subdirectory.twig',
                    array(
                        'dir' => $dir,
                        'operations' => $operations,
                    )
                )
            );
        }

        return $output;
    }

    public static function normalise($uri)
    {
        $uri = str_replace(array('/', '\''), '', $uri);
        $uri = preg_split('/(?=[A-Z])/', $uri, -1, PREG_SPLIT_NO_EMPTY);
        $uri = implode('-', $uri);
        $uri = strtolower($uri);
        return $uri;
    }

    public static function getBaseResource($uri)
    {
        $uri = ltrim($uri, '/');
        $base = $uri;

        if (false !== ($pos = strpos($uri, '/'))) {
            $base = substr($uri, 0, $pos);
        }

        return $base;
    }
}
