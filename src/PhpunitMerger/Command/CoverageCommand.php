<?php
namespace Nimut\PhpunitMerger\Command;

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Clover;
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
                'file',
                InputArgument::REQUIRED,
                'The file where to write the merged result'
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

        $writer = new Clover();
        $writer->process($codeCoverage, $input->getArgument('file'));
    }
}
