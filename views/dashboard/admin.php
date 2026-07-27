<?php
/** @var array $stats @var array $recent @var array $waiting @var array $printing
 *  @var array $lowFilament @var array $busyPrinters @var array $requestsByMonth @var array $filamentByMonth */

$maxReq = max(1, ...array_map(fn($r) => $r['value'], $requestsByMonth));
$maxFil = max(1, ...array_map(fn($r) => $r['value'], $filamentByMonth));
?>

<?php require __DIR__ . '/_banner.php'; ?>

<div class="stat-grid">
    <div class="stat">
        <span class="stat-icon i-blue"><i class="bi bi-inbox"></i></span>
        <div><div class="stat-num"><?= $stats['total'] ?></div><div class="stat-label">Total Requests</div></div>
    </div>
    <div class="stat">
        <span class="stat-icon i-amber"><i class="bi bi-hourglass-split"></i></span>
        <div><div class="stat-num"><?= $stats['pending'] ?></div><div class="stat-label">Pending Approval</div></div>
    </div>
    <div class="stat">
        <span class="stat-icon i-purple"><i class="bi bi-printer"></i></span>
        <div><div class="stat-num"><?= $stats['printing'] ?></div><div class="stat-label">Printing</div></div>
    </div>
    <div class="stat">
        <span class="stat-icon i-green"><i class="bi bi-check-all"></i></span>
        <div><div class="stat-num"><?= $stats['completed'] ?></div><div class="stat-label">Completed</div></div>
    </div>
    <div class="stat">
        <span class="stat-icon i-slate"><i class="bi bi-slash-circle"></i></span>
        <div><div class="stat-num"><?= $stats['cancelled'] ?></div><div class="stat-label">Cancelled</div></div>
    </div>
</div>

