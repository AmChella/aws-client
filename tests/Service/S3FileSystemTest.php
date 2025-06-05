<?php

declare(strict_types=1);

namespace Chella\AwsService\Tests\Service;

use Aws\S3\S3Client;
use Aws\Result;
use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Chella\AwsService\Service\S3FileSystem;
use Chella\AwsService\Tests\TestCase;
use Chella\AwsService\Exception\AssertNotFoundException;
use Psr\Http\Message\StreamInterface;

class S3FileSystemTest extends TestCase
{
    private $s3ClientMock;
    private $bucketName = 'test-bucket';

    protected function setUp(): void
    {
        parent::setUp();
        $this->s3ClientMock = $this->createMock(S3Client::class);
    }

    private function createFileSystem(array $options = [], bool $detectContentType = false): S3FileSystem
    {
        return new S3FileSystem($this->s3ClientMock, $this->bucketName, $options, $detectContentType);
    }

    // Helper to mock S3Exception
    private function createS3Exception(string $message = '', string $awsErrorCode = null): S3Exception
    {
        $command = $this->createMock(CommandInterface::class);
        return new S3Exception($message, $command, ['code' => $awsErrorCode]);
    }

    public function testConstructor()
    {
        $fs = $this->createFileSystem();
        $this->assertInstanceOf(S3FileSystem::class, $fs);
    }

    public function testGetUrlDeprecated()
    {
        $fs = $this->createFileSystem();
        $key = 'test.txt';
        $expectedUrl = 'http://example.com/test.txt';

        $this->s3ClientMock->expects($this->once())
            ->method('getObjectUrl')
            ->with($this->bucketName, $key, null, [])
            ->willReturn($expectedUrl);

        @trigger_error('', E_USER_DEPRECATED); // Suppress own trigger for testing
        $url = $fs->getUrl($key);
        $this->assertEquals($expectedUrl, $url);
    }

    public function testSetAndGetMetadata()
    {
        $fs = $this->createFileSystem();
        $key = 'file.txt';
        $metadata = ['ContentType' => 'text/plain', 'Custom' => 'Value'];

        $fs->setMetadata($key, $metadata);
        $this->assertEquals($metadata, $fs->getMetadata($key));

        // Test BC contentType key
        $fs->setMetadata($key, ['contentType' => 'application/json']);
        $this->assertEquals(['ContentType' => 'application/json'], $fs->getMetadata($key));
    }

    public function testReadSuccess()
    {
        $fs = $this->createFileSystem(['create' => true]); // ensureBucketExists might be called
        $key = 'file.txt';
        $content = 'Hello S3!';
        $contentType = 'text/plain';

        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->method('__toString')->willReturn($content);

        $resultMock = new Result(['Body' => $streamMock, 'ContentType' => $contentType]);

        $this->s3ClientMock->method('doesBucketExist')->willReturn(true); // For ensureBucketExists
        $this->s3ClientMock->expects($this->once())
            ->method('getObject')
            ->with([
                'ACL' => 'private', // default
                'Bucket' => $this->bucketName,
                'Key' => $key
            ])
            ->willReturn($resultMock);

        $readContent = $fs->read($key);
        $this->assertEquals($content, $readContent);
        $this->assertEquals(['ContentType' => $contentType], $fs->getMetadata($key));
    }

    public function testReadFailure()
    {
        $fs = $this->createFileSystem(['create' => true]);
        $key = 'nonexistent.txt';

        $this->s3ClientMock->method('doesBucketExist')->willReturn(true);
        $this->s3ClientMock->expects($this->once())
            ->method('getObject')
            ->willThrowException($this->createS3Exception('Error reading object'));

        $this->assertFalse($fs->read($key));
    }

    public function testEnsureBucketExistsWhenItAlreadyDoes()
    {
        $fs = $this->createFileSystem(['create' => false]); // create = false explicitly
        $this->s3ClientMock->expects($this->once())
            ->method('doesBucketExist')
            ->with($this->bucketName)
            ->willReturn(true);
        $this->s3ClientMock->expects($this->never())->method('createBucket');

        $fs->read('any.txt'); // Trigger ensureBucketExists
    }

