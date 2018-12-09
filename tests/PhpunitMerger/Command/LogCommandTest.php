<?php
namespace Nimut\PhpunitMerger\Tests\Command;

use Nimut\PhpunitMerger\Command\LogCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class LogCommandTest extends TestCase
{
    /**
     * @var string
     */
    private $logDirectory = __DIR__ . '/../../../.Log/';

    /**
     * @var string
     */
    private $outputFile = 'log.xml';

    public function setUp()
    {
        parent::setUp();

        if (file_exists($this->logDirectory . $this->outputFile)) {
            unlink($this->logDirectory . $this->outputFile);
        }
    }

    public function testRunMergesCoverage()
    {
        $this->assertFileNotExists($this->logDirectory . $this->outputFile);

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
