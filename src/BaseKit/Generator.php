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

            $tokens[] = $operation->getHttpMethod();

            $commandPath = '/api/' . self::normalise($name) . '.html';

            $relatedMethods = array();

            if (isset($related[$operation->getUri()])) {
                $relatedMethods = $related[$operation->getUri()];
                $relatedMethods = array_filter($relatedMethods, function ($relatedMethod) use ($operation) {
                    return $relatedMethod['method'] !== $operation->getHttpMethod();
                });
            }

            $typeahead[] = array(
                'value' => $operation->getHttpMethod() . ' ' . $operation->getUri(),
                'tokens' => array_values($tokens),
                'path' => $commandPath,
                'related' => $relatedMethods,
                'uri' => $operation->getUri(),
                'params' => array_map(function ($param) { return $param->toArray(); }, $operation->getParams()),
                'method' => $operation->getHttpMethod(),
                'summary' => $operation->getSummary(),
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
