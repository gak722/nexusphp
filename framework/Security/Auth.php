<?php
declare(strict_types=1);

namespace Nexus\Security;

use Nexus\Database\Model;
use Nexus\Http\Request;

/**
 * Authentication Manager supporting Stateful Session Guard and Stateless JWT Guard
 */
class Auth
{
    protected static ?Model $user = null;

    public static function setUser(Model $user): void
    {
        static::$user = $user;
    }

    public static function user(): ?Model
    {
        return static::$user;
    }

    public static function check(): bool
    {
        return static::$user !== null;
    }

    public static function id(): mixed
    {
        if (static::$user === null) {
            return null;
        }
        $key = static::$user->getPrimaryKey();
        return static::$user->{$key};
    }

    public static function guard(Request $request, string $userModelClass): ?Model
    {
        // 1. Check Bearer Token (Stateless JWT)
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            $secret = env('APP_KEY', 'default_secret_key_32_bytes_len_!!');
            $payload = Jwt::decode($token, $secret);
            if ($payload && isset($payload['sub'])) {
                /** @var class-string<Model> $userModelClass */
                $user = $userModelClass::find($payload['sub']);
                if ($user) {
                    static::$user = $user;
                    return $user;
                }
            }
        }

        // 2. Check Session (Stateful)
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }

        if (isset($_SESSION['auth_user_id'])) {
            /** @var class-string<Model> $userModelClass */
            $user = $userModelClass::find($_SESSION['auth_user_id']);
            if ($user) {
                static::$user = $user;
                return $user;
            }
        }

        return null;
    }

    public static function login(Model $user): void
    {
        static::$user = $user;
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }
        $key = $user->getPrimaryKey();
        $_SESSION['auth_user_id'] = $user->{$key};
    }

    public static function logout(): void
    {
        static::$user = null;
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['auth_user_id']);
        }
    }
}
