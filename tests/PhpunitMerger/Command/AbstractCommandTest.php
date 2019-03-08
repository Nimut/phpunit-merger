<?php
namespace Nimut\PhpunitMerger\Tests\Command;

use PHPUnit\Framework\TestCase;

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

    public function testFileNotExists()
    {
        if (file_exists($this->logDirectory . $this->outputFile)) {
            unlink($this->logDirectory . $this->outputFile);
        }

        $this->assertFileNotExists($this->logDirectory . $this->outputFile);
    }
}
