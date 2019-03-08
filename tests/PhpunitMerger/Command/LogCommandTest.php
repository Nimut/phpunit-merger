<?php
namespace Nimut\PhpunitMerger\Tests\Command;

use Nimut\PhpunitMerger\Command\LogCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class LogCommandTest extends AbstractCommandTest
{
    /**
     * @var string
     */
    protected $outputFile = 'log.xml';

    /**
     * @depends testFileNotExists
     */
    public function testRunMergesCoverage()
    {
        $input = new ArgvInput(
            [
                'log',
                $this->logDirectory . 'log/',
                $this->logDirectory . $this->outputFile,
            ]
        );
        $output = new ConsoleOutput();

        $command = new LogCommand();
        $command->run($input, $output);

        $this->assertFileExists($this->logDirectory . $this->outputFile);
    }
}
