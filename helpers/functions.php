<?php
/**
 * Global helper functions.
 */

/** Compute the application base path (works in a subfolder too). */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $base = ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
    }
    return $base;
}

/** Build an application URL, e.g. url('requests/show/5'). */
function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

/** Build an asset URL, e.g. asset('css/style.css'). */
function asset(string $path = ''): string
{
    return base_path() . '/assets/' . ltrim($path, '/');
}

/** Escape output for HTML. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Redirect to an application route and stop. */
function redirect(string $path = ''): void
{
    header('Location: ' . url($path));
    exit;
}

/** Is the current request an AJAX / fetch call? */
function is_ajax(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/** Old input value helper for re-populating forms. */
function old(string $key, $default = ''): string
{
    return e($_SESSION['_old'][$key] ?? $default);
}

/** Format a date/time for display. */
function fmt_date(?string $dt, string $format = 'M j, Y'): string
{
    if (!$dt) return '—';
    $ts = strtotime($dt);
    return $ts ? date($format, $ts) : '—';
}

function fmt_datetime(?string $dt): string
{
    return fmt_date($dt, 'M j, Y g:i A');
}

/** Bootstrap badge class for a request status. */
function status_class(string $status): string
{
    return [
        'Submitted' => 'status-submitted',
        'Approved'  => 'status-approved',
        'Printing'  => 'status-printing',
        'Completed' => 'status-completed',
        'Rejected'  => 'status-rejected',
        'Cancelled' => 'status-cancelled',
    ][$status] ?? 'status-submitted';
}

/** Bootstrap Icon name for a request status. */
function status_icon(string $status): string
{
    return [
        'Submitted' => 'inbox',
        'Approved'  => 'check-circle',
        'Printing'  => 'printer',
        'Completed' => 'check-all',
        'Rejected'  => 'x-circle',
        'Cancelled' => 'slash-circle',
    ][$status] ?? 'inbox';
}

/** CSS class for a priority level. */
function priority_class(string $priority): string
{
    return [
        'Low'    => 'priority-low',
        'Medium' => 'priority-medium',
        'High'   => 'priority-high',
    ][$priority] ?? 'priority-low';
}

/** Warning level for a filament weight: '', 'warn' or 'crit'. */
function filament_level($grams): string
{
    $g = (float) $grams;
    if ($g < FILAMENT_WARN_CRIT) return 'crit';
    if ($g < FILAMENT_WARN_LOW)  return 'warn';
    return '';
}

/** Absolute URL (scheme + host + path) for links shared outside the app. */
function full_url(string $path = ''): string
{
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) == 443)
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . url($path);
}

/**
 * Normalise a phone number to an international WhatsApp number (digits only).
 * Syrian defaults: 09XXXXXXXX -> 9639XXXXXXXX, +963.. / 00963.. handled too.
 * Returns '' when nothing usable was given.
 */
function wa_number(?string $raw): string
{
    $d = preg_replace('/\D+/', '', $raw ?? '');
    if ($d === '') return '';
    if (str_starts_with($d, '00')) {
        $d = substr($d, 2);              // 00963... -> 963...
    } elseif (str_starts_with($d, '0')) {
        $d = '963' . substr($d, 1);      // 09... -> 9639...
    } elseif (!str_starts_with($d, '963')) {
        $d = '963' . $d;                 // bare local number -> add country code
    }
    return $d;
}

/**
 * Build a WhatsApp click-to-chat link from a message + optional number.
 * An empty number opens WhatsApp so the sender picks the recipient.
 * (Named wa_url to avoid clashing with the news view's wa_link().)
 */
function wa_url(string $message, ?string $number = null): string
{
    return 'https://wa.me/' . wa_number($number) . '?text=' . rawurlencode($message);
}

/** Human readable file size. */
function human_size(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $b = (float) $bytes;
    while ($b >= 1024 && $i < count($units) - 1) {
        $b /= 1024;
        $i++;
    }
    return round($b, $i ? 1 : 0) . ' ' . $units[$i];
}
