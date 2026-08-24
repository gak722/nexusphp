<?php
declare(strict_types=1);

namespace Nexus\Support;

use Nexus\Filesystem\Filesystem;
use Nexus\Foundation\Application;

/**
 * Static accessor for local storage interactions, scoped to the application's storage path.
 */
class Storage
{
    protected static ?Filesystem $disk = null;

    protected static function disk(): Filesystem
    {
        if (static::$disk === null) {
            static::$disk = new Filesystem();
        }
        return static::$disk;
    }

    protected static function path(string $path): string
    {
        return Application::getInstance()->storagePath('app/' . ltrim($path, '\/'));
    }

    public static function exists(string $path): bool
    {
        return static::disk()->exists(static::path($path));
    }

    public static function get(string $path): string
    {
        return static::disk()->get(static::path($path));
    }

    public static function put(string $path, string $contents, bool $lock = false): int|false
    {
        $fullPath = static::path($path);
        $directory = dirname($fullPath);
        if (!static::disk()->isDirectory($directory)) {
            static::disk()->makeDirectory($directory, 0755, true);
        }
        return static::disk()->put($fullPath, $contents, $lock);
    }

    /**
     * Store an uploaded file array (from $request->file('key')) into a specified directory.
     * Returns relative path on success, or false on failure.
     */
    public static function putFile(string $directory, array $file): string|false
    {
        if (!isset($file['tmp_name']) || (!is_uploaded_file($file['tmp_name']) && !file_exists($file['tmp_name']))) {
            return false;
        }

        $extension = pathinfo($file['name'] ?? '', PATHINFO_EXTENSION);
        $hashName = bin2hex(random_bytes(16)) . ($extension ? '.' . $extension : '');
        $targetPath = rtrim($directory, '/\\') . '/' . $hashName;

        $fullPath = static::path($targetPath);
        $dirPath = dirname($fullPath);
        if (!static::disk()->isDirectory($dirPath)) {
            static::disk()->makeDirectory($dirPath, 0755, true);
        }

        if (is_uploaded_file($file['tmp_name'])) {
            if (move_uploaded_file($file['tmp_name'], $fullPath)) {
                return $targetPath;
            }
        } else {
            if (copy($file['tmp_name'], $fullPath)) {
                return $targetPath;
            }
        }

        return false;
    }

    public static function append(string $path, string $data): int|false
    {
        $fullPath = static::path($path);
        $directory = dirname($fullPath);
        if (!static::disk()->isDirectory($directory)) {
            static::disk()->makeDirectory($directory, 0755, true);
        }
        return static::disk()->append($fullPath, $data);
    }

    public static function delete(string|array|false|null $paths): bool
    {
        if (!$paths) {
            return false;
        }
        $paths = is_array($paths) ? $paths : [$paths];
        $fullPaths = array_map(fn($p) => static::path((string) $p), $paths);
        return static::disk()->delete($fullPaths);
    }

    public static function move(string $path, string $target): bool
    {
        return static::disk()->move(static::path($path), static::path($target));
    }

    public static function copy(string $path, string $target): bool
    {
        return static::disk()->copy(static::path($path), static::path($target));
    }

    public static function makeDirectory(string $path): bool
    {
        return static::disk()->makeDirectory(static::path($path), 0755, true);
    }

    public static function deleteDirectory(string $directory): bool
    {
        return static::disk()->deleteDirectory(static::path($directory));
    }
}
