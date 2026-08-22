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
            $secret = static::config()->get('app.key', env('APP_KEY', 'default_secret_key_32_bytes_len_!!'));
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
        static::startSession();

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

    public static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function attempt(string $usernameKey, string $usernameValue, string $password, string $userModelClass, string $ipAddress = ''): bool
    {
        $rateLimitKey = "login_attempt:" . md5($usernameValue . '|' . $ipAddress);
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5, 300)) {
            throw new \RuntimeException("Too many login attempts. Please try again in 5 minutes.");
        }

        /** @var class-string<Model> $userModelClass */
        $user = $userModelClass::query()->where($usernameKey, '=', $usernameValue)->first();
        if ($user && isset($user['password']) && Password::verify($password, (string) $user['password'])) {
            RateLimiter::resetAttempts($rateLimitKey);
            // Instantiate Model instance from array result
            $userInstance = new $userModelClass($user);
            static::login($userInstance);
            return true;
        }

        RateLimiter::hit($rateLimitKey, 300);
        return false;
    }

    public static function login(Model $user): void
    {
        static::$user = $user;
        static::startSession();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
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

    protected static function config(): \Nexus\Foundation\Config
    {
        try {
            $app = \Nexus\Foundation\Application::getInstance();

            if ($app->has(\Nexus\Foundation\Config::class)) {
                $config = $app->make(\Nexus\Foundation\Config::class);

                if ($config instanceof \Nexus\Foundation\Config) {
                    return $config;
                }
            }
        } catch (\Throwable $e) {
            // Fallback to standalone default Config instance
        }

        return new \Nexus\Foundation\Config();
    }
}
