<?php
/**
 * Minimal router.
 *
 * URL format:  index.php?url=controller/action/param1/param2
 * With the bundled .htaccess this becomes clean URLs, e.g.  /requests/show/5
 */
class Router
{
    public function dispatch(string $url): void
    {
        $url = trim($url, '/');
        $segments = $url === '' ? [] : explode('/', $url);

        // Defaults: send visitors to the dashboard (or login) when no route is given.
        $controllerSlug = $segments[0] ?? '';
        $action         = $segments[1] ?? 'index';
        $params         = array_slice($segments, 2);

        if ($controllerSlug === '') {
            $controllerSlug = Auth::check() ? 'dashboard' : 'auth';
            $action = 'index';
        }

        // Map "requests" -> RequestController, "activity-logs" -> ActivityLogController, etc.
        $className = $this->toClassName($controllerSlug) . 'Controller';
        $file = __DIR__ . '/../controllers/' . $className . '.php';

        if (!file_exists($file)) {
            $this->notFound();
            return;
        }

        require $file;
        if (!class_exists($className)) {
            $this->notFound();
            return;
        }

        $controller = new $className();

        // Sanitise the action name and confirm it exists.
        $action = preg_replace('/[^a-zA-Z0-9_]/', '', $action);
        if ($action === '' || !method_exists($controller, $action)) {
            $this->notFound();
            return;
        }

        call_user_func_array([$controller, $action], $params);
    }

    /** "print-requests" -> "PrintRequests" */
    private function toClassName(string $slug): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug);
        $parts = preg_split('/[-_]/', $slug);
        return implode('', array_map('ucfirst', $parts));
    }

    private function notFound(): void
    {
        // Unknown routes for guests go to the login screen.
        if (!Auth::check()) {
            redirect('auth/login');
        }
        http_response_code(404);
        $viewFile = __DIR__ . '/../views/errors/404.php';
        ob_start();
        require $viewFile;
        $content = ob_get_clean();
        require __DIR__ . '/../views/layouts/main.php';
    }
}
