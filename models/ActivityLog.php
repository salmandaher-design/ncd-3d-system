<?php
class ActivityLog extends Model
{
    protected string $table = 'activity_logs';

    /** Record an action. Safe to call anywhere. */
    public static function record(string $action, string $description = ''): void
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare(
                "INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (?,?,?,NOW())"
            );
            $stmt->execute([Auth::id(), $action, $description]);
        } catch (Throwable $e) {
            // Logging must never break the main flow.
        }
    }

    public function recent(int $limit = 30): array
    {
        return $this->fetchAll(
            "SELECT a.*, u.name AS user_name
             FROM activity_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC
             LIMIT " . (int) $limit
        );
    }
}
