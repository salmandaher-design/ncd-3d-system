<?php
/**
 * A print job = several requests merged onto ONE physical plate.
 *
 * The job owns the printer, the filament and the total weight, so filament is
 * deducted once for the whole plate instead of once per request. The member
 * requests inside a job follow the job's status.
 */
class PrintJob extends Model
{
    protected string $table = 'print_jobs';

    public const STATUSES = ['Planned', 'Approved', 'Printing', 'Completed', 'Cancelled'];

    /** Job status -> the status its member requests should carry. */
    public const REQUEST_STATUS = [
        'Planned'   => 'Submitted',
        'Approved'  => 'Approved',
        'Printing'  => 'Printing',
        'Completed' => 'Completed',
        'Cancelled' => 'Cancelled',
    ];

    private function baseSelect(): string
    {
        return "SELECT j.*,
                       p.name  AS printer_name,
                       f.color AS filament_color,
                       u.name  AS creator_name,
                       (SELECT COUNT(*) FROM requests r WHERE r.job_id = j.id) AS request_count
                FROM print_jobs j
                LEFT JOIN printers p ON p.id = j.printer_id
                LEFT JOIN filament f ON f.id = j.filament_id
                LEFT JOIN users    u ON u.id = j.created_by";
    }

    public function findFull(int $id): ?array
    {
        return $this->fetch($this->baseSelect() . " WHERE j.id = ?", [$id]);
    }

    /** All jobs, open ones first, newest first. */
    public function allWithCounts(): array
    {
        return $this->fetchAll(
            $this->baseSelect() .
            " ORDER BY FIELD(j.status,'Printing','Approved','Planned','Completed','Cancelled'), j.created_at DESC"
        );
    }

    /** Jobs that can still take more requests. */
    public function openJobs(): array
    {
        return $this->fetchAll(
            $this->baseSelect() . " WHERE j.status IN ('Planned','Approved') ORDER BY j.created_at DESC"
        );
    }

    public function create(string $title, ?int $userId, ?string $notes = null): int
    {
        $this->run(
            "INSERT INTO print_jobs (title, status, notes, created_by, created_at, updated_at)
             VALUES (?, 'Planned', ?, ?, NOW(), NOW())",
            [$title, $notes, $userId]
        );
        return $this->lastId();
    }

    /** The requests merged into this job. */
    public function requests(int $jobId): array
    {
        return $this->fetchAll(
            "SELECT r.*, t.name AS team_name,
                    u.name AS requester_name, u.phone AS requester_phone
             FROM requests r
             LEFT JOIN teams t ON t.id = r.team_id
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.job_id = ?
             ORDER BY r.id ASC",
            [$jobId]
        );
    }

    /**
     * The plate's requests grouped by the member who submitted them, so the
     * admin can send ONE WhatsApp message per person instead of one per
     * request. Returns [user_id => ['name','phone','requests'=>[...]]].
     */
    public function requestsByRequester(int $jobId): array
    {
        $grouped = [];
        foreach ($this->requests($jobId) as $r) {
            $uid = (int) ($r['user_id'] ?? 0);
            if (!isset($grouped[$uid])) {
                $grouped[$uid] = [
                    'name'     => $r['requester_name'] ?? 'Unknown',
                    'phone'    => $r['requester_phone'] ?? null,
                    'team'     => $r['team_name'] ?? null,
                    'requests' => [],
                ];
            }
            $grouped[$uid]['requests'][] = $r;
        }
        return $grouped;
    }

    public function attach(int $jobId, int $requestId): void
    {
        $this->run("UPDATE requests SET job_id = ?, updated_at = NOW() WHERE id = ?", [$jobId, $requestId]);
    }

    public function detach(int $requestId): void
    {
        $this->run("UPDATE requests SET job_id = NULL, updated_at = NOW() WHERE id = ?", [$requestId]);
    }

    /** Unlink every request (used before deleting a job). */
    public function detachAll(int $jobId): void
    {
        $this->run("UPDATE requests SET job_id = NULL, updated_at = NOW() WHERE job_id = ?", [$jobId]);
    }

    /** Push the job's status onto all of its requests. */
    public function syncRequestStatus(int $jobId, string $jobStatus): void
    {
        $target = self::REQUEST_STATUS[$jobStatus] ?? null;
        if ($target === null) return;
        $this->run("UPDATE requests SET status = ?, updated_at = NOW() WHERE job_id = ?", [$target, $jobId]);
    }

    public function setStatus(int $id, string $status): void
    {
        $this->run("UPDATE print_jobs SET status = ?, updated_at = NOW() WHERE id = ?", [$status, $id]);
    }

    public function updateDetails(int $id, string $title, ?string $notes): void
    {
        $this->run("UPDATE print_jobs SET title = ?, notes = ?, updated_at = NOW() WHERE id = ?",
            [$title, $notes, $id]);
    }

    /** Approve the plate: record filament + total weight (deduction happens in the controller). */
    public function approve(int $id, int $filamentId, float $weight): void
    {
        $this->run(
            "UPDATE print_jobs
             SET status='Approved', filament_id=?, total_weight=?, filament_deducted=1, updated_at=NOW()
             WHERE id=?",
            [$filamentId, $weight, $id]
        );
    }

    public function startPrinting(int $id, int $printerId): void
    {
        $this->run("UPDATE print_jobs SET status='Printing', printer_id=?, updated_at=NOW() WHERE id=?",
            [$printerId, $id]);
    }

    public function clearDeducted(int $id): void
    {
        $this->run("UPDATE print_jobs SET filament_deducted=0, updated_at=NOW() WHERE id=?", [$id]);
    }

    /** Sum of the per-request estimates, used to pre-fill the plate weight. */
    public function estimatedTotal(int $jobId): float
    {
        return (float) $this->scalar(
            "SELECT COALESCE(SUM(COALESCE(estimated_weight, actual_weight, 0)),0) FROM requests WHERE job_id = ?",
            [$jobId]
        );
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->scalar("SELECT COUNT(*) FROM print_jobs WHERE status = ?", [$status]);
    }
}
