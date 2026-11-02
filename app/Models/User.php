<?php
namespace App\Models;

use App\Core\Model;

class User extends Model
{
    public static function find(int $id): ?array
    {
        return self::one('SELECT user_id, full_name, email, phone, role, created_at FROM users WHERE user_id = ?', [$id]);
    }

    /** Includes the password hash, for login only. */
    public static function findByEmail(string $email): ?array
    {
        return self::one('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public static function emailExists(string $email, int $exceptId = 0): bool
    {
        return (bool) self::value('SELECT COUNT(*) FROM users WHERE email = ? AND user_id <> ?', [$email, $exceptId]);
    }

    public static function create(string $name, string $email, string $phone, string $password, string $role = 'Customer'): int
    {
        return self::insert(
            'INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)',
            [$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), $role]
        );
    }

    public static function updateProfile(int $id, string $name, string $phone): void
    {
        self::run('UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?', [$name, $phone, $id]);
    }

    public static function updatePassword(int $id, string $password): void
    {
        self::run('UPDATE users SET password = ? WHERE user_id = ?', [password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public static function setRole(int $id, string $role): void
    {
        self::run('UPDATE users SET role = ? WHERE user_id = ?', [$role, $id]);
    }

    public static function delete(int $id): void
    {
        self::run('DELETE FROM users WHERE user_id = ?', [$id]);
    }

    public static function search(string $q = '', string $role = ''): array
    {
        $sql = 'SELECT u.user_id, u.full_name, u.email, u.phone, u.role, u.created_at,
                       (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.user_id) AS bookings_count
                FROM users u WHERE 1';
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            array_push($params, "%$q%", "%$q%", "%$q%");
        }
        if ($role !== '') {
            $sql .= ' AND u.role = ?';
            $params[] = $role;
        }
        return self::all($sql . ' ORDER BY u.created_at DESC', $params);
    }

    public static function count(): int
    {
        return (int) self::value('SELECT COUNT(*) FROM users');
    }

    public static function countAdmins(): int
    {
        return (int) self::value("SELECT COUNT(*) FROM users WHERE role = 'Admin'");
    }

    // ---- "Remember me" tokens ----
    public static function saveRememberToken(int $userId, string $selector, string $hash, string $expires): void
    {
        self::run('DELETE FROM remember_tokens WHERE expires_at < NOW()');
        self::run('INSERT INTO remember_tokens (selector, user_id, token_hash, expires_at) VALUES (?, ?, ?, ?)',
            [$selector, $userId, $hash, $expires]);
    }

    public static function findRememberToken(string $selector): ?array
    {
        return self::one('SELECT * FROM remember_tokens WHERE selector = ?', [$selector]);
    }

    public static function deleteRememberToken(string $selector): void
    {
        self::run('DELETE FROM remember_tokens WHERE selector = ?', [$selector]);
    }
}
