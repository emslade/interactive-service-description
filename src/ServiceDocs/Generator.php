<?php
namespace ServiceDocs;

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

        $filter = new \Twig_SimpleFilter('formatUri', array(__CLASS__, 'formatUri'));
        $this->twig->addFilter($filter);

        $filter = new \Twig_SimpleFilter('formatTitle', array(__CLASS__, 'formatTitle'));
        $this->twig->addFilter($filter);

        $typehead = array();
        $output = array();

        $description = ServiceDescription::factory($path);
        $descriptionName = $description->getName();

        $related = array();

        $directory = array();
        $timestamp = new \DateTime('now', new \DateTimeZone('UTC'));

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
            }

            $formattedTitle = self::formatTitle($name);
            $formattedUri = preg_replace('~(\{[^\}]+\})~', '<span class="uri-param">$1</span>', $operation->getUri());

            $params = array_map(function ($param) { return $param->toArray(); }, $operation->getParams());

            $paramLocations = array(
                'uri' => 'URI',
                'query' => 'Query',
                'header' => 'Header',
                'body' => 'Body',
                'postField' => 'Post field',
                'postFile' => 'Post file',
                'json' => 'JSON',
                'xml' => 'XML',
                'responseBody' => 'Response body'
            );
            $groupedParams = array();

            foreach ($paramLocations as $key => $location) {
                $groupedParams[$key] = array(
                    'location' => $location,
                    'params' => array_filter(
                        $params,
                        function ($param) use ($key) {
                            return isset($param['location']) && $param['location'] === $key;
                        }
                    ),
                );
            }

            $groupedParams['other'] = array(
                'location' => 'Other',
                'params' => array_filter(
                    $params,
                    function ($param) use ($paramLocations) {
                        return !isset($param['location']) || (isset($param['location']) && !array_key_exists($param['location'], $paramLocations));
                    }
                )
            );

            $typeahead[] = array(
                'command' => $name,
                'value' => $operation->getHttpMethod() . ' ' . $operation->getUri(),
                'tokens' => array_values($tokens),
                'path' => $commandPath,
                'related' => $relatedMethods,
                'uri' => $operation->getUri(),
                'groupedParams' => $groupedParams,
                'method' => $operation->getHttpMethod(),
                'summary' => $operation->getSummary(),
                'formattedTitle' => $formattedTitle,
                'formattedUri' => $formattedUri,
                'directory' => self::getBaseResource($operation->getUri()),
                'generated' => $timestamp,
            );

            file_put_contents($outputPath . $commandPath, $this->twig->render('command.twig', $typeahead[count($typeahead) - 1]));

            $output[] = 'file';
        }

        ksort($directory);
        $context = array(
            'descriptionName' => $descriptionName,
            'baseResources' => array_keys($directory),
            'generated' => $timestamp,
        );

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

        $context = array(
            'baseResources' => array_keys($directory),
            'generated' => $timestamp,
        );

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
                        'generated' => $generated,
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

    public static function formatTitle($str)
    {
        $str = str_replace(array('/', '\''), '', $str);
        $str = preg_split('/(?=[A-Z])/', $str, -1, PREG_SPLIT_NO_EMPTY);
        $str = implode(' ', $str);
        return $str;
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

    public static function formatUri($uri)
    {
        return preg_replace('~(\{[^\}]+\})~', '<span class="uri-param">$1</span>', $uri);
    }
}