<!-- Charts -->
<div class="grid-2" style="margin-bottom:18px;">
    <div class="card2">
        <div class="card2-head"><i class="bi bi-bar-chart"></i> Requests by Month</div>
        <div class="card2-body">
            <div class="bars">
                <?php foreach ($requestsByMonth as $m): ?>
                    <div class="bar-col">
                        <div class="bar-track">
                            <div class="bar" style="height: <?= max(3, round($m['value'] / $maxReq * 100)) ?>%;">
                                <span class="bar-val"><?= $m['value'] ?></span>
                            </div>
                        </div>
                        <div class="bar-label"><?= e($m['label']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="card2">
        <div class="card2-head"><i class="bi bi-palette"></i> Filament Consumption (g)</div>
        <div class="card2-body">
            <div class="bars">
                <?php foreach ($filamentByMonth as $m): ?>
                    <div class="bar-col">
                        <div class="bar-track">
                            <div class="bar green" style="height: <?= max(3, round($m['value'] / $maxFil * 100)) ?>%;">
                                <span class="bar-val"><?= $m['value'] ?></span>
                            </div>
                        </div>
                        <div class="bar-label"><?= e($m['label']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="grid-main">
    <!-- Left column -->
    <div style="display:flex; flex-direction:column; gap:18px;">
        <!-- Waiting approval -->
        <div class="card2">
            <div class="card2-head">
                <i class="bi bi-hourglass-split"></i> Waiting for Approval
                <span class="badge2 status-submitted" style="margin-left:auto;"><?= count($waiting) ?></span>
            </div>
            <div class="card2-body tight">
                <?php if (!$waiting): ?>
                    <div class="empty"><i class="bi bi-check2-circle"></i>Nothing waiting. You're all caught up.</div>
                <?php else: foreach (array_slice($waiting, 0, 5) as $r): ?>
                    <div class="list-row">
                        <span class="stat-icon i-amber" style="width:38px;height:38px;font-size:16px;"><i class="bi bi-file-earmark"></i></span>
                        <div class="grow">
                            <strong><a href="<?= url('requests/show/' . $r['id']) ?>"><?= e($r['project_name']) ?></a></strong>
                            <small><?= e($r['team_name'] ?? '—') ?> · <?= fmt_date($r['created_at']) ?></small>
                        </div>
                        <span class="badge2 <?= priority_class($r['priority']) ?>"><?= e($r['priority']) ?></span>
                        <a class="btn2 sm primary" href="<?= url('requests/show/' . $r['id']) ?>">Review</a>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Currently printing -->
        <div class="card2">
            <div class="card2-head"><i class="bi bi-printer"></i> Currently Printing
                <span class="badge2 status-printing" style="margin-left:auto;"><?= count($printing) ?></span>
            </div>
            <div class="card2-body tight">
                <?php if (!$printing): ?>
                    <div class="empty"><i class="bi bi-printer"></i>No active print jobs.</div>
                <?php else: foreach ($printing as $r): ?>
                    <div class="list-row">
                        <span class="stat-icon i-purple" style="width:38px;height:38px;font-size:16px;"><i class="bi bi-printer"></i></span>
                        <div class="grow">
                            <strong><a href="<?= url('requests/show/' . $r['id']) ?>"><?= e($r['project_name']) ?></a></strong>
                            <small><?= e($r['team_name'] ?? '—') ?> · <?= e($r['printer_name'] ?? 'Printer') ?></small>
                        </div>
                        <a class="btn2 sm" href="<?= url('requests/show/' . $r['id']) ?>">Open</a>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Recent requests -->
        <div class="card2">
            <div class="card2-head"><i class="bi bi-clock-history"></i> Recent Requests
                <a href="<?= url('requests') ?>" class="btn2 sm ghost" style="margin-left:auto;">View all</a>
            </div>
            <div class="card2-body tight">
                <div class="table-wrap">
                    <table class="table2">
                        <thead><tr><th>Project</th><th>Team</th><th>Priority</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php if (!$recent): ?>
                            <tr><td colspan="5"><div class="empty"><i class="bi bi-inbox"></i>No requests yet.</div></td></tr>
                        <?php else: foreach ($recent as $r): ?>
                            <tr onclick="location.href='<?= url('requests/show/' . $r['id']) ?>'" style="cursor:pointer;">
                                <td><strong><?= e($r['project_name']) ?></strong></td>
                                <td class="muted"><?= e($r['team_name'] ?? '—') ?></td>
                                <td><span class="badge2 <?= priority_class($r['priority']) ?>"><?= e($r['priority']) ?></span></td>
                                <td><span class="badge2 <?= status_class($r['status']) ?>"><i class="bi bi-<?= status_icon($r['status']) ?>"></i> <?= e($r['status']) ?></span></td>
                                <td class="muted"><?= fmt_date($r['created_at']) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right column -->
    <div style="display:flex; flex-direction:column; gap:18px;">
        <!-- Low filament -->
        <div class="card2">
            <div class="card2-head"><i class="bi bi-exclamation-triangle"></i> Low Filament</div>
            <div class="card2-body tight">
                <?php if (!$lowFilament): ?>
                    <div class="empty"><i class="bi bi-check2-circle"></i>All spools are healthy.</div>
                <?php else: foreach ($lowFilament as $f):
                    $lvl = filament_level($f['remaining_weight']); ?>
                    <div class="list-row">
                        <span class="color-dot" style="background: <?= e(strtolower($f['color'])) ?>;"></span>
                        <div class="grow">
                            <strong><?= e($f['color']) ?></strong>
                            <small><?= e($f['material']) ?></small>
                        </div>
                        <span class="badge2 <?= $lvl === 'crit' ? 'status-rejected' : 'priority-medium' ?>">
                            <?= (int) $f['remaining_weight'] ?> g
                        </span>
                    </div>
                <?php endforeach; endif; ?>
                <div style="padding:12px 16px;">
                    <a href="<?= url('filament') ?>" class="btn2 sm block"><i class="bi bi-palette"></i> Manage inventory</a>
                </div>
            </div>
        </div>

        <!-- Printers -->
        <div class="card2">
            <div class="card2-head"><i class="bi bi-printer"></i> Printers</div>
            <div class="card2-body tight">
                <?php if (!$busyPrinters): ?>
                    <div class="empty"><i class="bi bi-printer"></i>Both printers are idle.</div>
                <?php else: foreach ($busyPrinters as $p): ?>
                    <div class="list-row">
                        <span class="stat-icon i-purple" style="width:38px;height:38px;font-size:16px;"><i class="bi bi-printer-fill"></i></span>
                        <div class="grow">
                            <strong><?= e($p['name']) ?></strong>
                            <small><?= e($p['current_project'] ?? '') ?> · <?= e($p['current_team'] ?? '') ?></small>
                        </div>
                        <span class="badge2 status-printing">Busy</span>
                    </div>
                <?php endforeach; endif; ?>
                <div style="padding:12px 16px;">
                    <a href="<?= url('printers') ?>" class="btn2 sm block"><i class="bi bi-printer"></i> View printers</a>
                </div>
            </div>
        </div>
    </div>
</div>
