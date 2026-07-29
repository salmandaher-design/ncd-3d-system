<?php
/** @var array $request @var array $files @var array $printers @var array $filament */
$isAdmin = Auth::isAdmin();
$st = $request['status'];

// Progress stages for the timeline.
$flow = ['Submitted', 'Approved', 'Printing', 'Completed'];
$currentIndex = array_search($st, $flow, true);
$isTerminalBad = in_array($st, ['Rejected', 'Cancelled'], true);

// ----- WhatsApp status message (admin notify) -----
$waAr = [
    'Approved'  => 'تمت الموافقة على طلب الطباعة الخاص بكم',
    'Printing'  => 'طلب الطباعة الخاص بكم قيد التنفيذ الآن',
    'Completed' => 'تم إنجاز طلب الطباعة الخاص بكم، ويمكنكم استلامه',
    'Rejected'  => 'نعتذر، تم رفض طلب الطباعة الخاص بكم',
];
$waMessage = '';
if (isset($waAr[$st])) {
    $lines = [
        ($request['requester_name'] ?? '') !== '' ? 'مرحباً ' . $request['requester_name'] . '،' : 'مرحباً،',
        $waAr[$st] . ': «' . $request['project_name'] . '»',
    ];
    if (($request['transaction_no'] ?? '') !== '') $lines[] = 'رقم المعاملة: ' . $request['transaction_no'];
    if ($st === 'Rejected' && ($request['admin_notes'] ?? '') !== '') $lines[] = 'السبب: ' . $request['admin_notes'];
    $lines[] = 'لمتابعة الطلب: ' . full_url('requests/show/' . $request['id']);
    $lines[] = '— ' . APP_FULL_NAME;
    $waMessage = implode("\n", $lines);
}
?>

<div class="page-head">
    <div>
        <h2><?= e($request['project_name']) ?></h2>
        <div class="sub">
            Request #<?= $request['id'] ?> ·
            <span class="badge2 <?= status_class($st) ?>"><i class="bi bi-<?= status_icon($st) ?>"></i> <?= e($st) ?></span>
        </div>
        <?php if ($st === 'Printing'):
            $etaG = $request['actual_weight'] ?? $request['estimated_weight'] ?? 30; ?>
            <div style="margin-top:8px;">
                <span class="eta-fun" title="A very scientific estimate">
                    <i class="bi bi-hourglass-split"></i> Ready in <?= e(funny_eta($etaG, (int) $request['id'])) ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
    <div class="spacer"></div>
    <?php if ($st !== 'Submitted'): ?>
        <a class="btn2 primary" href="<?= url('requests/printForm/' . $request['id']) ?>" target="_blank">
            <i class="bi bi-printer"></i> Print request
        </a>
    <?php endif; ?>
    <a class="btn2 ghost" href="<?= url('requests') ?>"><i class="bi bi-arrow-left"></i> Back to list</a>
</div>

