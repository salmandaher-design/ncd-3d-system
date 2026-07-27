<?php
/**
 * Session-based authentication.
 */
class Auth
{
    /** Attempt to log a user in. Returns true on success. */
    public static function attempt(string $email, string $password): bool
    {
        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user || (int) $user['is_active'] !== 1) {
            return false;
        }
        if (!password_verify($password, $user['password'])) {
            return false;
        }

        // Rehash if the algorithm/cost has changed.
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $userModel->updatePassword((int) $user['id'], $password);
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'      => (int) $user['id'],
            'name'    => $user['name'],
            'email'   => $user['email'],
            'role'    => $user['role'],
            'team_id' => $user['team_id'] !== null ? (int) $user['team_id'] : null,
        ];
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return (($_SESSION['user']['role'] ?? '') === 'admin');
    }

    public static function teamId(): ?int
    {
        return $_SESSION['user']['team_id'] ?? null;
    }

    public static function name(): string
    {
        return $_SESSION['user']['name'] ?? '';
    }
}
