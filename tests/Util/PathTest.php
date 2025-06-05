<?php

declare(strict_types=1);

namespace Chella\AwsService\Tests\Util;

use Chella\AwsService\Util\Path;
use Chella\AwsService\Tests\TestCase;

class PathTest extends TestCase
{
    /**
     * @dataProvider normalizePathsProvider
     */
    public function testNormalize(string $inputPath, string $expectedPath)
    {
        $this->assertEquals($expectedPath, Path::normalize($inputPath));
    }

    public function normalizePathsProvider(): array
    {
        return [
            // Basic cases
            ['path/to/file.txt', 'path/to/file.txt'],
            ['path\to\file.txt', 'path/to/file.txt'],
            ['/abs/path/to/file.txt', '/abs/path/to/file.txt'],
            ['C:\abs\path\to\file.txt', 'c://abs/path/to/file.txt'], // Assuming getAbsolutePrefix handles drive letters

            // Current directory references
            ['./path/to/file.txt', 'path/to/file.txt'],
            ['path/./to/file.txt', 'path/to/file.txt'],
            ['/path/./to/file.txt', '/path/to/file.txt'],

            // Parent directory references
            ['path/../file.txt', 'file.txt'],
            ['path/to/../../file.txt', 'file.txt'],
            ['/path/to/../../file.txt', '/file.txt'],
            ['C:/path/to/../../file.txt', 'c://file.txt'],

            // Mixed references
            ['./path/to/.././../file.txt', 'file.txt'],
            ['/./path/to/.././../file.txt', '/file.txt'],

            // Edge cases
            ['', ''],
            ['/', '/'],
            ['//', '//'],
            ['C:', 'c:'], // The regex in getAbsolutePrefix might make this c:// or c:
            ['C:/', 'c://'],
            ['C://', 'c://'],
            ['../path/file.txt', 'path/file.txt'], // Leading .. are ignored for relative paths
            ['/../path/file.txt', '/path/file.txt'], // Leading .. after root are ignored
            ['../../path/file.txt', 'path/file.txt'],
            ['foo/bar/../../../baz', 'baz'],
            ['foo/bar/../../../../baz', 'baz'], // Going above root of relative path

            // Protocol/Scheme paths
            ['http://example.com/path/to/resource', 'http://example.com/path/to/resource'],
            ['ftp://user@host/path/../file', 'ftp://user@host/file'],
            ['s3://bucket/path/./object', 's3://bucket/path/object'],
        ];
    }

    /**
     * @dataProvider normalizeWindowsPathsProvider
     */
    public function testNormalizeWindowsSpecific(string $inputPath, string $expectedPath)
    {
        // These tests focus on Windows-like paths that getAbsolutePrefix might handle.
        // The actual behavior depends on the regex in getAbsolutePrefix.
        // preg_match('|^(?P<prefix>([a-zA-Z]+:)?//?)|', $path, $matches);
        // 'C:' -> prefix: 'c:' -> normalize returns 'c:'
        // 'C:/' -> prefix: 'c:/' -> normalize returns 'c://' (due to str_replace then explode)
        // 'C://' -> prefix: 'c://' -> normalize returns 'c://'
        $this->assertEquals($expectedPath, Path::normalize($inputPath));
    }

    public function normalizeWindowsPathsProvider(): array
    {
        return [
            ['C:', 'c:'],
            ['C:\'', 'c://'], // str_replace makes it C:/, prefix is c:/, path becomes '', result c:/ + ''
            ['C:\Path', 'c://Path'],
            ['C:\Path\..\File', 'c://File'],
            ['D:\Path\.\Sub', 'd://Path/Sub'],
        ];
    }


    /**
     * @dataProvider isAbsolutePathsProvider
     */
    public function testIsAbsolute(string $inputPath, bool $expected)
    {
        $this->assertEquals($expected, Path::isAbsolute($inputPath));
    }

    public function isAbsolutePathsProvider(): array
    {
        return [
            ['path/to/file.txt', false],
            ['/abs/path/to/file.txt', true], // Unix-like absolute
            ['C:\Windows', true],          // Windows absolute
            ['C:/Windows', true],           // Windows absolute with forward slashes
            ['//server/share', true],       // UNC path
            ['http://example.com', true],   // URL
            ['s3://bucket/key', true],      // S3 URI
            ['', false],
            ['../relative', false],
        ];
    }

    /**
     * @dataProvider getAbsolutePrefixPathsProvider
     */
    public function testGetAbsolutePrefix(string $inputPath, string $expectedPrefix)
    {
        $this->assertEquals($expectedPrefix, Path::getAbsolutePrefix($inputPath));
    }

    public function getAbsolutePrefixPathsProvider(): array
    {
        return [
            ['path/to/file.txt', ''],
            ['/abs/path/to/file.txt', '/'], // Regex `([a-zA-Z]+:)?//?` matches // or /
            ['C:\Windows', 'c:'],          // Matches 'C:'
            ['C:/Windows', 'c:/'],          // Matches 'C:/'
            ['//server/share', '//'],       // Matches '//'
            ['http://example.com', 'http://'],
            ['ftp://user@host', 'ftp://'],
            ['s3://bucket/key', 's3://'],
            ['file:///path/to/file', 'file:///'],
            ['z:', 'z:'],
            ['', ''],
        ];
    }

    /**
     * @dataProvider dirnameProvider
     */
    public function testDirname(string $inputPath, string $expectedDirname)
    {
        $this->assertEquals($expectedDirname, Path::dirname($inputPath));
    }

    public function dirnameProvider(): array
    {
        return [
            ['path/to/file.txt', 'path/to'],
            ['path\to\file.txt', 'path/to'], // PHP's dirname handles backslashes, then str_replace
            ['/abs/path/to/file.txt', '/abs/path/to'],
            ['C:\Windows\System32', 'C:/Windows'], // dirname behavior + str_replace
            ['C:/Windows/System32', 'C:/Windows'],
            ['http://example.com/path/file', 'http://example.com/path'],
            ['s3://bucket/object/key', 's3://bucket/object'],
            ['file.txt', '.'], // PHP's dirname behavior
            ['/', '/'], // PHP's dirname behavior for root
            ['//server/share', '//server'], // PHP's dirname behavior for UNC path (might vary by OS)
                                        // On Linux, dirname("//server/share") might be "//server" or "/"
                                        // Let's assume a common behavior or test on a specific OS if critical
        ];
    }
}
