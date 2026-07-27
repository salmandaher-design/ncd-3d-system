<?php /** @var array $requests @var array $filters @var array $teams */
$isAdmin = Auth::isAdmin();
?>

<div class="page-head">
    <div>
        <h2><?= $isAdmin ? 'Print Requests' : 'My Requests' ?></h2>
        <div class="sub"><?= count($requests) ?> request<?= count($requests) === 1 ? '' : 's' ?> found</div>
    </div>
    <div class="spacer"></div>
    <?php if ($requests):
        // Carry the active filters over to the printable document.
        // (user_id is enforced server-side for members, no need to pass it.)
        $printFilters = array_diff_key($filters, ['user_id' => true]);
        $qs = http_build_query(array_filter($printFilters, fn($v) => $v !== '' && $v !== null));
    ?>
        <a class="btn2" href="<?= url('requests/printAll' . ($qs ? '?' . $qs : '')) ?>" target="_blank"
           title="Print these requests — one page each">
            <i class="bi bi-printer"></i> <?= $isAdmin ? 'Print list' : 'Print all my requests' ?>
        </a>
    <?php endif; ?>
    <a class="btn2 primary" href="<?= url('requests/create') ?>"><i class="bi bi-plus-lg"></i> New Request</a>
</div>

<!-- Search & filters -->
<div class="card2" style="margin-bottom:18px;">
    <div class="card2-body">
        <form method="get" action="<?= url('requests') ?>">
            <div class="form-row" style="align-items:end;">
                <div class="field" style="margin:0; grid-column: span 2;">
                    <label>Search</label>
                    <input class="input" type="text" name="q" value="<?= e($filters['q']) ?>"
                           placeholder="Project<?= $isAdmin ? ', team or requester' : '' ?>…">
                </div>
                <div class="field" style="margin:0;">
                    <label>Status</label>
                    <select class="select" name="status" data-autosubmit>
                        <option value="">All statuses</option>
                        <?php foreach (PrintRequest::STATUSES as $s): ?>
                            <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field" style="margin:0;">
                    <label>Priority</label>
                    <select class="select" name="priority" data-autosubmit>
                        <option value="">All priorities</option>
                        <?php foreach (PrintRequest::PRIORITIES as $p): ?>
                            <option value="<?= $p ?>" <?= $filters['priority'] === $p ? 'selected' : '' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($isAdmin): ?>
                <div class="field" style="margin:0;">
                    <label>Team</label>
                    <select class="select" name="team_id" data-autosubmit>
                        <option value="">All teams</option>
                        <?php foreach ($teams as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= (string) $filters['team_id'] === (string) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="field" style="margin:0; display:flex; gap:8px;">
                    <button type="submit" class="btn2 primary"><i class="bi bi-search"></i> Search</button>
                    <a href="<?= url('requests') ?>" class="btn2" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card2">
    <div class="card2-body tight">
        <div class="table-wrap">
            <table class="table2">
                <thead>
                    <tr>
                        <th>Project</th>
                        <?php if ($isAdmin): ?><th>Team</th><th>Requester</th><?php endif; ?>
                        <th>Priority</th>
                        <th>Color</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$requests): ?>
                    <tr><td colspan="8"><div class="empty"><i class="bi bi-search"></i>No requests match your filters.</div></td></tr>
                <?php else: foreach ($requests as $r): ?>
                    <tr onclick="location.href='<?= url('requests/show/' . $r['id']) ?>'" style="cursor:pointer;">
                        <td><strong>#<?= $r['id'] ?></strong> · <?= e($r['project_name']) ?></td>
                        <?php if ($isAdmin): ?>
                            <td class="muted"><?= e($r['team_name'] ?? '—') ?></td>
                            <td class="muted"><?= e($r['requester_name'] ?? '—') ?></td>
                        <?php endif; ?>
                        <td><span class="badge2 <?= priority_class($r['priority']) ?>"><?= e($r['priority']) ?></span></td>
                        <td>
                            <?php if (!empty($r['color'])): ?>
                                <span class="pill"><span class="color-dot" style="background: <?= e(strtolower($r['color'])) ?>;"></span> <?= e($r['color']) ?></span>
                            <?php else: ?><span class="muted">—</span><?php endif; ?>
                        </td>
                        <td><span class="badge2 <?= status_class($r['status']) ?>"><i class="bi bi-<?= status_icon($r['status']) ?>"></i> <?= e($r['status']) ?></span></td>
                        <td class="muted"><?= fmt_date($r['created_at']) ?></td>
                        <td><a class="btn2 sm" href="<?= url('requests/show/' . $r['id']) ?>">Open</a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
