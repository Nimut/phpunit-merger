<?php

declare(strict_types=1);

namespace Nimut\PhpunitMerger\Tests\Command\Coverage;

use Nimut\PhpunitMerger\Command\CoverageCommand;
use Nimut\PhpunitMerger\Tests\Command\AbstractCommandTest;
use Prophecy\Argument;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;

class CoverageCommandTest extends AbstractCommandTest
{
    /**
     * @var string
     */
    protected $outputFile = 'coverage.xml';

    public function testCoverageWritesOutputFile()
    {
        $this->assertOutputFileNotExists();

        $input = new ArgvInput(
            [
                'coverage',
                $this->logDirectory . 'coverage/',
                $this->logDirectory . $this->outputFile,
            ]
        );
        $output = $this->prophesize(OutputInterface::class);
        $output->write(Argument::any())->shouldNotBeCalled();

        $command = new CoverageCommand();
        $command->run($input, $output->reveal());

        $this->assertFileExists($this->logDirectory . $this->outputFile);
    }

    public function testCoverageWritesStandardOutput()
    {
        $this->assertOutputFileNotExists();

        $input = new ArgvInput(
            [
                'coverage',
                $this->logDirectory . 'coverage/',
            ]
        );
        $output = $this->prophesize(OutputInterface::class);
        $output->write(Argument::type('string'))->shouldBeCalled();

        $command = new CoverageCommand();
        $command->run($input, $output->reveal());
    }

    public function testCoverageWritesHtmlReport()
    {
        $this->outputFile = 'html/index.html';
        $this->assertOutputDirectoryNotExists();

        $input = new ArgvInput(
            [
                'coverage',
                $this->logDirectory . 'coverage/',
                '--html=' . $this->logDirectory . dirname($this->outputFile),
            ]
        );
        $output = $this->prophesize(OutputInterface::class);
        $output->write(Argument::type('string'))->shouldBeCalled();

        $command = new CoverageCommand();
        $command->run($input, $output->reveal());

        $this->assertFileExists($this->logDirectory . $this->outputFile);
    }

    public function testCoverageWritesOutputFileAndHtmlReport()
    {
        $this->outputFile = 'html/coverage.xml';
        $this->assertOutputFileNotExists();
        $this->assertOutputDirectoryNotExists();

        $input = new ArgvInput(
            [
                'coverage',
                $this->logDirectory . 'coverage/',
                '--html=' . $this->logDirectory . dirname($this->outputFile),
                $this->logDirectory . $this->outputFile,
            ]
        );
        $output = $this->prophesize(OutputInterface::class);
        $output->write(Argument::any())->shouldNotBeCalled();

        $command = new CoverageCommand();
        $command->run($input, $output->reveal());

        $this->assertFileExists($this->logDirectory . $this->outputFile);
        $this->assertFileExists($this->logDirectory . dirname($this->outputFile) . '/index.html');
    }
}
