<?php
/** @var array $job @var array $requests @var array $byRequester @var array $candidates
 *  @var array $printers @var array $filament @var float $estimated */
$st = $job['status'];
$jobStatusClass = [
    'Planned' => 'status-submitted', 'Approved' => 'status-approved', 'Printing' => 'status-printing',
    'Completed' => 'status-completed', 'Cancelled' => 'status-cancelled',
];
$flow = ['Planned', 'Approved', 'Printing', 'Completed'];
$currentIndex = array_search($st, $flow, true);
$isCancelled = $st === 'Cancelled';

// Warn when the plate mixes colours or teams — useful before printing.
$colors = array_values(array_unique(array_filter(array_map(
    fn($r) => trim((string) ($r['color'] ?? '')), $requests
))));
$teams = array_values(array_unique(array_filter(array_column($requests, 'team_name'))));

/**
 * Build ONE Arabic WhatsApp message per member, listing every part of theirs
 * on this plate — so the admin sends one message per person, not per request.
 */
$statusLine = [
    'Planned'   => 'تم دمج طلباتكم التالية في لوحة طباعة واحدة، وسيتم تنفيذها معاً',
    'Approved'  => 'تمت الموافقة على طلباتكم التالية وتم دمجها في لوحة طباعة واحدة',
    'Printing'  => 'طلباتكم التالية قيد الطباعة الآن ضمن لوحة واحدة',
    'Completed' => 'تم إنجاز طلباتكم التالية، ويمكنكم استلامها',
    'Cancelled' => 'نعتذر، تم إلغاء لوحة الطباعة التي تضم طلباتكم التالية',
][$st] ?? 'تحديث بخصوص طلباتكم التالية';

$notifyMessage = function (array $g) use ($job, $statusLine): string {
    $lines = ['مرحباً ' . $g['name'] . '،', $statusLine . ':', ''];
    foreach ($g['requests'] as $r) {
        $line = '• ' . $r['project_name'];
        if (($r['transaction_no'] ?? '') !== '') {
            $line .= ' (' . $r['transaction_no'] . ')';
        }
        $lines[] = $line;
    }
    $lines[] = '';
    $lines[] = 'اسم اللوحة: ' . $job['title'];
    if (!empty($job['filament_color'])) {
        $lines[] = 'الفيلامنت: ' . $job['filament_color'];
    }
    $lines[] = 'لمتابعة طلباتكم: ' . full_url('requests');
    $lines[] = '— ' . APP_FULL_NAME . ' · مخبر الروبوت والذكاء الصنعي';
    return implode("\n", $lines);
};
?>

<div class="page-head">
    <div>
        <h2><?= e($job['title']) ?></h2>
        <div class="sub">
            Job #<?= $job['id'] ?> ·
            <span class="badge2 <?= $jobStatusClass[$st] ?? 'status-submitted' ?>"><?= e($st) ?></span>
            · <?= count($requests) ?> request<?= count($requests) === 1 ? '' : 's' ?> on this plate
        </div>
    </div>
    <div class="spacer"></div>
    <?php if ($requests): ?>
        <a class="btn2" target="_blank" href="<?= url('requests/printAll?job_id=' . $job['id']) ?>">
            <i class="bi bi-printer"></i> Print all forms
        </a>
    <?php endif; ?>
    <a class="btn2 ghost" href="<?= url('jobs') ?>"><i class="bi bi-arrow-left"></i> All jobs</a>
</div>

