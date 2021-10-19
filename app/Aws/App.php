<?php
namespace Chella\AwsService;

use \Exception;
use Chella\AwsService\Service\{S3FileSystem, FileSystem, Validator};
use Aws\S3\S3Client;
use Aws\SES\SESClient;
use Chella\AwsService\AppInitializerException;

Class App {
    public static $instance;
    public function __construct() {}

    public static function getApp() {
        if (!self::$instance) {
            self::$instance = new App();
        }

        self::$instance->app = self::bootApp();

        return self::$instance->app;
    }

    public static function get($key, $config = []) {
        $app = self::getApp();
        if (isset($app[$key]) === false) {
            throw new AppInitializerException("$key.object.does.not.exist");
        }

        return \call_user_func_array($app[$key], [$config]);
    }

    private function bootApp() {

        $app = [];

        $app['fileSystemS3'] = function($config) {
            Validator::attestConfigS3($config);
            $config['version'] = "latest";
            $aws = S3Client::factory($config);

            return new S3FileSystem($aws, $config['bucket']);
        };

        $app['fileSystem'] = function() {
            return new FileSystem('/');
        };

        return $app;
    }
}