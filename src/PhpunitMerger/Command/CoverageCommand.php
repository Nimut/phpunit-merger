<?php

declare(strict_types=1);

namespace Nimut\PhpunitMerger\Command;

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report\Clover;
use SebastianBergmann\CodeCoverage\Report\Html\Facade;
use SebastianBergmann\CodeCoverage\Report\Thresholds;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
                InputArgument::OPTIONAL,
                'The file where to write the merged result. Default: Standard output'
            )
            ->addOption(
                'html',
                null,
                InputOption::VALUE_REQUIRED,
                'The directory where to write the code coverage report in HTML format'
            )
            ->addOption(
                'lowUpperBound',
                null,
                InputOption::VALUE_REQUIRED,
                'The lowUpperBound value to be used for HTML format'
            )
            ->addOption(
                'highLowerBound',
                null,
                InputOption::VALUE_REQUIRED,
                'The highLowerBound value to be used for HTML format'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $finder = new Finder();
        $finder->files()
            ->in(realpath($input->getArgument('directory')));

        $codeCoverage = $this->getCodeCoverage();

        foreach ($finder as $file) {
            $coverage = require $file->getRealPath();
            if (!$coverage instanceof CodeCoverage) {
                throw new \RuntimeException($file->getRealPath() . ' doesn\'t return a valid ' . CodeCoverage::class . ' object!');
            }
            $this->normalizeCoverage($coverage);
            $codeCoverage->merge($coverage);
        }

        $this->writeCodeCoverage($codeCoverage, $output, $input->getArgument('file'));
        $html = $input->getOption('html');
        if ($html !== null) {
            $lowUpperBound = (int)($input->getOption('lowUpperBound') ?: 50);
            $highLowerBound = (int)($input->getOption('highLowerBound') ?: 90);
            $this->writeHtmlReport($codeCoverage, $html, $lowUpperBound, $highLowerBound);
        }

        return 0;
    }

    private function getCodeCoverage()
    {
        $filter = new Filter();

        if (method_exists(Driver::class, 'forLineCoverage')) {
            $driver = Driver::forLineCoverage($filter);

            return new CodeCoverage($driver, $filter);
        }

        return new CodeCoverage((new Selector())->forLineCoverage($filter), $filter);
    }

    private function normalizeCoverage(CodeCoverage $coverage)
    {
        $tests = $coverage->getTests();
        foreach ($tests as &$test) {
            $test['fromTestcase'] = $test['fromTestcase'] ?? false;
        }
        $coverage->setTests($tests);
    }

    private function writeCodeCoverage(CodeCoverage $codeCoverage, OutputInterface $output, $file = null)
    {
        $writer = new Clover();
        $buffer = $writer->process($codeCoverage, $file);
        if ($file === null) {
            $output->write($buffer);
        }
    }

    private function writeHtmlReport(CodeCoverage $codeCoverage, string $destination, int $lowUpperBound, int $highLowerBound)
    {
        if (class_exists('SebastianBergmann\\CodeCoverage\\Report\\Thresholds')) {
            $writer = new Facade('', null, Thresholds::from($lowUpperBound, $highLowerBound));
        } else {
            $writer = new Facade($lowUpperBound, $highLowerBound);
        }

        $writer->process($codeCoverage, $destination);
    }
}
