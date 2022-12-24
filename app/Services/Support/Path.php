<?php

namespace App\Services\Support;

use Illuminate\Support\Facades\File;

class Path
{
    public static function currentDirectory($append = null): string
    {
        return $append ? getcwd() . '/' . $append : getcwd();
    }

    public static function homeDir($append = null): string
    {
        $path = $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'];

        return $append ? "{$path}/{$append}" : $path;
    }

    public static function isFile(string $filePath): bool
    {
        return File::isFile($filePath);
    }

    public static function cleanDir(string $dir): bool
    {
        return File::cleanDirectory($dir);
    }

    public static function ensureDirExist(string $dir): void
    {
        File::ensureDirectoryExists($dir);
    }
}
