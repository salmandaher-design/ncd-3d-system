<?php
/** @var array $jobs */
$jobStatusClass = [
    'Planned'   => 'status-submitted',
    'Approved'  => 'status-approved',
    'Printing'  => 'status-printing',
    'Completed' => 'status-completed',
    'Cancelled' => 'status-cancelled',
];
$jobStatusIcon = [
    'Planned'   => 'stack',
    'Approved'  => 'check-circle',
    'Printing'  => 'printer',
    'Completed' => 'check-all',
    'Cancelled' => 'slash-circle',
];
$open = array_filter($jobs, fn($j) => in_array($j['status'], ['Planned', 'Approved', 'Printing'], true));
?>

<div class="page-head">
    <div>
        <h2>Print Jobs</h2>
        <div class="sub">
            Group several requests onto one plate — <?= count($open) ?> open of <?= count($jobs) ?> total
        </div>
    </div>
    <div class="spacer"></div>
    <a class="btn2 primary" href="<?= url('requests') ?>">
        <i class="bi bi-plus-lg"></i> Merge requests into a job
    </a>
</div>

<div class="card2" style="margin-bottom:18px;">
    <div class="card2-body" style="display:flex; align-items:flex-start; gap:10px; font-size:13.5px; color:var(--text-muted);">
        <i class="bi bi-info-circle" style="color:var(--accent); font-size:16px;"></i>
        <div>
            A <strong>print job</strong> is one physical plate holding several requests. The job carries the
            printer, the filament and the total weight, so filament is deducted <strong>once per plate</strong>
            instead of once per request. Approving, starting or completing a job applies to every request on it.
        </div>
    </div>
</div>

<div class="card2">
    <div class="card2-body tight">
        <div class="table-wrap">
            <table class="table2">
                <thead>
                    <tr>
                        <th>Job</th><th>Requests</th><th>Filament</th><th>Weight</th>
                        <th>Printer</th><th>Status</th><th>Created</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$jobs): ?>
                    <tr><td colspan="8">
                        <div class="empty">
                            <i class="bi bi-stack"></i>
                            No print jobs yet.
                            <div style="margin-top:12px;">
                                <a class="btn2 primary" href="<?= url('requests') ?>">
                                    <i class="bi bi-plus-lg"></i> Pick requests to merge
                                </a>
                            </div>
                        </div>
                    </td></tr>
                <?php else: foreach ($jobs as $j): ?>
                    <tr onclick="location.href='<?= url('jobs/show/' . $j['id']) ?>'" style="cursor:pointer;">
                        <td><strong>#<?= $j['id'] ?></strong> · <?= e($j['title']) ?></td>
                        <td><span class="pill"><i class="bi bi-layers"></i> <?= (int) $j['request_count'] ?></span></td>
                        <td class="muted">
                            <?php if (!empty($j['filament_color'])): ?>
                                <span class="pill"><span class="color-dot" style="background: <?= e(strtolower($j['filament_color'])) ?>;"></span> <?= e($j['filament_color']) ?></span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="muted"><?= $j['total_weight'] !== null ? (float) $j['total_weight'] . ' g' : '—' ?></td>
                        <td class="muted"><?= e($j['printer_name'] ?? '—') ?></td>
                        <td>
                            <span class="badge2 <?= $jobStatusClass[$j['status']] ?? 'status-submitted' ?>">
                                <i class="bi bi-<?= $jobStatusIcon[$j['status']] ?? 'stack' ?>"></i> <?= e($j['status']) ?>
                            </span>
                        </td>
                        <td class="muted"><?= fmt_date($j['created_at']) ?></td>
                        <td><a class="btn2 sm" href="<?= url('jobs/show/' . $j['id']) ?>">Open</a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
