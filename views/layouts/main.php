<?php
/** @var string $content */
$pageTitle = $pageTitle ?? APP_NAME;
$user = Auth::user();
$isAdmin = Auth::isAdmin();

// Current top-level route for active nav highlighting.
$curr = explode('/', trim($_GET['url'] ?? '', '/'))[0] ?: 'dashboard';

// Notification badges.
$badgePending = 0; $badgeLowFil = 0; $badgeMine = 0;
try {
    if ($isAdmin) {
        $badgePending = (new PrintRequest())->countPending();
        $badgeLowFil  = count((new Filament())->lowStock());
    } else {
        $mine = (new PrintRequest())->forUser(Auth::id());
        foreach ($mine as $m) {
            if (in_array($m['status'], ['Submitted', 'Approved', 'Printing'], true)) $badgeMine++;
        }
    }
} catch (Throwable $e) { /* ignore on fresh installs */ }

$initials = strtoupper(mb_substr($user['name'] ?? '?', 0, 1));

if (!function_exists('navlink')) {
    function navlink(string $route, string $icon, string $label, string $curr, string $active, int $badge = 0, bool $warn = false): void
    {
        $isActive = $curr === $active ? ' active' : '';
        echo '<a class="nav-link' . $isActive . '" href="' . url($route) . '">';
        echo '<i class="bi bi-' . $icon . '"></i><span>' . e($label) . '</span>';
        if ($badge > 0) {
            echo '<span class="nav-badge' . ($warn ? ' warn' : '') . '">' . $badge . '</span>';
        }
        echo '</a>';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= Csrf::token() ?>">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;750&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <div class="sidebar-backdrop"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <span class="brand-mark"><i class="bi bi-printer-fill"></i></span>
            <span class="brand-text">
                <?= e(APP_NAME) ?>
                <small><?= e(APP_TAGLINE) ?></small>
            </span>
        </div>

        <nav class="nav-section">
            <?php if ($isAdmin): ?>
                <div class="nav-label">Overview</div>
                <?php navlink('dashboard', 'grid-1x2', 'Dashboard', $curr, 'dashboard'); ?>
                <?php navlink('requests', 'inbox', 'Requests', $curr, 'requests', $badgePending); ?>

                <?php navlink('news', 'newspaper', 'News', $curr, 'news'); ?>

                <div class="nav-label">Management</div>
                <?php navlink('teams', 'people', 'Teams', $curr, 'teams'); ?>
                <?php navlink('users', 'person-badge', 'Members', $curr, 'users'); ?>
                <?php navlink('filament', 'palette', 'Filament', $curr, 'filament', $badgeLowFil, true); ?>
                <?php navlink('printers', 'printer', 'Printers', $curr, 'printers'); ?>

                <div class="nav-label">Fun</div>
                <?php navlink('spaghetti', 'emoji-dizzy', 'Wall of Spaghetti', $curr, 'spaghetti'); ?>

                <div class="nav-label">System</div>
                <?php navlink('activity', 'clock-history', 'Activity Log', $curr, 'activity'); ?>
            <?php else: ?>
                <div class="nav-label">Menu</div>
                <?php navlink('dashboard', 'grid-1x2', 'Dashboard', $curr, 'dashboard'); ?>
                <?php navlink('requests', 'inbox', 'My Requests', $curr, 'requests', $badgeMine); ?>
                <?php navlink('requests/create', 'plus-circle', 'New Request', $curr, ''); ?>
                <?php navlink('news', 'newspaper', 'News', $curr, 'news'); ?>
                <?php navlink('printers', 'printer', 'Printers', $curr, 'printers'); ?>
                <?php navlink('spaghetti', 'emoji-dizzy', 'Wall of Spaghetti', $curr, 'spaghetti'); ?>
            <?php endif; ?>
        </nav>

        <div class="sidebar-foot">
            <div class="user-chip">
                <span class="avatar"><?= e($initials) ?></span>
                <div class="meta">
                    <strong><?= e($user['name'] ?? '') ?></strong>
                    <span><?= $isAdmin ? 'Administrator' : 'Team Member' ?></span>
                </div>
            </div>
            <a href="<?= url('auth/logout') ?>" class="btn2 ghost sm block" style="margin-top:6px;">
                <i class="bi bi-box-arrow-right"></i> Sign out
            </a>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="icon-btn mobile-only" id="sidebarToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
            <h1><?= e($pageTitle) ?></h1>
            <div class="spacer"></div>

            <?php if ($isAdmin && ($badgePending > 0 || $badgeLowFil > 0)): ?>
                <a class="icon-btn" href="<?= url('requests?status=Submitted') ?>" title="<?= $badgePending ?> pending request(s)">
                    <i class="bi bi-bell"></i>
                    <?php if ($badgePending > 0): ?><span class="dot"><?= $badgePending ?></span><?php endif; ?>
                </a>
            <?php elseif (!$isAdmin && $badgeMine > 0): ?>
                <a class="icon-btn" href="<?= url('requests') ?>" title="<?= $badgeMine ?> active request(s)">
                    <i class="bi bi-bell"></i><span class="dot"><?= $badgeMine ?></span>
                </a>
            <?php endif; ?>

            <button class="icon-btn" id="themeToggle" title="Toggle theme"><i class="bi bi-moon-stars"></i></button>
        </header>

        <main class="content">
            <?= Flash::render() ?>
            <?= $content ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
