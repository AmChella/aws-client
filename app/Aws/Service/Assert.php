<?php
namespace Chella\AwsService\Service;

use Chella\AwsService\Exception\AssertionException;

class Assert {
    public static function isNumber($number, $message) {
        if (preg_match('/[^0-9]/', $number)) {
            throw new AssertionException($message);
        }
    }

    public static function isArray($array, $message) {
        if (!is_array($array)) {
            throw new AssertionException($message);
        }
    }

    public static function isAssociateArray($array, $message) {
        self::isArray($array, $message);
        if (array_keys($array) === range(0, count($array) - 1)) {
            throw new AssertionException($message);
        }
    }

    public static function isString($str, $message) {
        if (!is_string($str)) {
            throw new AssertionException($message);
        }
    }

    public static function arrayNotEmpty($array, $message) {
        self::isArray($array, $message);
        if (count($array) === 0) {
            throw new AssertionException($message);
        }
    }

    public static function arrayIsEmpty($array, $message) {
        self::isArray($array, $message);
        if (count($array) > 0) {
            throw new AssertionException($message);
        }
    }

    public static function notNull($data, $message) {
        if (is_null($data)) {
            throw new AssertionException($message);
        }
    }

    public static function notEmpty($data, $message) {
        self::notNull($data, $message);
        self::isString($data, $message);
        if (strlen($data) === 0) {
            throw new AssertionException($message);
        }
    }

    public static function isEmpty($data, $message) {
        if (empty($data) === true) {
            throw new AssertionException($message);
        }
    }

    public static function maxLength($string,$number, $message) {
        if (strlen($string) > $number) {
            throw new AssertionException($message);
        }
    }

    public static function isZero($data, $message) {
        if ($data === 0) {
            throw new AssertionException($message);
        }
    }

    public static function isPositiveInteger($data, $message) {
        if (is_int($data) === false && $data < 1) {
            throw new AssertionException($message);
        }
    }

    public static function isNotZero($data, $message) {
        if ($data !== 0) {
            throw new AssertionException($message);
        }
    }

    public static function notEquals($value1, $value2, $message) {
        if ($value1 !== $value2) {
            throw new AssertionException($message);
        }
    }

    public static function keysExist(
        Array $array, Array $vKeys, String $message
    ) {
        self::isArray($array, $message);
        $keys = array_keys($array);
        foreach($vKeys as $item) {
            if (in_array($item, $keys) === false) {
                $msg = sprintf("'%s'-%s", $item, $message);
                throw new AssertionException($msg);
            }
        } 
    }

    public static function multipleArrayKeyExists($array, $keys, $message) {
        self::isArray($array, $message);
        foreach ($array as $value) {
            self::arrayKeyExists($value, $keys, $message);
        }

    }

    public static function multipleArrayValueExists($array, $message) {
        self::isArray($array, $message);
        foreach ($array as $value) {
            self::arrayValueExists($value, $message);
        }

    }

    public static function isValidXml($data, $message) {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadXML($data);
        $errors = libxml_get_errors();
        if(empty($errors) === false) {
            libxml_use_internal_errors(false);
            libxml_clear_errors();
            throw new AssertionException($message);
        }
    }

    public static function isAllowedFileType(
        $allowedFileTypes, $file, $message
    ) {
        if (in_array($file, $allowedFileTypes) === false) {
            throw new AssertionException($message);
        }
    }

    public static function isAllowedFileSize(
        $allowedFileSize, $fileSize, $message
    ) {
        if ($fileSize > $allowedFileSize) {
            throw new AssertionException($message);
        }
    }

    public static function arrayValueExists($array, $message) {
        self::isArray($array, $message);
        foreach ($array as $value) {
            if (empty($value) === true) {
                throw new AssertionException($message);
            }
        }
    }

    public static function isNull($data, $message) {
        if ($data !== null) {
            throw new AssertionException($message);
        }
    }

    public static function isNumeric($data, $message) {
        if (is_numeric($data) === false) {
            throw new AssertionException($message);
        }
    }
    public static function validateEmail($data, $message) {
        $emails = explode(',', $data);
        $emailCount = count($emails);
        for ($i=0; $i < $emailCount; $i++) {
            if (filter_var(trim($emails[$i]), FILTER_VALIDATE_EMAIL) === false) {
                throw new AssertionException($message);
            }
        }
    }

}