    public function testEnsureBucketExistsAndCreateIt()
    {
        $fs = $this->createFileSystem(['create' => true]);
        $this->s3ClientMock->expects($this->once())
            ->method('doesBucketExist')
            ->with($this->bucketName)
            ->willReturn(false); // First it doesn't exist

        $this->s3ClientMock->expects($this->once())
            ->method('createBucket')
            ->with(['Bucket' => $this->bucketName, 'LocationConstraint' => null]); // Assuming region is null if not specified

        $this->s3ClientMock->method('getObject')->willReturn(new Result(['Body' => ''])); // for the read
        $fs->read('any.txt'); // Trigger ensureBucketExists then getObject
    }

    public function testEnsureBucketExistsAndNotCreateItThrowsException()
    {
        $fs = $this->createFileSystem(['create' => false]);
        $this->s3ClientMock->expects($this->once())
            ->method('doesBucketExist')
            ->with($this->bucketName)
            ->willReturn(false);

        $this->s3ClientMock->expects($this->never())->method('createBucket');

        $this->expectException(AssertNotFoundException::class);
        $fs->read('any.txt'); // Trigger ensureBucketExists
    }


    public function testWriteSuccess()
    {
        $fs = $this->createFileSystem(['create' => true, 'acl' => 'public-read']);
        $key = 'new_file.txt';
        $content = 'S3 Write Test';

        $this->s3ClientMock->method('doesBucketExist')->willReturn(true);
        $this->s3ClientMock->expects($this->once())
            ->method('putObject')
            ->with([
                'ACL' => 'public-read',
                'Bucket' => $this->bucketName,
                'Key' => $key,
                'Body' => $content
            ])
            ->willReturn(new Result());

        $result = $fs->write($key, $content);
        $this->assertEquals(strlen($content), $result);
    }

    public function testWriteWithContentTypeDetection()
    {
        $fs = $this->createFileSystem(['acl' => 'private'], true); // detectContentType = true
        $key = 'image.jpg';
        $content = 'dummyimagecontent'; // Real image content would be binary

        $this->s3ClientMock->method('doesBucketExist')->willReturn(true);
        $this->s3ClientMock->expects($this->once())
            ->method('putObject')
            ->with($this->callback(function ($options) use ($key, $content) {
                return $options['Bucket'] === $this->bucketName &&
                       $options['Key'] === $key &&
                       $options['Body'] === $content &&
                       isset($options['ContentType']) && // ContentType should be set
                       $options['ContentType'] !== 'binary/octet-stream'; // And not the default
            }))
            ->willReturn(new Result());

        $fs->write($key, $content);
    }

    public function testRenameSuccess()
    {
        $fs = $this->createFileSystem(['create' => true]);
        $sourceKey = 'old.txt';
        $targetKey = 'new.txt';

        $this->s3ClientMock->method('doesBucketExist')->willReturn(true);
        $this->s3ClientMock->expects($this->once())
            ->method('copyObject')
            ->with([
                'ACL' => 'private',
                'Bucket' => $this->bucketName,
                'Key' => $targetKey,
                'CopySource' => $this->bucketName . '/' . $sourceKey
            ])
            ->willReturn(new Result());

        $this->s3ClientMock->expects($this->once())
            ->method('deleteObject')
            ->with([
                'ACL' => 'private',
                'Bucket' => $this->bucketName,
                'Key' => $sourceKey
            ])
            ->willReturn(new Result());

        $this->assertTrue($fs->rename($sourceKey, $targetKey));
    }

    public function testRenameFailureOnCopy()
    {
        $fs = $this->createFileSystem(['create' => true]);
        $sourceKey = 'old.txt';
        $targetKey = 'new.txt';

        $this->s3ClientMock->method('doesBucketExist')->willReturn(true);
        $this->s3ClientMock->expects($this->once())
            ->method('copyObject')
            ->willThrowException($this->createS3Exception('Copy failed'));

        $this->s3ClientMock->expects($this->never())->method('deleteObject');

        $this->assertFalse($fs->rename($sourceKey, $targetKey));
    }

