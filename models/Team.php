<?php
class Team extends Model
{
    protected string $table = 'teams';

    /** All teams with member and request counts. */
    public function allWithCounts(): array
    {
        return $this->fetchAll(
            "SELECT t.*,
                    (SELECT COUNT(*) FROM users u WHERE u.team_id = t.id)      AS member_count,
                    (SELECT COUNT(*) FROM requests r WHERE r.team_id = t.id)   AS request_count
             FROM teams t
             ORDER BY t.name ASC"
        );
    }

    public function create(array $d): int
    {
        $this->run(
            "INSERT INTO teams (name, competition, supervisor, created_at) VALUES (?,?,?,NOW())",
            [$d['name'], $d['competition'], $d['supervisor']]
        );
        return $this->lastId();
    }

    public function update(int $id, array $d): void
    {
        $this->run(
            "UPDATE teams SET name=?, competition=?, supervisor=? WHERE id=?",
            [$d['name'], $d['competition'], $d['supervisor'], $id]
        );
    }

    public function members(int $teamId): array
    {
        return $this->fetchAll(
            "SELECT id, name, email FROM users WHERE team_id = ? ORDER BY name", [$teamId]);
    }
}
