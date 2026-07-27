<?php
class NewsController extends Controller
{
    private News $news;

    public function __construct()
    {
        $this->news = new News();
    }

    /** Where to return after saving — restricted to known pages. */
    private function backTo(string $default = 'news'): string
    {
        $back = $_POST['back'] ?? '';
        return in_array($back, ['dashboard', 'news'], true) ? $back : $default;
    }

    /** News archive — visible to everyone who is signed in. */
    public function index(): void
    {
        $this->requireLogin();
        $all = $this->news->allWithAuthor();

        $this->view('news/index', [
            'pageTitle' => 'News',
            'current'   => $all[0] ?? null,
            'archive'   => array_slice($all, 1),
        ]);
    }

    /**
     * Publish a NEW item (admin). The previous banner is kept and moves
     * into the archive automatically.
     */
    public function publish(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if ($title === '') {
            Flash::set('error', 'News title is required.');
            redirect($this->backTo('dashboard'));
        }

        try {
            $imagePath = Upload::image($_FILES['image'] ?? []);
        } catch (RuntimeException $e) {
            Flash::set('error', $e->getMessage());
            redirect($this->backTo('dashboard'));
        }

        $this->news->publish($title, $content, $imagePath, Auth::id());

        ActivityLog::record('news_publish', 'Published news: ' . $title);
        Flash::set('success', 'News published. The previous item moved to the archive.');
        redirect($this->backTo('dashboard'));
    }

    /** Edit an existing item in place (admin). */
    public function update(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;

        $item = $this->news->find($id);
        if (!$item) {
            redirect('news');
        }

        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        if ($title === '') {
            Flash::set('error', 'News title is required.');
            redirect($this->backTo('news'));
        }

        try {
            $imagePath = Upload::image($_FILES['image'] ?? []);
        } catch (RuntimeException $e) {
            Flash::set('error', $e->getMessage());
            redirect($this->backTo('news'));
        }

        // Remove the old picture only when a new one replaces it.
        if ($imagePath !== null && !empty($item['image_path'])) {
            Upload::remove($item['image_path']);
        }

        $this->news->edit($id, $title, $content, $imagePath);

        ActivityLog::record('news_update', 'Updated news #' . $id);
        Flash::set('success', 'News updated.');
        redirect($this->backTo('news'));
    }

    /** Delete an item (admin). */
    public function delete(string $id = '0'): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $id = (int) $id;

        $item = $this->news->find($id);
        if ($item) {
            Upload::remove($item['image_path'] ?? null);
            $this->news->delete($id);
            ActivityLog::record('news_delete', 'Deleted news #' . $id);
            Flash::set('success', 'News item deleted.');
        }
        redirect('news');
    }
}