    public function testExistsTrue()
    {
        $fs = $this->createFileSystem();
        $key = 'existent.txt';
        $this->s3ClientMock->expects($this->once())
            ->method('doesObjectExist')
            ->with($this->bucketName, $key)
            ->willReturn(true);
        $this->assertTrue($fs->exists($key));
    }

    public function testExistsFalse()
    {
        $fs = $this->createFileSystem();
        $key = 'nonexistent.txt';
        $this->s3ClientMock->expects($this->once())
            ->method('doesObjectExist')
            ->with($this->bucketName, $key)
            ->willReturn(false);
        $this->assertFalse($fs->exists($key));
    }

    public function testMtimeSuccess()
    {
        $fs = $this->createFileSystem();
        $key = 'file.txt';
        $lastModifiedDate = '2023-01-01T12:00:00Z';
        $expectedTimestamp = strtotime($lastModifiedDate);

        $this->s3ClientMock->expects($this->once())
            ->method('headObject')
            ->with($this->callback(function($options) use ($key) {
                return $options['Key'] === $key && $options['Bucket'] === $this->bucketName;
            }))
            ->willReturn(new Result(['LastModified' => new \DateTime($lastModifiedDate)]));

        $this->assertEquals($expectedTimestamp, $fs->mtime($key));
    }

    public function testMtimeFailure()
    {
        $fs = $this->createFileSystem();
        $key = 'file.txt';
        $this->s3ClientMock->method('headObject')->willThrowException($this->createS3Exception('Head failed'));
        $this->assertFalse($fs->mtime($key));
    }

    public function testSizeSuccess()
    {
        $fs = $this->createFileSystem();
        $key = 'file.txt';
        $contentLength = 1024;

        $this->s3ClientMock->expects($this->once())
            ->method('headObject')
             ->with($this->callback(function($options) use ($key) {
                return $options['Key'] === $key && $options['Bucket'] === $this->bucketName;
            }))
            ->willReturn(new Result(['ContentLength' => $contentLength]));

        $this->assertEquals($contentLength, $fs->size($key));
    }

    public function testDeleteSuccess()
    {
        $fs = $this->createFileSystem();
        $key = 'to_delete.txt';
        $this->s3ClientMock->expects($this->once())
            ->method('deleteObject')
            ->with($this->callback(function($options) use ($key) {
                return $options['Key'] === $key && $options['Bucket'] === $this->bucketName;
            }))
            ->willReturn(new Result());
        $this->assertTrue($fs->delete($key));
    }

    public function testDeleteFailure()
    {
        $fs = $this->createFileSystem();
        $key = 'to_delete.txt';
        $this->s3ClientMock->method('deleteObject')->willThrowException($this->createS3Exception('Delete failed'));
        $this->assertFalse($fs->delete($key));
    }

    public function testKeysWithNoPrefix()
    {
        $fs = $this->createFileSystem(['create' => true]);
        $s3Objects = [
            ['Key' => 'file1.txt'],
            ['Key' => 'dir/file2.txt'],
        ];
        $expectedKeys = ['file1.txt', 'dir/file2.txt'];

        $this->s3ClientMock->method('doesBucketExist')->willReturn(true);
        $this->s3ClientMock->expects($this->once())
            ->method('getIterator')
            ->with('ListObjects', ['Bucket' => $this->bucketName])
            ->willReturn(new \ArrayIterator($s3Objects));

        $this->assertEquals($expectedKeys, $fs->keys());
    }

