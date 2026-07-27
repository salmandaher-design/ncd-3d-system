<?php
class User extends Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        return $this->fetch("SELECT * FROM users WHERE email = ? LIMIT 1", [$email]);
    }

    /** All users with their team name. */
    public function allWithTeam(): array
    {
        return $this->fetchAll(
            "SELECT u.*, t.name AS team_name
             FROM users u
             LEFT JOIN teams t ON t.id = u.team_id
             ORDER BY u.role ASC, u.name ASC"
        );
    }

    public function create(array $d): int
    {
        $this->run(
            "INSERT INTO users (name, email, password, role, team_id, is_active, created_at)
             VALUES (?,?,?,?,?,?,NOW())",
            [
                $d['name'],
                $d['email'],
                password_hash($d['password'], PASSWORD_DEFAULT),
                $d['role'],
                $d['team_id'] !== '' ? $d['team_id'] : null,
                $d['is_active'] ?? 1,
            ]
        );
        return $this->lastId();
    }

    public function update(int $id, array $d): void
    {
        // Update password only when a new one is supplied.
        if (!empty($d['password'])) {
            $this->run(
                "UPDATE users SET name=?, email=?, role=?, team_id=?, is_active=?, password=? WHERE id=?",
                [
                    $d['name'], $d['email'], $d['role'],
                    $d['team_id'] !== '' ? $d['team_id'] : null,
                    $d['is_active'] ?? 1,
                    password_hash($d['password'], PASSWORD_DEFAULT),
                    $id,
                ]
            );
        } else {
            $this->run(
                "UPDATE users SET name=?, email=?, role=?, team_id=?, is_active=? WHERE id=?",
                [
                    $d['name'], $d['email'], $d['role'],
                    $d['team_id'] !== '' ? $d['team_id'] : null,
                    $d['is_active'] ?? 1,
                    $id,
                ]
            );
        }
    }

    public function updatePassword(int $id, string $plain): void
    {
        $this->run("UPDATE users SET password=? WHERE id=?",
            [password_hash($plain, PASSWORD_DEFAULT), $id]);
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        if ($exceptId) {
            return (bool) $this->scalar(
                "SELECT 1 FROM users WHERE email = ? AND id <> ? LIMIT 1", [$email, $exceptId]);
        }
        return (bool) $this->scalar("SELECT 1 FROM users WHERE email = ? LIMIT 1", [$email]);
    }

    public function countAdmins(): int
    {
        return (int) $this->scalar("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    }
}
