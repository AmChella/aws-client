<?php

namespace Chella\AwsService\Util;

class Checksum {
    public static function fromContent($content) {
        return md5($content);
    }

    public static function fromFile($filename) {
        return md5_file($filename);
    }
}
