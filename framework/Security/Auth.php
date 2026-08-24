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

    private const DUMMY_PASSWORD_HASH = '$2y$12$e0MYzXyjpJS7Pd0RVvHwHe1e8Xl1M1Y9V0.j3l8e3.H6dfI/f/IKc';

    public static function guard(Request $request, string $userModelClass): ?Model
    {
        // 1. Check Bearer Token (Stateless JWT)
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            $secret = static::config()->get('app.key', env('APP_KEY'));
            if (empty($secret) || strlen((string)$secret) < 16 || $secret === 'default_secret_key_32_bytes_len_!!') {
                throw new \RuntimeException("Application key [APP_KEY] is missing, insecurely configured, or under 16 characters long.");
            }

            $options = [
                'leeway' => (int) (getenv('JWT_LEEWAY_SECONDS') ?: 0),
            ];
            $issuer = static::config()->get('app.jwt_issuer', env('JWT_ISSUER'));
            if ($issuer) {
                $options['issuer'] = $issuer;
            }
            $audience = static::config()->get('app.jwt_audience', env('JWT_AUDIENCE'));
            if ($audience) {
                $options['audience'] = $audience;
            }

            $payload = Jwt::decode($token, (string)$secret, $options);
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
            $secure = filter_var(env('SESSION_SECURE', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'), FILTER_VALIDATE_BOOL);
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            if (!session_start()) {
                throw new \RuntimeException('Unable to start session.');
            }
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
        $hash = ($user && isset($user['password'])) ? (string) $user['password'] : static::DUMMY_PASSWORD_HASH;

        $passwordValid = Password::verify($password, $hash);

        if ($user && isset($user['password']) && $passwordValid) {
            RateLimiter::resetAttempts($rateLimitKey);
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
            $_SESSION = [];
            if (ini_get("session.use_cookies") && !headers_sent()) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            @session_destroy();
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
