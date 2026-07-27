<?php
class RequestFile extends Model
{
    protected string $table = 'request_files';

    public function forRequest(int $requestId): array
    {
        return $this->fetchAll(
            "SELECT * FROM request_files WHERE request_id = ? ORDER BY id ASC", [$requestId]);
    }

    public function add(int $requestId, array $file): int
    {
        $this->run(
            "INSERT INTO request_files (request_id, file_name, file_path, file_size, file_type, created_at)
             VALUES (?,?,?,?,?,NOW())",
            [$requestId, $file['original'], $file['path'], $file['size'], $file['type']]
        );
        return $this->lastId();
    }

    /** Delete all DB rows + physical files for a request. */
    public function deleteForRequest(int $requestId): void
    {
        foreach ($this->forRequest($requestId) as $f) {
            Upload::remove($f['file_path']);
        }
        $this->run("DELETE FROM request_files WHERE request_id = ?", [$requestId]);
    }
}
