<?php

declare(strict_types=1);

namespace Chella\AwsService\Tests\Service;

use Chella\AwsService\Service\FileSystem;
use Chella\AwsService\Tests\TestCase;
use Chella\AwsService\Exception\AssertNotFoundException; // Assuming this exception exists
use Chella\AwsService\Exception\AssertionException; // For other potential assertions if used by helpers

class FileSystemTest extends TestCase
{
    private $baseTestDir;
    private $fileSystem;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a unique temporary directory for each test
        $this->baseTestDir = sys_get_temp_dir() . '/filesystem_test_' . uniqid();
        if (!mkdir($this->baseTestDir, 0777, true) && !is_dir($this->baseTestDir)) {
            throw new \RuntimeException("Could not create temp directory: " . $this->baseTestDir);
        }
        // Initialize FileSystem with create = true to allow directory creation
        $this->fileSystem = new FileSystem($this->baseTestDir, true, 0777);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->baseTestDir)) {
            // Recursively delete the temporary directory
            $this->deleteDirectoryRecursive($this->baseTestDir);
        }
        parent::tearDown();
    }

    private function deleteDirectoryRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }
        rmdir($dir);
    }

    public function testConstructorCreatesDirectory()
    {
        $testDir = $this->baseTestDir . '/new_dir';
        new FileSystem($testDir, true);
        $this->assertDirectoryExists($testDir);
    }

    public function testConstructorWithExistingSymlink()
    {
        $targetDir = $this->baseTestDir . '/actual_dir';
        mkdir($targetDir);
        $linkDir = $this->baseTestDir . '/link_dir';
        symlink($targetDir, $linkDir);

        $fs = new FileSystem($linkDir);
        // Internally, directory should be resolved to the real path
        // This is hard to assert directly without exposing internal state or specific behavior dependent on it.
        // We'll rely on other tests passing when using a symlinked base.
        $this->assertTrue(true);
        $fs->write('test.txt','hello');
        $this->assertFileExists($targetDir . '/test.txt');

    }

    public function testWriteAndRead()
    {
        $key = 'test.txt';
        $content = 'Hello World!';
        $this->fileSystem->write($key, $content);
        $this->assertFileExists($this->baseTestDir . '/' . $key);
        $this->assertEquals($content, $this->fileSystem->read($key));
    }

    public function testWriteInSubdirectory()
    {
        $key = 'sub/dir/test.txt';
        $content = 'Nested Hello!';
        $this->fileSystem->write($key, $content);
        $this->assertFileExists($this->baseTestDir . '/' . $key);
        $this->assertEquals($content, $this->fileSystem->read($key));
    }

    public function testExists()
    {
        $this->assertFalse($this->fileSystem->exists('nonexistent.txt'));
        $this->fileSystem->write('existent.txt', 'data');
        $this->assertTrue($this->fileSystem->exists('existent.txt'));
    }

    public function testDeleteFile()
    {
        $key = 'to_delete.txt';
        $this->fileSystem->write($key, 'data');
        $this->assertTrue($this->fileSystem->exists($key));
        $this->fileSystem->delete($key);
        $this->assertFalse($this->fileSystem->exists($key));
    }

    public function testDeleteDirectory()
    {
        $key = 'subdir_to_delete';
        mkdir($this->baseTestDir . '/' . $key);
        $this->assertTrue($this->fileSystem->isDirectory($key));
        $this->fileSystem->delete($key);
        $this->assertFalse($this->fileSystem->isDirectory($key)); // also checks existence implicitly
    }


    public function testRename()
    {
        $sourceKey = 'source.txt';
        $targetKey = 'target.txt';
        $content = 'File to rename';

        $this->fileSystem->write($sourceKey, $content);
        $this->assertTrue($this->fileSystem->exists($sourceKey));
        $this->assertFalse($this->fileSystem->exists($targetKey));

        $this->fileSystem->rename($sourceKey, $targetKey);

        $this->assertFalse($this->fileSystem->exists($sourceKey));
        $this->assertTrue($this->fileSystem->exists($targetKey));
        $this->assertEquals($content, $this->fileSystem->read($targetKey));
    }

    public function testRenameToNewSubdirectory()
    {
        $sourceKey = 'source_move.txt';
        $targetKey = 'new_sub/target_move.txt';
        $content = 'File to move';
        $this->fileSystem->write($sourceKey, $content);
        $this->fileSystem->rename($sourceKey, $targetKey);
        $this->assertTrue($this->fileSystem->exists($targetKey));
        $this->assertEquals($content, $this->fileSystem->read($targetKey));
        $this->assertFileExists($this->baseTestDir . '/new_sub');
    }

    public function testKeys()
    {
        $this->assertEquals([], $this->fileSystem->keys());

        $this->fileSystem->write('file1.txt', 'd');
        $this->fileSystem->write('sub/file2.txt', 'd');
        $this->fileSystem->write('another_sub/file3.txt', 'd');
        // Create an empty directory, should not be listed by keys() as it lists files
        mkdir($this->baseTestDir . '/empty_dir');


        $expectedKeys = [
            'another_sub/file3.txt',
            'file1.txt',
            'sub/file2.txt',
        ];
        $actualKeys = $this->fileSystem->keys();
        sort($actualKeys); // Ensure order for comparison

        $this->assertEquals($expectedKeys, $actualKeys);
    }

    public function testKeysWithEmptyBaseDir()
    {
        // Ensure keys() returns an empty array if the base directory itself doesn't exist
        // and creation is not allowed for it during keys() call (though setUp creates it).
        // This test is more about ensuring robustness if the directory vanished.
        $tempNonExistentDir = $this->baseTestDir . '/non_existent_base';
        $fs = new FileSystem($tempNonExistentDir, false); // create = false
        $this->assertEquals([], $fs->keys());
    }

    public function testMtime()
    {
        $key = 'time_test.txt';
        $startTime = time();
        $this->fileSystem->write($key, 'data');
        $mtime = $this->fileSystem->mtime($key);
        $this->assertGreaterThanOrEqual($startTime -1, $mtime); // Allow for slight timing variance
        $this->assertLessThanOrEqual(time() +1 , $mtime);
    }

    public function testSize()
    {
        $key = 'size_test.txt';
        $content = '12345';
        $this->fileSystem->write($key, $content);
        $this->assertEquals(strlen($content), $this->fileSystem->size($key));
    }

    public function testIsDirectory()
    {
        $fileKey = 'is_dir_file.txt';
        $dirKey = 'is_dir_subdir';

        $this->fileSystem->write($fileKey, 'data');
        mkdir($this->baseTestDir . '/' . $dirKey);

        $this->assertFalse($this->fileSystem->isDirectory($fileKey));
        $this->assertTrue($this->fileSystem->isDirectory($dirKey));
        $this->assertFalse($this->fileSystem->isDirectory('non_existent_dir'));
    }

    public function testChecksum()
    {
        $key = 'checksum_test.txt';
        $content = 'Hello for checksum';
        $this->fileSystem->write($key, $content);

        $expectedChecksum = md5($content); // Assuming Cheksum::fromFile uses md5
        $this->assertEquals($expectedChecksum, $this->fileSystem->checksum($key));
    }

    public function testMimeType()
    {
        // finfo needs the file to exist
        $key = 'mimetype.txt';
        $this->fileSystem->write($key, 'Simple text file.');
        $this->assertEquals('text/plain', $this->fileSystem->mimeType($key));

        $key2 = 'mimetype2.html';
        $this->fileSystem->write($key2, '<h1>Hello</h1>');
        // Mime type can vary slightly (e.g. text/html; charset=us-ascii)
        $this->assertStringStartsWith('text/html', $this->fileSystem->mimeType($key2));
    }

    public function testComputeKey()
    {
        $this->assertEquals('file.txt', $this->fileSystem->computeKey($this->baseTestDir . '/file.txt'));
        $this->assertEquals('sub/file.txt', $this->fileSystem->computeKey($this->baseTestDir . '/sub/file.txt'));
        $this->assertEquals('sub/dir/file.txt', $this->fileSystem->computeKey($this->baseTestDir . '/sub/dir/file.txt'));
    }

    public function testPathNormalizationAndBounds()
    {
        // Test internal _normalizePath method's boundary check via computePath
        $this->expectException(\OutOfBoundsException::class);
        // This key attempts to go outside the base directory
        $this->fileSystem->read('../../../outside_file.txt');
    }

    public function testEnsureDirectoryExistsWithoutCreateFlag()
    {
        $nonExistentDir = $this->baseTestDir . '/no_create_dir';
        $fs = new FileSystem($nonExistentDir, false); // create = false

        $this->expectException(AssertNotFoundException::class);
        // Trigger computePath which calls ensureDirectoryExists
        $fs->read('somefile.txt');
    }

    public function testConstructorWithNonExistentDirectoryAndNoCreate()
    {
        // This doesn't throw in constructor, but will on first operation if create is false
        $nonExistentDir = $this->baseTestDir . '/no_create_on_construct';
        $fs = new FileSystem($nonExistentDir, false);

        $this->expectException(AssertNotFoundException::class);
        $fs->write('test.txt', 'data'); // This should trigger the exception
    }
}

// Define the mock exception if it's not available elsewhere, for testing purposes
// In a real scenario, this should be part of the application's exception hierarchy.
namespace Chella\AwsService\Exception;

if (!class_exists(AssertNotFoundException::class)) {
    class AssertNotFoundException extends \RuntimeException {}
}
