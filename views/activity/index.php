<?php /** @var array $logs */
$icons = [
    'login' => 'box-arrow-in-right', 'logout' => 'box-arrow-right',
    'request_create' => 'plus-circle', 'request_approve' => 'check-circle',
    'request_reject' => 'x-circle', 'request_print' => 'printer',
    'request_complete' => 'check-all', 'request_cancel' => 'slash-circle',
    'request_delete' => 'trash', 'request_update' => 'pencil',
    'team_create' => 'people', 'team_update' => 'people', 'team_delete' => 'trash',
    'user_create' => 'person-plus', 'user_update' => 'person', 'user_delete' => 'person-x',
    'filament_create' => 'palette', 'filament_update' => 'palette', 'filament_delete' => 'trash',
    'printer_update' => 'printer', 'printer_free' => 'stop-circle',
    'news_publish' => 'megaphone', 'news_update' => 'pencil-square', 'news_delete' => 'trash',
    'request_comment' => 'chat-dots',
    'spaghetti_post' => 'emoji-dizzy',
];
?>

<div class="page-head">
    <div>
        <h2>Activity Log</h2>
        <div class="sub">Recent actions across the system</div>
    </div>
</div>

<div class="card2">
    <div class="card2-body tight">
        <?php if (!$logs): ?>
            <div class="empty"><i class="bi bi-clock-history"></i>No activity recorded yet.</div>
        <?php else: foreach ($logs as $log):
            $icon = $icons[$log['action']] ?? 'dot'; ?>
            <div class="list-row">
                <span class="stat-icon i-slate" style="width:36px;height:36px;font-size:15px;"><i class="bi bi-<?= $icon ?>"></i></span>
                <div class="grow">
                    <strong><?= e($log['description'] ?: $log['action']) ?></strong>
                    <small><?= e($log['user_name'] ?? 'System') ?> · <?= fmt_datetime($log['created_at']) ?></small>
                </div>
                <span class="pill"><?= e($log['action']) ?></span>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
