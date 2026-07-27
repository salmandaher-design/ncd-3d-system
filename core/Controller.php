<?php
/**
 * Base controller. Handles view rendering and common guards.
 */
abstract class Controller
{
    /**
     * Render a view inside the main layout.
     *
     * @param string $view  e.g. "requests/index"
     * @param array  $data  variables extracted into the view scope
     */
    protected function view(string $view, array $data = [], ?string $layout = 'main'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            http_response_code(500);
            die('View not found: ' . htmlspecialchars($view));
        }

        // Buffer the view so it can be injected into the layout.
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }
        require __DIR__ . '/../views/layouts/' . $layout . '.php';
    }

    /** Return a JSON response and stop. */
    protected function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /** Require an authenticated user; redirect to login otherwise. */
    protected function requireLogin(): void
    {
        if (!Auth::check()) {
            Flash::set('error', 'Please sign in to continue.');
            redirect('auth/login');
        }
    }

    /** Require an administrator; 403 otherwise. */
    protected function requireAdmin(): void
    {
        $this->requireLogin();
        if (!Auth::isAdmin()) {
            http_response_code(403);
            $this->view('errors/403');
            exit;
        }
    }

    /** Validate the CSRF token on state-changing POST requests. */
    protected function requireCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            if (!Csrf::validate($token)) {
                http_response_code(419);
                if (is_ajax()) {
                    $this->json(['ok' => false, 'error' => 'Invalid or expired security token.'], 419);
                }
                die('Invalid or expired security token. Please go back and try again.');
            }
        }
    }
}
