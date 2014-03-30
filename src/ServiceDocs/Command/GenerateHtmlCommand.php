<?php
namespace ServiceDocs\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateHtmlCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('generate:html')
            ->setDescription('Generate HTML from Guzzle Service Description')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Service description path'
            )
            ->addArgument(
                'outputPath',
                InputArgument::REQUIRED,
                'Path to write HTML'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->prepareStylesheet();

        $path = $input->getArgument('path');

        $loader = new \Twig_Loader_Filesystem(__DIR__ . '/../../../templates');
        $twig = new \Twig_Environment($loader);

        $outputPath = $input->getArgument('outputPath');

        $generator = new \ServiceDocs\Generator($twig);
        $files = $generator->generate($path, $outputPath);

        $output->writeln('Generated ' . count($files) . ' files');
    }

    private function prepareStylesheet()
    {
        $inputPath = __DIR__ . '/../../../public/stylesheets';
        $sc = new \scssc();
        $sc->setImportPaths(array($inputPath));
        $sc->setFormatter('scss_formatter_compressed');

        $outputPath = __DIR__ . '/../../../public/css';
        if (!file_exists($outputPath)) {
            mkdir($outputPath);
        }

        $css = $sc->compile('@import "application.scss"'); // Srsly!?

        file_put_contents($outputPath . '/application.css', $css);
    }
}
