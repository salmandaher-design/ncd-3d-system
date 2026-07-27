<?php
class Printer extends Model
{
    protected string $table = 'printers';

    public function allSorted(): array
    {
        return $this->fetchAll("SELECT * FROM printers ORDER BY id ASC");
    }

    public function rename(int $id, string $name): void
    {
        $this->run("UPDATE printers SET name=?, updated_at=NOW() WHERE id=?", [$name, $id]);
    }

    /** Mark a printer busy with the current job details. */
    public function setBusy(int $id, string $project, string $team, string $operator): void
    {
        $this->run(
            "UPDATE printers
             SET status='Busy', current_project=?, current_team=?, current_operator=?, updated_at=NOW()
             WHERE id=?",
            [$project, $team, $operator, $id]
        );
    }

    /** Free a printer. */
    public function setIdle(int $id): void
    {
        $this->run(
            "UPDATE printers
             SET status='Idle', current_project=NULL, current_team=NULL, current_operator=NULL, updated_at=NOW()
             WHERE id=?",
            [$id]
        );
    }

    public function busy(): array
    {
        return $this->fetchAll("SELECT * FROM printers WHERE status='Busy' ORDER BY id");
    }

    public function countBusy(): int
    {
        return (int) $this->scalar("SELECT COUNT(*) FROM printers WHERE status='Busy'");
    }
}
