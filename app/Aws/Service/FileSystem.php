<?php
namespace Chella\AwsService\Service;

use Chella\AwsService\Util\{Path, Checksum, Size};

class FileSystem {
    protected $directory;
    private $create;
    private $mode;

    public function __construct($directory, $create = false, $mode = 0777) {
        $this->directory = Path::normalize($directory);

        if (is_link($this->directory)) {
            $this->directory = realpath($this->directory);
        }

        $this->create = $create;
        $this->mode = $mode;
    }

    public function read($key) {
        return file_get_contents($this->computePath($key));
    }

    public function write($key, $content) {
        $path = $this->computePath($key);
        $this->ensureDirectoryExists(Path::dirname($path), true);

        return file_put_contents($path, $content);
    }

    public function rename($sourceKey, $targetKey) {
        $targetPath = $this->computePath($targetKey);
        $this->ensureDirectoryExists(Path::dirname($targetPath), true);

        return rename($this->computePath($sourceKey), $targetPath);
    }

    public function exists($key) {
        return is_file($this->computePath($key));
    }

    public function keys() {
        $this->ensureDirectoryExists($this->directory, $this->create);
        try {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $this->directory,
                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
                ),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
        } catch (\Exception $e) {
            $files = new \EmptyIterator();
        }

        $keys = [];
        foreach ($files as $file) {
            $keys[] = $this->computeKey($file);
        }
        sort($keys);

        return $keys;
    }

    public function mtime($key) {
        return filemtime($this->computePath($key));
    }

    public function delete($key) {
        if ($this->isDirectory($key)) {
            return rmdir($this->computePath($key));
        }

        return unlink($this->computePath($key));
    }

    public function isDirectory($key) {
        return is_dir($this->computePath($key));
    }

    public function checksum($key) {
        return Checksum::fromFile($this->computePath($key));
    }

    public function size($key) {
        return Size::fromFile($this->computePath($key));
    }

    public function mimeType($key) {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);

        return $fileInfo->file($this->computePath($key));
    }

    public function computeKey($path) {
        $path = $this->normalizePath($path);

        return ltrim(substr($path, strlen($this->directory)), '/');
    }

    protected function computePath($key) {
        $this->ensureDirectoryExists($this->directory, $this->create);

        return $this->normalizePath($this->directory.'/'.$key);
    }

    protected function normalizePath($path) {
        $path = Path::normalize($path);

        if (0 !== strpos($path, $this->directory)) {
            throw new \OutOfBoundsException(sprintf('The path "%s" is out of the filesystem.', $path));
        }

        return $path;
    }

    protected function ensureDirectoryExists($directory, $create = false) {
        if (!is_dir($directory)) {
            if (!$create) {
                throw new AssertNotFoundException(sprintf('The directory "%s" does not exist.', $directory));
            }

            $this->createDirectory($directory);
        }
    }

    protected function createDirectory($directory) {
        if (!@mkdir($directory, $this->mode, true) && !is_dir($directory)) {
            throw new AssertNotFoundException(sprintf('The directory \'%s\' could not be created.', $directory));
        }
    }
}
