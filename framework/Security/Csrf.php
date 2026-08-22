<?php
declare(strict_types=1);

namespace Nexus\Security;

use Nexus\Http\Request;

/**
 * Synchronizer Token CSRF Protection Handler
 */
class Csrf
{
    protected const TOKEN_KEY = '_csrf_token';

    public static function generateToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }

        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[self::TOKEN_KEY];
    }

    public static function validate(Request $request): bool
    {
        if (in_array(strtoupper($request->method), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }

        $sessionToken = $_SESSION[self::TOKEN_KEY] ?? null;
        if (!$sessionToken) {
            return false;
        }

        $inputToken = $request->post['_token'] 
            ?? ($request->isJson() ? $request->json('_token') : null)
            ?? $request->header('X-CSRF-TOKEN') 
            ?? null;
        if (!$inputToken) {
            return false;
        }

        return hash_equals((string) $sessionToken, (string) $inputToken);
    }
}
