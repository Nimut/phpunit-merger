<?php
namespace Nimut\PhpunitMerger\Command;

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Clover;
use SebastianBergmann\CodeCoverage\Report\Html\Facade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class CoverageCommand extends Command
{
    protected function configure()
    {
        $this->setName('coverage')
            ->setDescription('Merges multiple PHPUnit coverage php files into one')
            ->addArgument(
                'directory',
                InputArgument::REQUIRED,
                'The directory containing PHPUnit coverage php files'
            )
            ->addArgument(
                'output',
                InputArgument::REQUIRED,
                'The output where to write the merged result. Can be foo.xml or simple directory name'
            )
            ->addArgument(
                'type',
                InputArgument::OPTIONAL,
                'Type of the merged result. This actually support Clover and HTML output',
                'clover'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();
        $finder->files()
            ->in(realpath($input->getArgument('directory')));

        $codeCoverage = new CodeCoverage();

        foreach ($finder as $file) {
            $coverage = require $file->getRealPath();
            $codeCoverage->merge($coverage);
        }

        $writer = $input->getArgument('type') === 'clover' ? new Clover() : new Facade();
        $writer->process($codeCoverage, $input->getArgument('output'));
    }
}
