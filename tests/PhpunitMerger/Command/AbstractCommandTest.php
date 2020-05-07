<?php
namespace Nimut\PhpunitMerger\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractCommandTest extends TestCase
{
    /**
     * @var string
     */
    protected $logDirectory = __DIR__ . '/../../../.Log/';

    /**
     * @var string
     */
    protected $outputFile = 'foo.bar';

    public function assertOutputFileNotExists()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->logDirectory . $this->outputFile);

        $this->assertFileDoesNotExist($this->logDirectory . $this->outputFile);
    }

    public function assertOutputDirectoryNotExists()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->logDirectory . dirname($this->outputFile));

        $this->assertDirectoryDoesNotExist($this->logDirectory . dirname($this->outputFile));
    }
}
