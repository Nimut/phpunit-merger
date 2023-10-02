<?php

declare(strict_types=1);

namespace Nimut\PhpunitMerger\Tests\Command\Log;

use Nimut\PhpunitMerger\Command\LogCommand;
use Nimut\PhpunitMerger\Tests\Command\AbstractCommandTestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;

class LogCommandTest extends AbstractCommandTestCase
{
    /**
     * @var string
     */
    protected $outputFile = 'log.xml';

    public function testRunMergesCoverage()
    {
        $this->assertOutputFileNotExists();

        $input = new ArgvInput(
            [
                'log',
                $this->logDirectory . 'log/',
                $this->logDirectory . $this->outputFile,
            ]
        );
        $output = $this->getMockBuilder(OutputInterface::class)->getMock();

        $command = new LogCommand();
        $command->run($input, $output);

        $this->assertFileExists($this->logDirectory . $this->outputFile);
    }
}
