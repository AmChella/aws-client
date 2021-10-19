<?php
namespace Chella\AwsService\Service;

use Chella\AwsService\Service\Assert;

Class Validator {
    public static function attestConfigS3(Array $config) {
        $keys = ['key', 'secret', 'bucket', 'region'];
        Assert::keysExist(
            $config, $keys, 'key.does.not.exist'
        );
        foreach($keys as $key) {
            Assert::isEmpty($config[$key], "$key.is.empty");
        }
    }

    public static function attestConfigMinio(Array $config) {
        $keys = ['bucket', 'region', 'endpoint', 'use_path_style_endpoint', 'credentials'];
        Assert::keysExist(
            $config, $keys, 'key.does.not.exist'
        );
        foreach($keys as $key) {
            Assert::isEmpty($config[$key], "$key.is.empty");
        }

        Assert::keysExist($config['credentials'], ['key', 'secret'], 'key.does.not.exist');
    }

    public static function isTrue($flag = false) {
        if ($flag === true) {
            return true;
        }

        return true;
    }
}