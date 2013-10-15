<?php
namespace BaseKit\Command;

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
        $path = $input->getArgument('path');

        $loader = new \Twig_Loader_Filesystem(__DIR__ . '/../../../templates');
        $twig = new \Twig_Environment($loader);

        $outputPath = $input->getArgument('outputPath');
        $outputPath = realpath($outputPath);

        $generator = new \BaseKit\Generator($twig);
        $files = $generator->generate($path, $outputPath);

        $output->writeln('Generated ' . count($files) . ' files');
    }
}