<div class="grid-main">
    <!-- LEFT -->
    <div style="display:flex; flex-direction:column; gap:18px;">

        <?php if (count($colors) > 1): ?>
            <div class="alert alert-warning d-flex align-items-center" style="border-radius:12px; margin:0;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div>
                    This plate mixes <strong><?= count($colors) ?> colours</strong>
                    (<?= e(implode(', ', $colors)) ?>). Make sure the printer can handle that,
                    or split the plate.
                </div>
            </div>
        <?php endif; ?>

        <!-- Requests on the plate -->
        <div class="card2">
            <div class="card2-head">
                <i class="bi bi-layers"></i> Requests on this plate
                <span class="badge2 status-submitted" style="margin-left:auto;"><?= count($requests) ?></span>
            </div>
            <div class="card2-body tight">
                <?php if (!$requests): ?>
                    <div class="empty"><i class="bi bi-inbox"></i>No requests yet — add some from the panel on the right.</div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="table2">
                            <thead><tr><th>Request</th><th>Team</th><th>Colour</th><th>Priority</th><th>Est.</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($requests as $r): ?>
                                <tr>
                                    <td>
                                        <a href="<?= url('requests/show/' . $r['id']) ?>">
                                            <strong>#<?= $r['id'] ?></strong> · <?= e($r['project_name']) ?>
                                        </a>
                                    </td>
                                    <td class="muted"><?= e($r['team_name'] ?? '—') ?></td>
                                    <td>
                                        <?php if (!empty($r['color'])): ?>
                                            <span class="pill"><span class="color-dot" style="background: <?= e(strtolower($r['color'])) ?>;"></span> <?= e($r['color']) ?></span>
                                        <?php else: ?><span class="muted">—</span><?php endif; ?>
                                    </td>
                                    <td><span class="badge2 <?= priority_class($r['priority']) ?>"><?= e($r['priority']) ?></span></td>
                                    <td class="muted"><?= $r['estimated_weight'] !== null ? (float) $r['estimated_weight'] . ' g' : '—' ?></td>
                                    <td style="text-align:right;">
                                        <?php if ($st === 'Planned'): ?>
                                            <form method="post" action="<?= url('jobs/remove/' . $job['id'] . '/' . $r['id']) ?>"
                                                  data-confirm="Remove #<?= $r['id'] ?> from this plate?">
                                                <?= Csrf::field() ?>
                                                <button class="btn2 sm danger" title="Remove from plate"><i class="bi bi-x-lg"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add more requests (only while planning / approved) -->
        <?php if (in_array($st, ['Planned', 'Approved'], true)): ?>
        <div class="card2">
            <div class="card2-head"><i class="bi bi-plus-square"></i> Add requests to this plate</div>
            <div class="card2-body">
                <?php if (!$candidates): ?>
                    <div class="empty" style="padding:20px;">
                        <i class="bi bi-check2-circle"></i>No free requests available to merge right now.
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= url('jobs/merge') ?>">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                        <div class="pick-list">
                            <?php foreach ($candidates as $c): ?>
                                <label class="pick-row">
                                    <input type="checkbox" name="request_ids[]" value="<?= $c['id'] ?>" data-pick>
                                    <span class="grow">
                                        <strong>#<?= $c['id'] ?> · <?= e($c['project_name']) ?></strong>
                                        <small><?= e($c['team_name'] ?? '—') ?> · <?= e($c['status']) ?></small>
                                    </span>
                                    <?php if (!empty($c['color'])): ?>
                                        <span class="pill"><span class="color-dot" style="background: <?= e(strtolower($c['color'])) ?>;"></span> <?= e($c['color']) ?></span>
                                    <?php endif; ?>
                                    <span class="badge2 <?= priority_class($c['priority']) ?>"><?= e($c['priority']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn2 primary" style="margin-top:12px;">
                            <i class="bi bi-box-arrow-in-down"></i> Add selected to plate
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT -->
    <div style="display:flex; flex-direction:column; gap:18px;">
        <!-- Progress -->
        <div class="card2">
            <div class="card2-head"><i class="bi bi-diagram-3"></i> Progress</div>
            <div class="card2-body">
                <?php if ($isCancelled): ?>
                    <ul class="timeline">
                        <li><span class="tdot done"></span><strong>Planned</strong></li>
                        <li><span class="tdot done" style="border-color:var(--red); background:var(--red);"></span>
                            <strong>Cancelled</strong><br><small class="muted"><?= fmt_date($job['updated_at']) ?></small></li>
                    </ul>
                <?php else: ?>
                    <ul class="timeline">
                        <?php foreach ($flow as $i => $stage): $done = $currentIndex !== false && $i <= $currentIndex; ?>
                            <li>
                                <span class="tdot <?= $done ? 'done' : '' ?>"></span>
                                <strong style="<?= $done ? '' : 'color:var(--text-faint)' ?>"><?= $stage ?></strong>
                                <?php if ($i === $currentIndex): ?><br><small class="muted">Current · <?= fmt_date($job['updated_at']) ?></small><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Plate summary -->
        <div class="card2">
            <div class="card2-head"><i class="bi bi-clipboard-data"></i> Plate summary</div>
            <div class="card2-body">
                <dl class="kv">
                    <dt>Requests</dt><dd><?= count($requests) ?></dd>
                    <dt>Teams</dt><dd><?= $teams ? e(implode(', ', $teams)) : '—' ?></dd>
                    <dt>Colours</dt><dd><?= $colors ? e(implode(', ', $colors)) : '—' ?></dd>
                    <dt>Sum of estimates</dt><dd><?= $estimated > 0 ? round($estimated, 1) . ' g' : '—' ?></dd>
                    <?php if (!empty($job['filament_color'])): ?>
                        <dt>Filament</dt><dd><?= e($job['filament_color']) ?></dd>
                    <?php endif; ?>
                    <?php if ($job['total_weight'] !== null): ?>
                        <dt>Plate weight</dt><dd><strong><?= (float) $job['total_weight'] ?> g</strong></dd>
                    <?php endif; ?>
                    <?php if (!empty($job['printer_name'])): ?>
                        <dt>Printer</dt><dd><?= e($job['printer_name']) ?></dd>
                    <?php endif; ?>
                    <dt>Created by</dt><dd><?= e($job['creator_name'] ?? '—') ?></dd>
                </dl>
            </div>
        </div>

        <!-- Notify everyone on the plate: one message per member -->
        <?php if ($byRequester): ?>
        <div class="card2">
            <div class="card2-head">
                <i class="bi bi-whatsapp" style="color:#25D366;"></i> Notify requesters
                <span class="badge2 status-submitted" style="margin-left:auto;"><?= count($byRequester) ?></span>
            </div>
            <div class="card2-body" style="display:flex; flex-direction:column; gap:10px;">
                <div class="hint">
                    One message per member covering <strong>all</strong> of their parts on this plate —
                    <?= count($requests) ?> request<?= count($requests) === 1 ? '' : 's' ?> →
                    <?= count($byRequester) ?> message<?= count($byRequester) === 1 ? '' : 's' ?>.
                </div>
                <?php foreach ($byRequester as $g): ?>
                    <div class="notify-row">
                        <div class="grow">
                            <strong><?= e($g['name']) ?></strong>
                            <small>
                                <?= count($g['requests']) ?> part<?= count($g['requests']) === 1 ? '' : 's' ?>
                                <?php if (!empty($g['phone'])): ?>
                                    · <?= e($g['phone']) ?>
                                <?php else: ?>
                                    · <span style="color:var(--amber)">no number on file</span>
                                <?php endif; ?>
                            </small>
                        </div>
                        <a class="btn2 sm" style="background:#25D366; border-color:#25D366; color:#fff;"
                           target="_blank" rel="noopener"
                           href="<?= e(wa_url($notifyMessage($g), $g['phone'] ?? null)) ?>">
                            <i class="bi bi-whatsapp"></i> Send
                        </a>
                    </div>
                <?php endforeach; ?>
                <?php $apiReady = WhatsAppApi::isConfigured();
                      $withPhone = count(array_filter($byRequester, fn($g) => !empty($g['phone']))); ?>
                <hr class="divider" style="margin:4px 0;">
                <?php if ($apiReady): ?>
                    <form method="post" action="<?= url('jobs/notifyAll/' . $job['id']) ?>"
                          data-confirm="Send <?= $withPhone ?> WhatsApp message(s) now, one per member?">
                        <?= Csrf::field() ?>
                        <button class="btn2 primary block" <?= $withPhone ? '' : 'disabled' ?>>
                            <i class="bi bi-send-fill"></i> Send all automatically (<?= $withPhone ?>)
                        </button>
                    </form>
                    <div class="hint">Sends straight through the API — no need to open WhatsApp.</div>
                <?php else: ?>
                    <div class="hint" style="display:flex; gap:6px;">
                        <i class="bi bi-info-circle" style="color:var(--amber);"></i>
                        <span>
                            <strong>Automatic sending is off.</strong>
                            <?= e(WhatsAppApi::unavailableReason()) ?>
                            The green buttons above always work.
                        </span>
                    </div>
                <?php endif; ?>
                <div class="hint">
                    The green buttons open WhatsApp with the message ready — you review and tap send.
                    Add missing numbers on the <a href="<?= url('users') ?>">Members</a> page.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Workflow -->
        <div class="card2">
            <div class="card2-head"><i class="bi bi-lightning-charge"></i> Workflow</div>
            <div class="card2-body" style="display:flex; flex-direction:column; gap:12px;">
                <?php if ($st === 'Planned'): ?>
                    <form method="post" action="<?= url('jobs/approve/' . $job['id']) ?>" style="display:flex; flex-direction:column; gap:10px;">
                        <?= Csrf::field() ?>
                        <div class="field" style="margin:0;">
                            <label>Filament spool <span style="color:var(--red)">*</span></label>
                            <select class="select" name="filament_id" required>
                                <option value="">Select filament…</option>
                                <?php foreach ($filament as $f): ?>
                                    <option value="<?= $f['id'] ?>"><?= e($f['color']) ?> · <?= e($f['material']) ?> (<?= (int) $f['remaining_weight'] ?> g left)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field" style="margin:0;">
                            <label>Total plate weight (g) <span style="color:var(--red)">*</span></label>
                            <input class="input" type="number" step="0.1" min="0.1" name="total_weight" required
                                   value="<?= $estimated > 0 ? round($estimated, 1) : '' ?>" placeholder="e.g. 120">
                            <div class="hint">Deducted <strong>once</strong> for the whole plate. Pre-filled with the sum of the estimates.</div>
                        </div>
                        <button class="btn2 success block" <?= $requests ? '' : 'disabled' ?>>
                            <i class="bi bi-check-circle"></i> Approve plate &amp; deduct
                        </button>
                        <?php if (!$requests): ?>
                            <div class="hint">Add at least one request first.</div>
                        <?php endif; ?>
                    </form>

                <?php elseif ($st === 'Approved'): ?>
                    <form method="post" action="<?= url('jobs/start/' . $job['id']) ?>" style="display:flex; flex-direction:column; gap:10px;">
                        <?= Csrf::field() ?>
                        <div class="field" style="margin:0;">
                            <label>Printer <span style="color:var(--red)">*</span></label>
                            <select class="select" name="printer_id" required>
                                <option value="">Select printer…</option>
                                <?php foreach ($printers as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $p['status'] === 'Busy' ? 'disabled' : '' ?>>
                                        <?= e($p['name']) ?><?= $p['status'] === 'Busy' ? ' (busy)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn2 purple block"><i class="bi bi-play-circle"></i> Start printing plate</button>
                    </form>

                <?php elseif ($st === 'Printing'): ?>
                    <form method="post" action="<?= url('jobs/complete/' . $job['id']) ?>">
                        <?= Csrf::field() ?>
                        <div class="hint" style="margin-bottom:10px;">
                            <?= (float) ($job['total_weight'] ?? 0) ?> g of <?= e($job['filament_color'] ?? 'filament') ?>
                            was deducted when the plate was approved.
                        </div>
                        <button class="btn2 success block"><i class="bi bi-check-all"></i> Mark plate completed</button>
                    </form>

                <?php elseif ($st === 'Completed'): ?>
                    <div class="empty" style="padding:14px;"><i class="bi bi-check-all" style="color:var(--green)"></i>This plate is completed.</div>
                <?php else: ?>
                    <div class="empty" style="padding:14px;"><i class="bi bi-slash-circle"></i>This plate was cancelled.</div>
                <?php endif; ?>

                <?php if (!in_array($st, ['Completed', 'Cancelled'], true)): ?>
                    <form method="post" action="<?= url('jobs/cancel/' . $job['id']) ?>"
                          data-confirm="Cancel this plate? Filament will be returned and all its requests become Cancelled.">
                        <?= Csrf::field() ?>
                        <button class="btn2 block"><i class="bi bi-slash-circle"></i> Cancel plate</button>
                    </form>
                <?php endif; ?>

                <hr class="divider" style="margin:4px 0;">
                <form method="post" action="<?= url('jobs/delete/' . $job['id']) ?>"
                      data-confirm="Delete this job? The requests on it are released, not deleted.">
                    <?= Csrf::field() ?>
                    <button class="btn2 danger sm block"><i class="bi bi-trash"></i> Delete job</button>
                </form>
            </div>
        </div>

        <!-- Title & notes -->
        <div class="card2">
            <div class="card2-head"><i class="bi bi-journal-text"></i> Job details</div>
            <div class="card2-body">
                <form method="post" action="<?= url('jobs/update/' . $job['id']) ?>">
                    <?= Csrf::field() ?>
                    <div class="field">
                        <label>Title</label>
                        <input class="input" type="text" name="title" value="<?= e($job['title']) ?>" required maxlength="160">
                    </div>
                    <div class="field">
                        <label>Notes</label>
                        <textarea class="textarea" name="notes" placeholder="Plate layout, orientation, supports…"><?= e($job['notes'] ?? '') ?></textarea>
                    </div>
                    <button class="btn2 primary block"><i class="bi bi-save"></i> Save details</button>
                </form>
            </div>
        </div>
    </div>
</div>
