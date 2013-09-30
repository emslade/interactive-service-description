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

        $typehead = array();
        $output = array();

        $description = ServiceDescription::factory($path);

        $related = array();

        foreach ($description->getOperations() as $name => $operation) {
            $related[$operation->getUri()][] = array('method' => $operation->getHttpMethod(), 'name' => $name, 'link' => '/api/' . self::normalise($name) . '.html');
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

        file_put_contents($outputPath . '/data/commands.json', json_encode($typeahead));

        $index = $this->twig->render('commands.twig', $context);

        file_put_contents($outputPath . '/index.html', $index);

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
}
