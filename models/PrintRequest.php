<?php
/**
 * A 3D print request. (Class name avoids the reserved word "Request".)
 * Table: requests
 */
class PrintRequest extends Model
{
    protected string $table = 'requests';

    public const STATUSES = [
        'Submitted', 'Approved', 'Printing', 'Completed', 'Rejected', 'Cancelled',
    ];
    public const PRIORITIES = ['Low', 'Medium', 'High'];

    /** Base SELECT joining team, user, printer and filament. */
    private function baseSelect(): string
    {
        return "SELECT r.*,
                       t.name  AS team_name,
                       u.name  AS requester_name,
                       u.phone AS requester_phone,
                       p.name  AS printer_name,
                       f.color AS filament_color,
                       j.title  AS job_title,
                       j.status AS job_status
                FROM requests r
                LEFT JOIN teams t   ON t.id = r.team_id
                LEFT JOIN users u   ON u.id = r.user_id
                LEFT JOIN printers p ON p.id = r.printer_id
                LEFT JOIN filament f ON f.id = r.filament_id
                LEFT JOIN print_jobs j ON j.id = r.job_id";
    }

    public function findFull(int $id): ?array
    {
        return $this->fetch($this->baseSelect() . " WHERE r.id = ?", [$id]);
    }

    /**
     * Search / filter list.
     * @param array $f keys: q, status, priority, team_id, user_id
     */
    public function search(array $f = []): array
    {
        $sql = $this->baseSelect() . " WHERE 1=1";
        $params = [];

        if (!empty($f['q'])) {
            $sql .= " AND (r.project_name LIKE ? OR t.name LIKE ? OR u.name LIKE ?)";
            $like = '%' . $f['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        if (!empty($f['status'])) {
            $sql .= " AND r.status = ?";
            $params[] = $f['status'];
        }
        if (!empty($f['priority'])) {
            $sql .= " AND r.priority = ?";
            $params[] = $f['priority'];
        }
        if (!empty($f['team_id'])) {
            $sql .= " AND r.team_id = ?";
            $params[] = $f['team_id'];
        }
        if (!empty($f['user_id'])) {
            $sql .= " AND r.user_id = ?";
            $params[] = $f['user_id'];
        }
        if (!empty($f['job_id'])) {
            $sql .= " AND r.job_id = ?";
            $params[] = $f['job_id'];
        }

        $sql .= " ORDER BY FIELD(r.priority,'High','Medium','Low'), r.created_at DESC";
        return $this->fetchAll($sql, $params);
    }

    /**
     * Requests that may still be merged into a plate: not finished, not
     * rejected/cancelled, and not already in another job.
     */
    public function mergeable(?int $excludeJobId = null): array
    {
        $sql = $this->baseSelect() . " WHERE r.status IN ('Submitted','Approved') AND r.job_id IS NULL";
        $params = [];
        if ($excludeJobId !== null) {
            $sql = $this->baseSelect() .
                " WHERE r.status IN ('Submitted','Approved') AND (r.job_id IS NULL OR r.job_id = ?)";
            $params[] = $excludeJobId;
        }
        $sql .= " ORDER BY r.color, FIELD(r.priority,'High','Medium','Low'), r.created_at DESC";
        return $this->fetchAll($sql, $params);
    }

    public function forUser(int $userId): array
    {
        return $this->search(['user_id' => $userId]);
    }

    public function recent(int $limit = 6): array
    {
        return $this->fetchAll($this->baseSelect() . " ORDER BY r.created_at DESC LIMIT " . (int) $limit);
    }

    public function byStatus(string $status): array
    {
        return $this->search(['status' => $status]);
    }

    public function create(array $d): int
    {
        $this->run(
            "INSERT INTO requests
                (user_id, team_id, project_name, description, priority, color, transaction_no, status, image_path, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?, 'Submitted', ?, NOW(), NOW())",
            [
                $d['user_id'], $d['team_id'], $d['project_name'], $d['description'],
                $d['priority'], $d['color'], ($d['transaction_no'] ?? '') !== '' ? $d['transaction_no'] : null,
                $d['image_path'],
            ]
        );
        $id = $this->lastId();

        // Auto-generate a transaction number when none was supplied.
        if (($d['transaction_no'] ?? '') === '') {
            $this->run("UPDATE requests SET transaction_no=? WHERE id=?", [$this->makeTransactionNo($id), $id]);
        }
        return $id;
    }

    /** Build an automatic transaction number, e.g. NCD-2026-0009. */
    public function makeTransactionNo(int $id): string
    {
        $prefix = defined('TRANSACTION_PREFIX') ? TRANSACTION_PREFIX : 'NCD';
        return $prefix . '-' . date('Y') . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    public function setStatus(int $id, string $status): void
    {
        $this->run("UPDATE requests SET status=?, updated_at=NOW() WHERE id=?", [$status, $id]);
    }

    /** Update admin planning fields (notes, estimate, transaction no.). */
    public function updateNotes(int $id, ?string $notes, $estWeight, ?string $txn = null): void
    {
        $this->run(
            "UPDATE requests SET admin_notes=?, estimated_weight=?, transaction_no=?, updated_at=NOW() WHERE id=?",
            [
                $notes,
                ($estWeight !== '' && $estWeight !== null) ? $estWeight : null,
                ($txn !== null && $txn !== '') ? $txn : null,
                $id,
            ]
        );
    }

    /** Record the actual filament used (grams). */
    public function setActualWeight(int $id, float $grams): void
    {
        $this->run("UPDATE requests SET actual_weight=?, updated_at=NOW() WHERE id=?", [$grams, $id]);
    }

    /**
     * Approve a request AND commit filament usage in one step.
     * Records the chosen spool + weight and marks it as deducted.
     */
    public function approveWithFilament(int $id, int $filamentId, float $weight): void
    {
        $this->run(
            "UPDATE requests
             SET status='Approved', filament_id=?, actual_weight=?, filament_deducted=1, updated_at=NOW()
             WHERE id=?",
            [$filamentId, $weight, $id]
        );
    }

    /** Reset the deduction flag (after a refund). */
    public function clearDeducted(int $id): void
    {
        $this->run("UPDATE requests SET filament_deducted=0, updated_at=NOW() WHERE id=?", [$id]);
    }

    /** Approved -> Printing (filament already chosen at approval; pick a printer). */
    public function startPrinting(int $id, int $printerId): void
    {
        $this->run(
            "UPDATE requests SET status='Printing', printer_id=?, updated_at=NOW() WHERE id=?",
            [$printerId, $id]
        );
    }

    // ---- Dashboard aggregates ----

    public function countAll(): int
    {
        return (int) $this->scalar("SELECT COUNT(*) FROM requests");
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->scalar("SELECT COUNT(*) FROM requests WHERE status=?", [$status]);
    }

    /** Requests awaiting approval (Submitted). */
    public function countPending(): int
    {
        return $this->countByStatus('Submitted');
    }

    /** Requests grouped by the last 6 months. */
    public function requestsByMonth(int $months = 6): array
    {
        $rows = $this->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c
             FROM requests
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY ym ORDER BY ym ASC",
            [$months]
        );
        $map = [];
        foreach ($rows as $r) {
            $map[$r['ym']] = (int) $r['c'];
        }
        // Fill missing months with zero so the chart is continuous.
        $out = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-$i month"));
            $out[] = ['label' => date('M', strtotime($ym . '-01')), 'value' => $map[$ym] ?? 0];
        }
        return $out;
    }

    /** Filament consumed (actual grams) per month. */
    public function filamentByMonth(int $months = 6): array
    {
        $rows = $this->fetchAll(
            "SELECT DATE_FORMAT(updated_at, '%Y-%m') AS ym, SUM(actual_weight) AS g
             FROM requests
             WHERE status='Completed' AND actual_weight IS NOT NULL
               AND updated_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY ym ORDER BY ym ASC",
            [$months]
        );
        $map = [];
        foreach ($rows as $r) {
            $map[$r['ym']] = (float) $r['g'];
        }
        $out = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-$i month"));
            $out[] = ['label' => date('M', strtotime($ym . '-01')), 'value' => round($map[$ym] ?? 0, 1)];
        }
        return $out;
    }
}
