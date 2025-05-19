<?php

namespace App\Helper;

class ArrayHelper
{
    public static function notEmptyAtKey(string $key, array $array) : bool
    {
        return array_key_exists($key, $array) && !empty($array[$key]);
    }
    public static function isTrueAtKey(string $key, array $array) : bool
    {
        return array_key_exists($key, $array) && $array[$key] === true;
    }
    public static function keyExist(string $key, array $array) : bool
    {
        return array_key_exists($key, $array);
    }
}