<div class="grid-main">
    <!-- LEFT: details -->
    <div style="display:flex; flex-direction:column; gap:18px;">
        <?php if (!empty($request['image_path'])): ?>
        <div class="card2">
            <div class="card2-body">
                <img class="thumb-lg" src="<?= url($request['image_path']) ?>" alt="Project image">
            </div>
        </div>
        <?php endif; ?>

        <div class="card2">
            <div class="card2-head"><i class="bi bi-info-circle"></i> Details</div>
            <div class="card2-body">
                <dl class="kv">
                    <dt>Team</dt><dd><?= e($request['team_name'] ?? '—') ?></dd>
                    <dt>Requested by</dt><dd><?= e($request['requester_name'] ?? '—') ?></dd>
                    <dt>Priority</dt><dd><span class="badge2 <?= priority_class($request['priority']) ?>"><?= e($request['priority']) ?></span></dd>
                    <dt>Desired color</dt>
                    <dd>
                        <?php if (!empty($request['color'])): ?>
                            <span class="pill"><span class="color-dot" style="background: <?= e(strtolower($request['color'])) ?>;"></span> <?= e($request['color']) ?></span>
                        <?php else: ?>—<?php endif; ?>
                    </dd>
                    <?php if (!empty($request['transaction_no'])): ?>
                        <dt>Transaction no.</dt><dd><?= e($request['transaction_no']) ?></dd>
                    <?php endif; ?>
                    <dt>Submitted</dt><dd><?= fmt_datetime($request['created_at']) ?></dd>
                    <dt>Last update</dt><dd><?= fmt_datetime($request['updated_at']) ?></dd>
                </dl>

                <?php if (!empty($request['description'])): ?>
                    <hr class="divider">
                    <div style="white-space:pre-wrap; color:var(--text);"><?= e($request['description']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card2">
            <div class="card2-head"><i class="bi bi-paperclip"></i> Files
                <span class="badge2 status-submitted" style="margin-left:auto;"><?= count($files) ?></span>
            </div>
            <div class="card2-body tight">
                <?php if (!$files): ?>
                    <div class="empty"><i class="bi bi-file-earmark"></i>No files were attached.</div>
                <?php else: foreach ($files as $f): ?>
                    <div class="list-row">
                        <i class="bi bi-file-earmark-zip" style="font-size:22px; color:var(--accent);"></i>
                        <div class="grow">
                            <strong><?= e($f['file_name']) ?></strong>
                            <small><?= strtoupper(e($f['file_type'])) ?> · <?= human_size((int) $f['file_size']) ?></small>
                        </div>
                        <span class="pill" title="Prints required">
                            <i class="bi bi-layers"></i> ×<?= (int) ($f['quantity'] ?? 1) ?>
                        </span>
                        <a class="btn2 sm" href="<?= url('requests/download/' . $f['id']) ?>"><i class="bi bi-download"></i> Download</a>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Discussion thread (both roles) -->
        <div class="card2" id="comments">
            <div class="card2-head"><i class="bi bi-chat-dots"></i> Discussion
                <span class="badge2 status-submitted" style="margin-left:auto;"><?= count($comments) ?></span>
            </div>
            <div class="card2-body tight">
                <?php if (!$comments): ?>
                    <div class="empty" style="padding:24px;"><i class="bi bi-chat"></i>No messages yet — start the conversation below.</div>
                <?php else: foreach ($comments as $c):
                    $mine = (int) ($c['user_id'] ?? 0) === Auth::id();
                    $adminAuthor = ($c['author_role'] ?? '') === 'admin'; ?>
                    <div class="comment <?= $mine ? 'mine' : '' ?>">
                        <span class="avatar" style="width:32px; height:32px; font-size:12px;">
                            <?= e(strtoupper(mb_substr($c['author_name'] ?? '?', 0, 1))) ?>
                        </span>
                        <div class="comment-body">
                            <div class="comment-meta">
                                <strong><?= e($c['author_name'] ?? 'Unknown') ?></strong>
                                <?php if ($adminAuthor): ?>
                                    <span class="badge2 status-approved" style="padding:1px 7px; font-size:10px;">Admin</span>
                                <?php endif; ?>
                                <span class="comment-time"><?= fmt_datetime($c['created_at']) ?></span>
                            </div>
                            <div class="comment-text" dir="auto"><?= nl2br(e($c['body'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>

                <div style="padding:14px 16px; border-top:1px solid var(--border);">
                    <form method="post" action="<?= url('requests/comment/' . $request['id']) ?>">
                        <?= Csrf::field() ?>
                        <div class="field" style="margin:0 0 10px;">
                            <textarea class="textarea" name="body" rows="2" required dir="auto"
                                      style="min-height:64px;"
                                      placeholder="Write a message to <?= $isAdmin ? 'the team' : 'the lab' ?>…"></textarea>
                        </div>
                        <button class="btn2 primary sm"><i class="bi bi-send"></i> Send message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: workflow -->
    <div style="display:flex; flex-direction:column; gap:18px;">
        <!-- Print job (plate) this request belongs to -->
        <?php if (!empty($request['job_id'])): ?>
            <div class="card2">
                <div class="card2-head"><i class="bi bi-stack"></i> Part of a print job</div>
                <div class="card2-body">
                    <div style="font-weight:650; margin-bottom:4px;"><?= e($request['job_title'] ?? ('Job #' . $request['job_id'])) ?></div>
                    <div class="hint" style="margin-bottom:10px;">
                        This part is printed together with other requests on one plate
                        (job status: <strong><?= e($request['job_status'] ?? '—') ?></strong>).
                    </div>
                    <?php if ($isAdmin): ?>
                        <a class="btn2 sm block" href="<?= url('jobs/show/' . $request['job_id']) ?>">
                            <i class="bi bi-box-arrow-up-right"></i> Open print job #<?= (int) $request['job_id'] ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($isAdmin && in_array($st, ['Submitted', 'Approved'], true)): ?>
            <!-- Merge this request with others onto one plate -->
            <div class="card2">
                <div class="card2-head"><i class="bi bi-stack"></i> Merge with other requests</div>
                <div class="card2-body">
                    <div class="hint" style="margin-bottom:10px;">
                        Print this part together with others on a single plate. Filament is then
                        deducted once for the whole plate.
                    </div>
                    <?php $openJobs = (new PrintJob())->openJobs(); ?>
                    <form method="post" action="<?= url('jobs/merge') ?>" style="display:flex; flex-direction:column; gap:10px;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="request_ids[]" value="<?= $request['id'] ?>">
                        <div class="field" style="margin:0;">
                            <label>Add to</label>
                            <select class="select" name="job_id">
                                <option value="0">➕ A new print job</option>
                                <?php foreach ($openJobs as $j): ?>
                                    <option value="<?= $j['id'] ?>">#<?= $j['id'] ?> · <?= e($j['title']) ?> (<?= (int) $j['request_count'] ?> parts)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field" style="margin:0;">
                            <label>Plate name <span class="hint" style="font-weight:400;">(new job only)</span></label>
                            <input class="input" type="text" name="title" placeholder="e.g. Black PLA plate — <?= e(date('M j')) ?>">
                        </div>
                        <button class="btn2 block"><i class="bi bi-box-arrow-in-down"></i> Merge into plate</button>
                        <div class="hint">You can add more requests from the job page or the requests list.</div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Timeline -->
        <div class="card2">
            <div class="card2-head"><i class="bi bi-diagram-3"></i> Progress</div>
            <div class="card2-body">
                <?php if ($isTerminalBad): ?>
                    <ul class="timeline">
                        <li><span class="tdot done"></span><strong>Submitted</strong><br><small class="muted"><?= fmt_date($request['created_at']) ?></small></li>
                        <li><span class="tdot done" style="border-color:var(--red); background:var(--red);"></span>
                            <strong><?= e($st) ?></strong><br><small class="muted"><?= fmt_date($request['updated_at']) ?></small></li>
                    </ul>
                <?php else: ?>
                    <ul class="timeline">
                        <?php foreach ($flow as $i => $stage):
                            $done = $currentIndex !== false && $i <= $currentIndex; ?>
                            <li>
                                <span class="tdot <?= $done ? 'done' : '' ?>"></span>
                                <strong style="<?= $done ? '' : 'color:var(--text-faint)' ?>"><?= $stage ?></strong>
                                <?php if ($i === $currentIndex): ?><br><small class="muted">Current stage · <?= fmt_date($request['updated_at']) ?></small><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Print info (visible to all) -->
        <?php if (!empty($request['printer_name']) || !empty($request['estimated_weight']) || !empty($request['actual_weight']) || !empty($request['filament_color'])): ?>
        <div class="card2">
            <div class="card2-head"><i class="bi bi-printer"></i> Print job</div>
            <div class="card2-body">
                <dl class="kv">
                    <?php if (!empty($request['printer_name'])): ?><dt>Printer</dt><dd><?= e($request['printer_name']) ?></dd><?php endif; ?>
                    <?php if (!empty($request['filament_color'])): ?><dt>Filament</dt><dd><?= e($request['filament_color']) ?></dd><?php endif; ?>
                    <?php if ($request['actual_weight'] !== null): ?><dt>Weight used</dt><dd><?= e((string) $request['actual_weight']) ?> g</dd><?php endif; ?>
                    <?php if ($request['estimated_weight'] !== null): ?><dt>Estimate</dt><dd><?= e((string) $request['estimated_weight']) ?> g</dd><?php endif; ?>
                </dl>
                <?php if (!empty($request['admin_notes'])): ?>
                    <hr class="divider">
                    <div class="hint" style="margin-bottom:4px;">Lab notes</div>
                    <div style="white-space:pre-wrap;"><?= e($request['admin_notes']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($isAdmin && $waMessage !== ''): ?>
            <!-- ADMIN: WhatsApp status ping -->
            <div class="card2">
                <div class="card2-head"><i class="bi bi-whatsapp" style="color:#25D366;"></i> Notify requester</div>
                <div class="card2-body">
                    <?php if (!empty($request['requester_phone'])): ?>
                        <div class="hint" style="margin-bottom:10px;">
                            Opens WhatsApp to <?= e($request['requester_name']) ?> · <?= e($request['requester_phone']) ?>
                        </div>
                    <?php else: ?>
                        <div class="hint" style="margin-bottom:10px;">
                            No WhatsApp number on file — WhatsApp will ask you to choose the chat.
                            Add one on the <a href="<?= url('users') ?>">Members</a> page.
                        </div>
                    <?php endif; ?>
                    <a class="btn2 block" style="background:#25D366; border-color:#25D366; color:#fff;"
                       target="_blank" rel="noopener"
                       href="<?= e(wa_url($waMessage, $request['requester_phone'] ?? null)) ?>">
                        <i class="bi bi-whatsapp"></i> Send status on WhatsApp
                    </a>
                    <div class="hint" style="margin-top:8px;">You review and tap send — nothing is sent automatically.</div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isAdmin && !empty($request['job_id'])): ?>
            <!-- This request is driven by its print job, not individually -->
            <div class="card2">
                <div class="card2-head"><i class="bi bi-lightning-charge"></i> Workflow</div>
                <div class="card2-body">
                    <div class="empty" style="padding:16px;">
                        <i class="bi bi-stack" style="color:var(--purple)"></i>
                        This request is part of a print job, so it is approved, printed and completed
                        together with the rest of the plate.
                    </div>
                    <a class="btn2 primary block" href="<?= url('jobs/show/' . $request['job_id']) ?>">
                        <i class="bi bi-box-arrow-up-right"></i> Manage print job #<?= (int) $request['job_id'] ?>
                    </a>
                </div>
            </div>

        <?php elseif ($isAdmin): ?>
            <!-- ADMIN: workflow actions -->
            <div class="card2">
                <div class="card2-head"><i class="bi bi-lightning-charge"></i> Workflow</div>
                <div class="card2-body" style="display:flex; flex-direction:column; gap:12px;">

                    <?php if ($st === 'Submitted'): ?>
                        <!-- Approve: pick a filament spool + weight; it is deducted immediately -->
                        <form method="post" action="<?= url('requests/approve/' . $request['id']) ?>" style="display:flex; flex-direction:column; gap:10px;">
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
                                <label>Weight to use (g) <span style="color:var(--red)">*</span></label>
                                <input class="input" type="number" step="0.1" min="0.1" name="weight" required placeholder="e.g. 45">
                                <div class="hint">This amount is subtracted from the chosen spool right away.</div>
                            </div>
                            <button class="btn2 success block"><i class="bi bi-check-circle"></i> Approve &amp; deduct</button>
                        </form>
                        <details>
                            <summary class="btn2 danger sm block" style="cursor:pointer;"><i class="bi bi-x-circle"></i> Reject…</summary>
                            <form method="post" action="<?= url('requests/reject/' . $request['id']) ?>" style="margin-top:10px; display:flex; flex-direction:column; gap:10px;">
                                <?= Csrf::field() ?>
                                <div class="field" style="margin:0;">
                                    <label>Reason for rejection</label>
                                    <textarea class="textarea" name="reason" placeholder="Explain why (shown on the printed form)…"></textarea>
                                </div>
                                <button class="btn2 danger block"><i class="bi bi-x-circle"></i> Confirm rejection</button>
                            </form>
                        </details>

                    <?php elseif ($st === 'Approved'): ?>
                        <form method="post" action="<?= url('requests/start/' . $request['id']) ?>" style="display:flex; flex-direction:column; gap:10px;">
                            <?= Csrf::field() ?>
                            <div class="field" style="margin:0;">
                                <label>Choose printer <span style="color:var(--red)">*</span></label>
                                <select class="select" name="printer_id" required>
                                    <option value="">Select printer…</option>
                                    <?php foreach ($printers as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= $p['status'] === 'Busy' ? 'disabled' : '' ?>>
                                            <?= e($p['name']) ?><?= $p['status'] === 'Busy' ? ' (busy)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="hint">Filament (<?= e($request['filament_color'] ?? '—') ?>, <?= e((string) ($request['actual_weight'] ?? '0')) ?> g) was set at approval.</div>
                            </div>
                            <button class="btn2 purple block"><i class="bi bi-play-circle"></i> Start printing</button>
                        </form>
                        <details>
                            <summary class="btn2 danger sm block" style="cursor:pointer;"><i class="bi bi-x-circle"></i> Reject…</summary>
                            <form method="post" action="<?= url('requests/reject/' . $request['id']) ?>" style="margin-top:10px; display:flex; flex-direction:column; gap:10px;">
                                <?= Csrf::field() ?>
                                <div class="field" style="margin:0;">
                                    <label>Reason for rejection</label>
                                    <textarea class="textarea" name="reason" placeholder="Explain why (shown on the printed form)…"></textarea>
                                </div>
                                <div class="hint">The filament committed at approval will be returned to the spool.</div>
                                <button class="btn2 danger block"><i class="bi bi-x-circle"></i> Confirm rejection</button>
                            </form>
                        </details>

                    <?php elseif ($st === 'Printing'): ?>
                        <form method="post" action="<?= url('requests/complete/' . $request['id']) ?>">
                            <?= Csrf::field() ?>
                            <div class="hint" style="margin-bottom:10px;">
                                <?= e((string) ($request['actual_weight'] ?? '0')) ?> g of
                                <?= e($request['filament_color'] ?? 'filament') ?> was already deducted at approval.
                            </div>
                            <button class="btn2 success block"><i class="bi bi-check-all"></i> Mark completed</button>
                        </form>
                        <form method="post" action="<?= url('requests/cancel/' . $request['id']) ?>" data-confirm="Cancel this print? The filament will be returned to the spool.">
                            <?= Csrf::field() ?><button class="btn2 block"><i class="bi bi-slash-circle"></i> Cancel print</button>
                        </form>

                    <?php elseif ($st === 'Completed'): ?>
                        <div class="empty" style="padding:14px;"><i class="bi bi-check-all" style="color:var(--green)"></i>This request is completed.</div>

                    <?php else: /* Rejected / Cancelled */ ?>
                        <div class="empty" style="padding:14px;"><i class="bi bi-<?= status_icon($st) ?>"></i>This request is <?= strtolower($st) ?>.</div>
                    <?php endif; ?>

                    <?php if (in_array($st, ['Submitted', 'Approved'], true)): ?>
                        <form method="post" action="<?= url('requests/cancel/' . $request['id']) ?>" data-confirm="Cancel this request?">
                            <?= Csrf::field() ?><button class="btn2 ghost sm block"><i class="bi bi-slash-circle"></i> Cancel request</button>
                        </form>
                    <?php endif; ?>

                    <hr class="divider" style="margin:4px 0;">
                    <form method="post" action="<?= url('requests/delete/' . $request['id']) ?>"
                          data-confirm="Permanently delete this request and its files? This cannot be undone.">
                        <?= Csrf::field() ?>
                        <button class="btn2 danger sm block"><i class="bi bi-trash"></i> Delete request</button>
                    </form>
                </div>
            </div>

        <?php else: /* MEMBER actions */ ?>
            <?php if ($st === 'Submitted'): ?>
            <div class="card2">
                <div class="card2-body">
                    <form method="post" action="<?= url('requests/cancel/' . $request['id']) ?>" data-confirm="Cancel your request?">
                        <?= Csrf::field() ?>
                        <button class="btn2 danger block"><i class="bi bi-slash-circle"></i> Cancel my request</button>
                    </form>
                    <div class="hint" style="margin-top:8px;">You can cancel while the request is still awaiting approval.</div>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <!-- ADMIN: notes + estimate (available whether or not it is on a plate) -->
            <div class="card2">
                <div class="card2-head"><i class="bi bi-journal-text"></i> Notes &amp; estimate</div>
                <div class="card2-body">
                    <form method="post" action="<?= url('requests/update/' . $request['id']) ?>">
                        <?= Csrf::field() ?>
                        <div class="field">
                            <label>Transaction no. <span class="hint" style="font-weight:400;" dir="rtl">رقم المعاملة</span></label>
                            <input class="input" type="text" name="transaction_no"
                                   value="<?= e((string) ($request['transaction_no'] ?? '')) ?>">
                        </div>
                        <div class="field">
                            <label>Estimated filament (g)</label>
                            <input class="input" type="number" step="0.1" min="0" name="estimated_weight"
                                   value="<?= e((string) ($request['estimated_weight'] ?? '')) ?>">
                            <div class="hint">Used to pre-fill the plate weight when this request is merged into a job.</div>
                        </div>
                        <div class="field">
                            <label>Lab notes</label>
                            <textarea class="textarea" name="admin_notes" placeholder="Internal notes about this print…"><?= e($request['admin_notes'] ?? '') ?></textarea>
                        </div>
                        <button class="btn2 primary block"><i class="bi bi-save"></i> Save details</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
