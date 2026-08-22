<?php
declare(strict_types=1);

namespace Nexus\Routing;

/**
 * Route Regex Compiler
 */
class RouteCompiler
{
    public static function compile(string $uri): array
    {
        $paramNames = [];
        $pattern = preg_replace_callback('/\{([a-zA-Z0-9_]+)(?::([^}]+))?\}/', function ($matches) use (&$paramNames) {
            $paramNames[] = $matches[1];
            $regex = $matches[2] ?? '[^/]+';
            return "({$regex})";
        }, $uri);

        $pattern = '#^' . $pattern . '$#s';

        return [
            'pattern' => $pattern,
            'paramNames' => $paramNames,
        ];
    }
}
