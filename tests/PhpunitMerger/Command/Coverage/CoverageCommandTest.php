<?php

declare(strict_types=1);

namespace Nimut\PhpunitMerger\Tests\Command\Coverage;

use Nimut\PhpunitMerger\Command\CoverageCommand;
use Nimut\PhpunitMerger\Tests\Command\AbstractCommandTestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;

class CoverageCommandTest extends AbstractCommandTestCase
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
        $output = $this->getMockBuilder(OutputInterface::class)
            ->getMock();
        $output->method('write')->willThrowException(new \Exception());

        $command = new CoverageCommand();
        $command->run($input, $output);

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
        $output = $this->getMockBuilder(OutputInterface::class)
            ->getMock();

        $command = new CoverageCommand();
        $command->run($input, $output);
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
        $output = $this->getMockBuilder(OutputInterface::class)
            ->getMock();

        $command = new CoverageCommand();
        $command->run($input, $output);

        $this->assertFileExists($this->logDirectory . $this->outputFile);
    }

    public function testCoverageWritesHtmlReportWithCustomBounds()
    {
        $this->outputFile = 'html/index.html';
        $this->assertOutputDirectoryNotExists();

        $input = new ArgvInput(
            [
                'coverage',
                $this->logDirectory . 'coverage/',
                '--html=' . $this->logDirectory . dirname($this->outputFile),
                '--lowUpperBound=20',
                '--highLowerBound=70',
            ]
        );
        $output = $this->getMockBuilder(OutputInterface::class)
            ->getMock();

        $command = new CoverageCommand();
        $command->run($input, $output);

        $this->assertFileExists($this->logDirectory . $this->outputFile);

        $content = file_get_contents($this->logDirectory . $this->outputFile);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('<strong>Low</strong>: 0% to 20%', $content);
            $this->assertStringContainsString('<strong>High</strong>: 70% to 100%', $content);
        } else {
            // Fallback for phpunit < 7.0
            $this->assertContains('<strong>Low</strong>: 0% to 20%', $content);
            $this->assertContains('<strong>High</strong>: 70% to 100%', $content);
        }
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
        $output = $this->getMockBuilder(OutputInterface::class)
            ->getMock();
        $output->method('write')->willThrowException(new \Exception());

        $command = new CoverageCommand();
        $command->run($input, $output);

        $this->assertFileExists($this->logDirectory . $this->outputFile);
        $this->assertFileExists($this->logDirectory . dirname($this->outputFile) . '/index.html');
    }
}
