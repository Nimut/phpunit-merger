<?php

declare(strict_types=1);

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
        self::assertDirectoryExists($this->logDirectory, $this->logDirectory . ' does not exists');
        $filesystem->remove($this->logDirectory . $this->outputFile);

        $this->assertFileNotExists($this->logDirectory . $this->outputFile);
    }

    public function assertOutputDirectoryNotExists()
    {
        self::assertDirectoryExists($this->logDirectory, $this->logDirectory . ' does not exists');
        $filesystem = new Filesystem();
        $filesystem->remove($this->logDirectory . dirname($this->outputFile));

        $this->assertDirectoryNotExists($this->logDirectory . dirname($this->outputFile));
    }
}
