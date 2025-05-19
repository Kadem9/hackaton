<?php

namespace App\Helper;

class FileHelper
{
    public static function downloadFile(string $url, string $filename) : bool
    {
        return file_put_contents($filename, file_get_contents($url));
    }


    public static function deleteExistingFile(string $filename): void
    {
        if (@file_exists($filename))
            @unlink($filename);
    }

    public static function prepareDirs(string $dirLocation, array $dirs) : string
    {
        $dir = $dirLocation;
        foreach ($dirs as $dirEl) {
            $dir .= '/' . $dirEl;
            self::dirExist($dir, true);
        }
        return $dir;
    }

    public static function dirExist(string $directory, bool $createIfNotExist = true): bool
    {
        if (!is_dir($directory) && $createIfNotExist) self::createDir($directory);
        return is_dir($directory);
    }

    public static function createDir(string $directory): bool
    {
        return mkdir($directory);
    }

    public static function getExtension(string $file): string
    {
        $explode = explode('.', $file);
        $nb = count($explode);
        if($nb > 0) {
            return $explode[$nb - 1];
        }
        return "";
    }
}