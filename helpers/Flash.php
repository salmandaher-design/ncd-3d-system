<?php
/**
 * One-time flash messages stored in the session.
 */
class Flash
{
    public static function set(string $type, string $message): void
    {
        $_SESSION['_flash'][$type] = $message;
    }

    public static function all(): array
    {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flash;
    }

    /** Render all pending flash messages as Bootstrap alerts. */
    public static function render(): string
    {
        $map = [
            'success' => ['alert-success', 'check-circle-fill'],
            'error'   => ['alert-danger',  'exclamation-triangle-fill'],
            'warning' => ['alert-warning', 'exclamation-circle-fill'],
            'info'    => ['alert-info',    'info-circle-fill'],
        ];
        $html = '';
        foreach (self::all() as $type => $message) {
            [$class, $icon] = $map[$type] ?? ['alert-secondary', 'info-circle-fill'];
            $html .= '<div class="alert ' . $class . ' alert-dismissible fade show d-flex align-items-center" role="alert">'
                . '<i class="bi bi-' . $icon . ' me-2"></i>'
                . '<div>' . e($message) . '</div>'
                . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
                . '</div>';
        }
        return $html;
    }
}
