<?php
declare(strict_types=1);

namespace Nexus\Support\Path;

class Path
{
    public static function join(string ...$paths): string
    {
        $clean = [];
        foreach ($paths as $p) {
            if ($p !== '') {
                $clean[] = trim($p, '/\\');
            }
        }
        $prefix = (isset($paths[0]) && str_starts_with($paths[0], '/')) ? '/' : '';
        return $prefix . implode('/', $clean);
    }

    public static function normalize(string $path): string
    {
        $path = str_replace(['\\', "\0"], ['/', ''], $path);
        $parts = array_filter(explode('/', $path), fn($p) => $p !== '' && $p !== '.');
        $stack = [];
        foreach ($parts as $part) {
            if ($part === '..') {
                array_pop($stack);
            } else {
                $stack[] = $part;
            }
        }
        $prefix = str_starts_with($path, '/') ? '/' : '';
        return $prefix . implode('/', $stack);
    }

    public static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:[\\\\\/]/', $path) === 1;
    }

    public static function isRelative(string $path): bool
    {
        return !static::isAbsolute($path);
    }

    public static function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    public static function filename(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    public static function basename(string $path): string
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    public static function directory(string $path): string
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }
}
