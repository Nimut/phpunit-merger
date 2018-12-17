<?php
namespace Nimut\PhpunitMerger\Tests\Command;

use Nimut\PhpunitMerger\Command\CoverageCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class CoverageCommandTest extends TestCase
{
    /**
     * @var string
     */
    private $logDirectory = __DIR__ . '/../../../.Log/';

    /**
     * @var string
     */
    private $output;

    /**
     * @var string
     */
    private $type = 'clover';

    public function setUp()
    {
        parent::setUp();
    }

    public function testRunMergesCoverageToClover()
    {
        $this->output = 'coverage.xml';
        $this->clearOutput();

        $this->assertFileNotExists($this->logDirectory . $this->output);

        $this->execCommand();

        $this->assertFileExists($this->logDirectory . $this->output);
    }

    public function testRunMergesCoverageToHTML()
    {
        $this->output = 'html_coverage/';
        $this->type = 'html';
        $this->clearOutput();

        $this->assertFileNotExists($this->logDirectory . $this->output);

        $this->execCommand();

        $this->assertFileExists($this->logDirectory . $this->output);
    }

    private function clearOutput()
    {
        $outputPath = $this->logDirectory . $this->output;

        if (file_exists($outputPath) || is_dir($outputPath)) {
            $this->type === 'clover'
                ? unlink($outputPath)
                : $this->recursiveDeleteDir($outputPath);
        }
    }

    private function recursiveDeleteDir($directory)
    {
        foreach (scandir($directory) as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }

            if (is_dir("$directory/$file")) {
                $this->recursiveDeleteDir("$directory/$file");
            } else {
                unlink("$directory/$file");
            }
        }

        rmdir($directory);
    }

    private function execCommand()
    {
        $input = new ArgvInput(
            [
                'coverage',
                $this->logDirectory . 'coverage/',
                $this->logDirectory . $this->output,
                $this->type,
            ]
        );
        $output = new ConsoleOutput();

        $command = new CoverageCommand();
        $command->run($input, $output);
    }
}
