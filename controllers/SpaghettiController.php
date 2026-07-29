<?php
/**
 * The Wall of Spaghetti — a light-hearted hall of fame for failed prints.
 * Visible to everyone who is signed in.
 */
class SpaghettiController extends Controller
{
    private PrintFail $fails;

    public function __construct()
    {
        $this->fails = new PrintFail();
    }

    public function index(): void
    {
        $this->requireLogin();
        $this->view('spaghetti/index', [
            'pageTitle'  => 'Wall of Spaghetti',
            'fails'      => $this->fails->wall(),
            'totalGrams' => $this->fails->totalGrams(),
            'champion'   => $this->fails->championThisMonth(),
            'respected'  => $_SESSION['respected'] ?? [],
        ]);
    }

    /** Immortalise a fresh disaster. */
    public function store(): void
    {
        $this->requireLogin();
        $this->requireCsrf();

        $caption = trim($_POST['caption'] ?? '');
        $grams   = is_numeric($_POST['grams'] ?? null) ? (float) $_POST['grams'] : 0;
        $printer = trim($_POST['printer_name'] ?? '');

        if ($caption === '') {
            Flash::set('error', 'Every masterpiece needs a caption.');
            redirect('spaghetti');
        }

        try {
            $imagePath = Upload::image($_FILES['image'] ?? []);
        } catch (RuntimeException $e) {
            Flash::set('error', $e->getMessage());
            redirect('spaghetti');
        }

        $this->fails->create([
            'user_id'      => Auth::id(),
            'team_id'      => Auth::teamId(),
            'caption'      => $caption,
            'image_path'   => $imagePath,
            'grams'        => $grams,
            'printer_name' => $printer,
        ]);

        ActivityLog::record('spaghetti_post', 'Added a print fail to the Wall of Spaghetti 🍝');
        Flash::set('success', 'Immortalised on the Wall of Spaghetti. F.');
        redirect('spaghetti');
    }

    /** "F to pay respects" — one per fail per session. Returns JSON for AJAX. */
    public function respect(string $id = '0'): void
    {
        $this->requireLogin();
        $this->requireCsrf();
        $id = (int) $id;

        $already = $_SESSION['respected'][$id] ?? false;
        if (!$this->fails->find($id)) {
            $this->json(['ok' => false], 404);
        }

        if ($already) {
            $count = (int) $this->fails->find($id)['respects'];
            $this->json(['ok' => true, 'respects' => $count, 'already' => true]);
        }

        $count = $this->fails->addRespect($id);
        $_SESSION['respected'][$id] = true;
        $this->json(['ok' => true, 'respects' => $count]);
    }

    /** Delete a fail (admin, or the person who posted it). */
    public function delete(string $id = '0'): void
    {
        $this->requireLogin();
        $this->requireCsrf();
        $id = (int) $id;

        $fail = $this->fails->find($id);
        if ($fail) {
            $isOwner = (int) ($fail['user_id'] ?? 0) === Auth::id();
            if (!Auth::isAdmin() && !$isOwner) {
                http_response_code(403);
                $this->view('errors/403');
                return;
            }
            Upload::remove($fail['image_path'] ?? null);
            $this->fails->delete($id);
            Flash::set('success', 'Fail deleted. We shall never speak of it again.');
        }
        redirect('spaghetti');
    }
}
