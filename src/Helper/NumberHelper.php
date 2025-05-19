<?php

namespace App\Helper;

class NumberHelper
{
    public static function isDecimalEqualZero(int|float $number) : bool
    {
        return $number - floor($number) == 0;
    }

    public static function getSquareMeter(float $widthMeter, float $heightMeter) : float
    {
        return round($widthMeter * $heightMeter, 2);
    }
}