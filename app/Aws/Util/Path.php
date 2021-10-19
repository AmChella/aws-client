<?php
namespace Chella\AwsService\Util;

class Path {
    public static function normalize($path) {
        $path = str_replace('\\', '/', $path);
        $prefix = static::getAbsolutePrefix($path);
        $path = substr($path, strlen($prefix));
        $parts = array_filter(explode('/', $path), 'strlen');
        $tokens = array();

        foreach ($parts as $part) {
            switch ($part) {
                case '.':
                    continue;
                case '..':
                    if (0 !== count($tokens)) {
                        array_pop($tokens);
                        continue;
                    } elseif (!empty($prefix)) {
                        continue;
                    }
                default:
                    $tokens[] = $part;
            }
        }

        return $prefix.implode('/', $tokens);
    }

    public static function isAbsolute($path) {
        return '' !== static::getAbsolutePrefix($path);
    }

    public static function getAbsolutePrefix($path) {
        preg_match('|^(?P<prefix>([a-zA-Z]+:)?//?)|', $path, $matches);

        if (empty($matches['prefix'])) {
            return '';
        }

        return strtolower($matches['prefix']);
    }

    public static function dirname($path) {
        return str_replace('\\', '/', \dirname($path));
    }
}
