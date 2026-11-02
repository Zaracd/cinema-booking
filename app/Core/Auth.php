<?php
namespace App\Core;

use App\Models\User;

/**
 * Session login plus a "Remember me" cookie.
 * The cookie holds a random selector:validator pair; only a SHA-256 hash of the
 * validator is stored in the database, and the password is never in the cookie.
 */
class Auth
{
    private const COOKIE = 'remember_me';
    private const REMEMBER_DAYS = 30;
    private static ?array $user = null;

    public static function login(array $user, bool $remember = false): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['user_id'];
        self::$user = null;
        if ($remember) {
            self::issueRememberCookie((int) $user['user_id']);
        }
    }

    public static function logout(): void
    {
        if (!empty($_COOKIE[self::COOKIE])) {
            [$selector] = explode(':', $_COOKIE[self::COOKIE]) + [''];
            User::deleteRememberToken($selector);
            self::setCookie('', time() - 3600);
        }
        $_SESSION = [];
        session_regenerate_id(true);
        self::$user = null;
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        if (!empty($_SESSION['user_id'])) {
            self::$user = User::find((int) $_SESSION['user_id']);
            if (!self::$user) {
                unset($_SESSION['user_id']);
            }
        } elseif (!empty($_COOKIE[self::COOKIE])) {
            self::loginFromCookie($_COOKIE[self::COOKIE]);
        }
        return self::$user;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int) $u['user_id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'Admin';
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? null;
            flash('error', 'Please log in to continue.');
            redirect('/login');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            abort(403);
        }
    }

    private static function issueRememberCookie(int $userId): void
    {
        $selector  = bin2hex(random_bytes(9));
        $validator = bin2hex(random_bytes(32));
        $expires   = time() + self::REMEMBER_DAYS * 86400;
        User::saveRememberToken($userId, $selector, hash('sha256', $validator), date('Y-m-d H:i:s', $expires));
        self::setCookie("$selector:$validator", $expires);
    }

    private static function loginFromCookie(string $cookie): void
    {
        [$selector, $validator] = explode(':', $cookie) + ['', ''];
        $token = $selector ? User::findRememberToken($selector) : null;
        if ($token && strtotime($token['expires_at']) > time()
            && hash_equals($token['token_hash'], hash('sha256', $validator))) {
            $user = User::find((int) $token['user_id']);
            if ($user) {
                // Rotate the token each time it is used.
                User::deleteRememberToken($selector);
                self::login($user, true);
                self::$user = $user;
                return;
            }
        }
        self::setCookie('', time() - 3600);
    }

    private static function setCookie(string $value, int $expires): void
    {
        setcookie(self::COOKIE, $value, [
            'expires'  => $expires,
            'path'     => base_path() ?: '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
    }
}
