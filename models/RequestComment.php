<?php
/**
 * Two-way discussion thread attached to a print request.
 * Both the requesting member and the administrator can post.
 */
class RequestComment extends Model
{
    protected string $table = 'request_comments';

    /** All comments for a request, oldest first, with author name + role. */
    public function forRequest(int $requestId): array
    {
        return $this->fetchAll(
            "SELECT c.*, u.name AS author_name, u.role AS author_role
             FROM request_comments c
             LEFT JOIN users u ON u.id = c.user_id
             WHERE c.request_id = ?
             ORDER BY c.created_at ASC, c.id ASC",
            [$requestId]
        );
    }

    public function add(int $requestId, ?int $userId, string $body): int
    {
        $this->run(
            "INSERT INTO request_comments (request_id, user_id, body, created_at) VALUES (?,?,?,NOW())",
            [$requestId, $userId, $body]
        );
        return $this->lastId();
    }

    public function countFor(int $requestId): int
    {
        return (int) $this->scalar(
            "SELECT COUNT(*) FROM request_comments WHERE request_id = ?", [$requestId]);
    }
}
