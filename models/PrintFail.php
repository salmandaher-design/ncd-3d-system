<?php
/**
 * The Wall of Spaghetti — memorialised failed prints.
 */
class PrintFail extends Model
{
    protected string $table = 'print_fails';

    /** All fails, freshest first, with author + team names. */
    public function wall(): array
    {
        return $this->fetchAll(
            "SELECT f.*, u.name AS author_name, t.name AS team_name
             FROM print_fails f
             LEFT JOIN users u ON u.id = f.user_id
             LEFT JOIN teams t ON t.id = f.team_id
             ORDER BY f.created_at DESC, f.id DESC"
        );
    }

    public function create(array $d): int
    {
        $this->run(
            "INSERT INTO print_fails (user_id, team_id, caption, image_path, grams, printer_name, created_at)
             VALUES (?,?,?,?,?,?,NOW())",
            [
                $d['user_id'], $d['team_id'] ?: null, $d['caption'],
                $d['image_path'], $d['grams'] ?: 0, ($d['printer_name'] ?? '') !== '' ? $d['printer_name'] : null,
            ]
        );
        return $this->lastId();
    }

    /** Total filament sacrificed across all fails (grams). */
    public function totalGrams(): float
    {
        return (float) $this->scalar("SELECT COALESCE(SUM(grams),0) FROM print_fails");
    }

    public function count(): int
    {
        return (int) $this->scalar("SELECT COUNT(*) FROM print_fails");
    }

    public function countForUser(int $userId): int
    {
        return (int) $this->scalar("SELECT COUNT(*) FROM print_fails WHERE user_id = ?", [$userId]);
    }

    /** Team with the most fails this calendar month (the "Spaghetti Champion"). */
    public function championThisMonth(): ?array
    {
        return $this->fetch(
            "SELECT t.name AS team_name, COUNT(*) AS fails, COALESCE(SUM(f.grams),0) AS grams
             FROM print_fails f
             JOIN teams t ON t.id = f.team_id
             WHERE f.created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
             GROUP BY f.team_id
             ORDER BY fails DESC, grams DESC
             LIMIT 1"
        );
    }

    public function addRespect(int $id): int
    {
        $this->run("UPDATE print_fails SET respects = respects + 1 WHERE id = ?", [$id]);
        return (int) $this->scalar("SELECT respects FROM print_fails WHERE id = ?", [$id]);
    }
}
