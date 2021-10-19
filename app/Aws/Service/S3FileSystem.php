<?php
namespace Chella\AwsService\Service;

use Aws\S3\S3Client;
use Chella\AwsService\Util;
use Chella\AwsService\Exception\AssertNotFoundException;

class S3FileSystem {
    /** @var S3Client */
    protected $service;
    /** @var stringkeyExist */
    protected $bucket;
    /** @var array */
    protected $options;
    /** @var bool */
    protected $bucketExists;
    /** @var array */
    protected $metadata = [];
    /** @var bool */
    protected $detectContentType;

    public function __construct(
        S3Client $service, $bucket, Array $options = [], 
        $detectContentType = false
    ) {
        $this->service = $service;
        $this->bucket = $bucket;
        $this->options = array_replace(
            [
                'create' => false,
                'directory' => '',
                'acl' => 'private',
            ],
            $options
        );

        $this->detectContentType = $detectContentType;
    }

    public function getUrl($key, array $options = []) {
        @trigger_error(
            E_USER_DEPRECATED,
            'Using AwsS3::getUrl() method was deprecated since v0.4. Please chek gaufrette/extras package if you want this feature'
        );

        return $this->service->getObjectUrl(
            $this->bucket,
            $this->computePath($key),
            isset($options['expires']) ? $options['expires'] : null,
            $options
        );
    }

    public function setMetadata($key, $metadata) {
        // BC with AmazonS3 adapter
        if (isset($metadata['contentType'])) {
            $metadata['ContentType'] = $metadata['contentType'];
            unset($metadata['contentType']);
        }

        $this->metadata[$key] = $metadata;
    }

    public function getMetadata($key) {
        return isset($this->metadata[$key]) ? $this->metadata[$key] : [];
    }

    public function read($key) {
        $this->ensureBucketExists();
        $options = $this->getOptions($key);

        try {
            // Get remote object
            $object = $this->service->getObject($options);
            // If there's no metadata array set up for this object, set it up
            if (!array_key_exists($key, $this->metadata) || !is_array($this->metadata[$key])) {
                $this->metadata[$key] = [];
            }
            // Make remote ContentType metadata available locally
            $this->metadata[$key]['ContentType'] = $object->get('ContentType');

            return (string) $object->get('Body');
        } catch (\Exception $e) {
            return false;
        }
    }

    public function rename($sourceKey, $targetKey) {
        $this->ensureBucketExists();
        $options = $this->getOptions(
            $targetKey,
            ['CopySource' => sprintf("%s/%s", $this->bucket, $this->computePath($sourceKey))]
        );

        try {
            $this->service->copyObject(array_merge($options, $this->getMetadata($targetKey)));

            return $this->delete($sourceKey);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function write($key, $content) {
        $this->ensureBucketExists();
        $options = $this->getOptions($key, ['Body' => $content]);

        /*
         * If the ContentType was not already set in the metadata, then we autodetect
         * it to prevent everything being served up as binary/octet-stream.
         */
        if (!isset($options['ContentType']) && $this->detectContentType) {
            $options['ContentType'] = $this->guessContentType($content);
        }

        try {
            $this->service->putObject($options);

            if (is_resource($content)) {
                return Util\Size::fromResource($content);
            }

            return Util\Size::fromContent($content);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function exists($key) {
        return $this->service->doesObjectExist($this->bucket, $this->computePath($key));
    }

    public function mtime($key) {
        try {
            $result = $this->service->headObject($this->getOptions($key));

            return strtotime($result['LastModified']);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function size($key) {
        try {
            $result = $this->service->headObject($this->getOptions($key));

            return $result['ContentLength'];
        } catch (\Exception $e) {
            return false;
        }
    }

    public function keys() {
        return $this->listKeys();
    }

    public function listKeys($prefix = '') {
        $this->ensureBucketExists();

        $options = ['Bucket' => $this->bucket];
        if ((string) $prefix != '') {
            $options['Prefix'] = $this->computePath($prefix);
        } elseif (!empty($this->options['directory'])) {
            $options['Prefix'] = $this->options['directory'];
        }

        $keys = [];
        $iter = $this->service->getIterator('ListObjects', $options);
        foreach ($iter as $file) {
            $keys[] = $this->computeKey($file['Key']);
        }

        return $keys;
    }

    public function delete($key) {
        try {
            $this->service->deleteObject($this->getOptions($key));

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function isDirectory($key) {
        $result = $this->service->listObjects([
            'Bucket' => $this->bucket,
            'Prefix' => rtrim($this->computePath($key), '/').'/',
            'MaxKeys' => 1,
        ]);

        if (isset($result['Contents'])) {
            if (is_array($result['Contents']) || $result['Contents'] instanceof \Countable) {
                return count($result['Contents']) > 0;
            }
        }

        return false;
    }

    protected function ensureBucketExists() {
        if ($this->bucketExists) {
            return true;
        }

        if ($this->bucketExists = $this->service->doesBucketExist($this->bucket)) {
            return true;
        }

        if (!$this->options['create']) {
            throw new AssertNotFoundException(sprintf(
                'The configured bucket "%s" does not exist.',
                $this->bucket
            ));
        }

        $this->service->createBucket([
            'Bucket' => $this->bucket,
            'LocationConstraint' => $this->service->getRegion()
        ]);
        $this->bucketExists = true;

        return true;
    }

    protected function getOptions($key, array $options = []) {
        $options['ACL'] = $this->options['acl'];
        $options['Bucket'] = $this->bucket;
        $options['Key'] = $this->computePath($key);

        /*
         * Merge global options for adapter, which are set in the constructor, with metadata.
         * Metadata will override global options.
         */
        $options = array_merge($this->options, $options, $this->getMetadata($key));

        return $options;
    }

    protected function computePath($key) {
        if (empty($this->options['directory'])) {
            return $key;
        }

        return sprintf('%s/%s', $this->options['directory'], $key);
    }

    protected function computeKey($path) {
        return ltrim(substr($path, strlen($this->options['directory'])), '/');
    }

    private function guessContentType($content) {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);

        if (is_resource($content)) {
            return $fileInfo->file(stream_get_meta_data($content)['uri']);
        }

        return $fileInfo->buffer($content);
    }

    public function mimeType($key) {
        try {
            $result = $this->service->headObject($this->getOptions($key));
            return ($result['ContentType']);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function copyToS3(String $target, String $copySource): Void {
        $this->service->copyObject([
            'Bucket'     => $this->bucket,
            'Key'        => $target,
            'CopySource' => sprintf('%s/%s', $this->bucket, $copySource)
        ]);
    }
}
