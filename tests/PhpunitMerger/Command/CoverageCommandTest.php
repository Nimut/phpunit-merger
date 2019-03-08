<?php
namespace Nimut\PhpunitMerger\Tests\Command;

use Nimut\PhpunitMerger\Command\CoverageCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class CoverageCommandTest extends AbstractCommandTest
{
    /**
     * @var string
     */
    protected $outputFile = 'coverage.xml';

    /**
     * @depends testFileNotExists
     */
    public function testRunMergesCoverage()
    {
        $input = new ArgvInput(
            [
                'coverage',
                $this->logDirectory . 'coverage/',
                $this->logDirectory . $this->outputFile,
            ]
        );
        $output = new ConsoleOutput();

        $command = new CoverageCommand();
        $command->run($input, $output);

        $this->assertFileExists($this->logDirectory . $this->outputFile);
    }
}
