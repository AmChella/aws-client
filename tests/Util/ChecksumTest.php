<?php

declare(strict_types=1);

namespace Chella\AwsService\Tests\Util;

use Chella\AwsService\Util\Checksum;
use Chella\AwsService\Tests\TestCase;

class ChecksumTest extends TestCase
{
    private $tempFile;

    protected function tearDown(): void
    {
        if ($this->tempFile && file_exists($this->tempFile)) {
            unlink($this->tempFile);
            $this->tempFile = null;
        }
        parent::tearDown();
    }

    public function testFromContent()
    {
        $content = 'Hello World!';
        $expectedChecksum = md5($content);
        $this->assertEquals($expectedChecksum, Checksum::fromContent($content));

        $contentEmpty = '';
        $expectedChecksumEmpty = md5($contentEmpty);
        $this->assertEquals($expectedChecksumEmpty, Checksum::fromContent($contentEmpty));
    }

    public function testFromFile()
    {
        $content = 'File content for checksum test.';
        $this->tempFile = tempnam(sys_get_temp_dir(), 'chk');
        file_put_contents($this->tempFile, $content);

        $expectedChecksum = md5_file($this->tempFile);
        $this->assertEquals($expectedChecksum, Checksum::fromFile($this->tempFile));
    }

    public function testFromFileNonExistent()
    {
        // md5_file() issues a warning and returns false for non-existent files.
        // We'll assert for false and suppress the warning during this test.
        $nonExistentFile = sys_get_temp_dir() . '/non_existent_file_' . uniqid();
        $actual = @Checksum::fromFile($nonExistentFile);
        $this->assertFalse($actual);
    }
}