    public function testKeysWithDirectoryOption()
    {
        $directory = 'basedir';
        $fs = $this->createFileSystem(['create' => true, 'directory' => $directory]);
        $s3Objects = [
            ['Key' => $directory . '/file1.txt'],
            ['Key' => $directory . '/sub/file2.txt'],
        ];
        // computeKey should strip the base directory
        $expectedKeys = ['file1.txt', 'sub/file2.txt'];

        $this->s3ClientMock->method('doesBucketExist')->willReturn(true);
        $this->s3ClientMock->expects($this->once())
            ->method('getIterator')
            ->with('ListObjects', ['Bucket' => $this->bucketName, 'Prefix' => $directory])
            ->willReturn(new \ArrayIterator($s3Objects));

        $this->assertEquals($expectedKeys, $fs->keys());
    }

    public function testListKeysWithPrefix()
    {
        $prefix = 'some/path';
        $fs = $this->createFileSystem(['create' => true]);
         $s3Objects = [
            ['Key' => $prefix . '/fileA.txt'],
        ];
        $expectedKeys = ['fileA.txt']; // computeKey strips prefix if directory option is also set to same prefix
                                       // Here directory is empty, so prefix is part of key

        $this->s3ClientMock->method('doesBucketExist')->willReturn(true);
        $this->s3ClientMock->expects($this->once())
            ->method('getIterator')
            ->with('ListObjects', ['Bucket' => $this->bucketName, 'Prefix' => $prefix])
            ->willReturn(new \ArrayIterator($s3Objects));

        $this->assertEquals([$prefix . '/fileA.txt'], $fs->listKeys($prefix));
    }


    public function testIsDirectoryTrue()
    {
        $fs = $this->createFileSystem();
        $key = 'my_dir/'; // S3 directories often end with /

        $this->s3ClientMock->expects($this->once())
            ->method('listObjects')
            ->with([
                'Bucket' => $this->bucketName,
                'Prefix' => $key, // already ends with /
                'MaxKeys' => 1,
            ])
            ->willReturn(new Result(['Contents' => [['Key' => $key . 'file.txt']]]));

        $this->assertTrue($fs->isDirectory($key));
    }

    public function testIsDirectoryFalse()
    {
        $fs = $this->createFileSystem();
        $key = 'not_a_dir';

        $this->s3ClientMock->expects($this->once())
            ->method('listObjects')
            ->with([
                'Bucket' => $this->bucketName,
                'Prefix' => $key . '/', // appends /
                'MaxKeys' => 1,
            ])
            ->willReturn(new Result(['Contents' => []])); // No contents

        $this->assertFalse($fs->isDirectory($key));
    }

    public function testMimeTypeSuccess()
    {
        $fs = $this->createFileSystem();
        $key = 'file.jpg';
        $contentType = 'image/jpeg';

        $this->s3ClientMock->expects($this->once())
            ->method('headObject')
            ->willReturn(new Result(['ContentType' => $contentType]));

        $this->assertEquals($contentType, $fs->mimeType($key));
    }

    public function testCopyToS3()
    {
        $fs = $this->createFileSystem();
        $target = 'target/path/newfile.txt';
        $copySource = 'source/path/oldfile.txt';

        $this->s3ClientMock->expects($this->once())
            ->method('copyObject')
            ->with([
                'Bucket' => $this->bucketName,
                'Key' => $target,
                'CopySource' => $this->bucketName . '/' . $copySource,
            ]);

        $fs->copyToS3($target, $copySource);
        // No return value to assert, just verifying mock expectation
        $this->assertTrue(true);
    }

    public function testComputePathWithDirectory()
    {
        $dir = 'my/app/files';
        $fs = $this->createFileSystem(['directory' => $dir]);
        $key = 'test.txt';
        // Reflection or a dedicated test method would be better for protected methods
        // For now, let's test its effect via a method that uses it, e.g. exists()
        $this->s3ClientMock->expects($this->once())
            ->method('doesObjectExist')
            ->with($this->bucketName, $dir . '/' . $key)
            ->willReturn(true);
        $fs->exists($key);
    }
}

// Define the mock exception if it's not available elsewhere, for testing purposes
namespace Chella\AwsService\Exception;

if (!class_exists(AssertNotFoundException::class)) {
    class AssertNotFoundException extends \RuntimeException {}
}
