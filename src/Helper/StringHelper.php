<?php

namespace App\Helper;

class StringHelper
{
    public static function base64Encode(string $str) : string
    {
        if (!$str)    return '';
        return str_replace(array('+','/'), array('-','_',''), base64_encode($str));
    }

    public static function base64Decode(string $str) : string
    {
        if (!$str)    return '';
        $data = str_replace(array('-','_'), array('+','/'), $str);
        return base64_decode($data);
    }

    public static function getRandomStringShuffle($length = 16) : string
    {
        $stringSpace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $stringLength = strlen($stringSpace);
        $string = str_repeat($stringSpace, ceil($length / $stringLength));
        $shuffledString = str_shuffle($string);
        return substr($shuffledString, 1, $length);
    }
}