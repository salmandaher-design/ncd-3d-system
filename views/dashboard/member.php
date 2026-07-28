<?php /** @var array $requests @var array $counts @var array|null $banner */ ?>

<?php require __DIR__ . '/_banner.php'; ?>

<div class="page-head">
    <div>
        <h2>Welcome back, <?= e(explode(' ', Auth::name())[0]) ?> 👋</h2>
        <div class="sub">Here are your 3D print requests and their current status.</div>
    </div>
    <div class="spacer"></div>
    <?php if ($counts['total'] > 0): ?>
        <a class="btn2" href="<?= url('requests/printAll') ?>" target="_blank"
           title="Print all your team's answered requests — one page each">
            <i class="bi bi-printer"></i> Print all team requests
        </a>
    <?php endif; ?>
    <a class="btn2 primary" href="<?= url('requests/create') ?>"><i class="bi bi-plus-lg"></i> New Request</a>
</div>

<div class="stat-grid">
    <div class="stat">
        <span class="stat-icon i-blue"><i class="bi bi-collection"></i></span>
        <div><div class="stat-num"><?= $counts['total'] ?></div><div class="stat-label">Total Requests</div></div>
    </div>
    <div class="stat">
        <span class="stat-icon i-amber"><i class="bi bi-hourglass-split"></i></span>
        <div><div class="stat-num"><?= $counts['active'] ?></div><div class="stat-label">In Progress</div></div>
    </div>
    <div class="stat">
        <span class="stat-icon i-green"><i class="bi bi-check-all"></i></span>
        <div><div class="stat-num"><?= $counts['completed'] ?></div><div class="stat-label">Completed</div></div>
    </div>
</div>

<div class="card2">
    <div class="card2-head"><i class="bi bi-inbox"></i> My Requests</div>
    <div class="card2-body tight">
        <div class="table-wrap">
            <table class="table2">
                <thead>
                    <tr><th>Project</th><th>Priority</th><th>Submitted</th><th>Current Stage</th><th></th></tr>
                </thead>
                <tbody>
                <?php if (!$requests): ?>
                    <tr><td colspan="5">
                        <div class="empty">
                            <i class="bi bi-inbox"></i>
                            You haven't submitted any requests yet.
                            <div style="margin-top:12px;"><a class="btn2 primary" href="<?= url('requests/create') ?>"><i class="bi bi-plus-lg"></i> Create your first request</a></div>
                        </div>
                    </td></tr>
                <?php else: foreach ($requests as $r): ?>
                    <tr onclick="location.href='<?= url('requests/show/' . $r['id']) ?>'" style="cursor:pointer;">
                        <td>
                            <strong><?= e($r['project_name']) ?></strong>
                            <?php if (!empty($r['color'])): ?>
                                <div><span class="pill"><span class="color-dot" style="background: <?= e(strtolower($r['color'])) ?>;"></span> <?= e($r['color']) ?></span></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge2 <?= priority_class($r['priority']) ?>"><?= e($r['priority']) ?></span></td>
                        <td class="muted"><?= fmt_date($r['created_at']) ?></td>
                        <td><span class="badge2 <?= status_class($r['status']) ?>"><i class="bi bi-<?= status_icon($r['status']) ?>"></i> <?= e($r['status']) ?></span></td>
                        <td><a class="btn2 sm" href="<?= url('requests/show/' . $r['id']) ?>">Details</a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
