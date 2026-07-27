<?php
/**
 * Dashboard news.
 * The most recent row is displayed as the banner; every older row
 * automatically becomes part of the news archive ("الأخبار القديمة").
 */
class News extends Model
{
    protected string $table = 'news';

    /** The current banner item (newest), or null when there is none. */
    public function latest(): ?array
    {
        return $this->fetch(
            "SELECT n.*, u.name AS author
             FROM news n LEFT JOIN users u ON u.id = n.user_id
             ORDER BY n.created_at DESC, n.id DESC LIMIT 1"
        );
    }

    /** Every item, newest first. */
    public function allWithAuthor(): array
    {
        return $this->fetchAll(
            "SELECT n.*, u.name AS author
             FROM news n LEFT JOIN users u ON u.id = n.user_id
             ORDER BY n.created_at DESC, n.id DESC"
        );
    }

    /** Older items only (everything except the current banner). */
    public function archive(): array
    {
        $all = $this->allWithAuthor();
        return array_slice($all, 1);
    }

    /**
     * Publish a new item. It becomes the banner and pushes the previous
     * one into the archive automatically (no data is overwritten).
     */
    public function publish(string $title, ?string $content, ?string $imagePath, ?int $userId): int
    {
        $this->run(
            "INSERT INTO news (title, content, image_path, user_id, created_at) VALUES (?,?,?,?,NOW())",
            [$title, $content, $imagePath, $userId]
        );
        return $this->lastId();
    }

    /** Edit an existing item in place (fixing a typo, replacing the image…). */
    public function edit(int $id, string $title, ?string $content, ?string $imagePath): void
    {
        if ($imagePath !== null) {
            $this->run("UPDATE news SET title=?, content=?, image_path=? WHERE id=?",
                [$title, $content, $imagePath, $id]);
        } else {
            $this->run("UPDATE news SET title=?, content=? WHERE id=?", [$title, $content, $id]);
        }
    }

    public function count(): int
    {
        return (int) $this->scalar("SELECT COUNT(*) FROM news");
    }
}
