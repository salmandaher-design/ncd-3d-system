<?php
class Filament extends Model
{
    protected string $table = 'filament';

    public function allSorted(): array
    {
        return $this->fetchAll("SELECT * FROM filament ORDER BY remaining_weight ASC");
    }

    public function create(array $d): int
    {
        $this->run(
            "INSERT INTO filament (color, material, remaining_weight, spools, location, notes, created_at, updated_at)
             VALUES (?,?,?,?,?,?,NOW(),NOW())",
            [$d['color'], $d['material'], $d['remaining_weight'], $d['spools'] ?? 1, $d['location'], $d['notes']]
        );
        return $this->lastId();
    }

    public function update(int $id, array $d): void
    {
        $this->run(
            "UPDATE filament SET color=?, material=?, remaining_weight=?, spools=?, location=?, notes=?, updated_at=NOW() WHERE id=?",
            [$d['color'], $d['material'], $d['remaining_weight'], $d['spools'] ?? 1, $d['location'], $d['notes'], $id]
        );
    }

    /** Deduct used grams from a spool (never below zero). */
    public function deduct(int $id, float $grams): void
    {
        $this->run(
            "UPDATE filament SET remaining_weight = GREATEST(0, remaining_weight - ?), updated_at=NOW() WHERE id=?",
            [$grams, $id]
        );
    }

    /** Return used grams to a spool (e.g. when an approved request is cancelled). */
    public function refund(int $id, float $grams): void
    {
        $this->run(
            "UPDATE filament SET remaining_weight = remaining_weight + ?, updated_at=NOW() WHERE id=?",
            [$grams, $id]
        );
    }

    /** Spools at or below the low threshold. */
    public function lowStock(): array
    {
        return $this->fetchAll(
            "SELECT * FROM filament WHERE remaining_weight < ? ORDER BY remaining_weight ASC",
            [FILAMENT_WARN_LOW]
        );
    }

    public function totalRemaining(): float
    {
        return (float) $this->scalar("SELECT COALESCE(SUM(remaining_weight),0) FROM filament");
    }

    /** Options for a <select>. */
    public function options(): array
    {
        return $this->fetchAll("SELECT id, color, material, remaining_weight FROM filament ORDER BY color");
    }
}
