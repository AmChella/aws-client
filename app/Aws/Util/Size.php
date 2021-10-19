<?php

namespace Chella\AwsService\Util;

class Size {

    public static function fromContent($content) {
        // Make sure to get the real length in byte and not
        // accidentally mistake some bytes as a UTF BOM.
        return mb_strlen($content, '8bit');
    }

    public static function fromFile($filename) {
        return filesize($filename);
    }

    public static function fromResource($handle) {
        $cStat = fstat($handle);
        // if the resource is a remote file, $cStat will be false
        return $cStat ? $cStat['size'] : 0;
    }
}
