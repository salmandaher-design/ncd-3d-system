<?php
/** @var array $fails @var float $totalGrams @var array|null $champion @var array $respected */
$isAdmin = Auth::isAdmin();
?>

<div class="page-head">
    <div>
        <h2>🍝 Wall of Spaghetti</h2>
        <div class="sub">A loving memorial to prints that gave their lives so we could learn.</div>
    </div>
    <div class="spacer"></div>
    <button class="btn2 primary" data-modal="#failModal"><i class="bi bi-plus-lg"></i> Add a fail</button>
</div>

<!-- Memorial stats -->
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
    <div class="stat">
        <span class="stat-icon i-red"><i class="bi bi-emoji-dizzy"></i></span>
        <div><div class="stat-num"><?= count($fails) ?></div><div class="stat-label">Glorious failures</div></div>
    </div>
    <div class="stat">
        <span class="stat-icon i-amber"><i class="bi bi-fire"></i></span>
        <div>
            <div class="stat-num"><?= number_format($totalGrams) ?> g</div>
            <div class="stat-label">Filament sacrificed ⚰️</div>
        </div>
    </div>
    <div class="stat">
        <span class="stat-icon i-purple"><i class="bi bi-trophy"></i></span>
        <div>
            <div class="stat-num" style="font-size:18px;"><?= $champion ? e($champion['team_name']) : '—' ?></div>
            <div class="stat-label">🏆 Spaghetti Champion (this month)</div>
        </div>
    </div>
</div>

<?php if ($champion): ?>
    <div class="alert alert-warning d-flex align-items-center" style="border-radius:12px;">
        <i class="bi bi-trophy-fill me-2"></i>
        <div>
            This month's <strong>Spaghetti Champion</strong> is <strong><?= e($champion['team_name']) ?></strong>
            with <strong><?= (int) $champion['fails'] ?></strong> fail(s) and
            <strong><?= number_format((float) $champion['grams']) ?> g</strong> heroically wasted. 👏
        </div>
    </div>
<?php endif; ?>

<!-- The wall -->
<?php if (!$fails): ?>
    <div class="card2"><div class="card2-body">
        <div class="empty"><i class="bi bi-emoji-smile"></i>
            No failures yet — suspicious. Either you're all geniuses, or someone's hiding the evidence.
        </div>
    </div></div>
<?php else: ?>
    <div class="spaghetti-grid">
        <?php foreach ($fails as $f):
            $canDelete = $isAdmin || (int) ($f['user_id'] ?? 0) === Auth::id();
            $didRespect = !empty($respected[$f['id']]);
        ?>
            <div class="fail-card">
                <?php if (!empty($f['image_path'])): ?>
                    <img class="fail-img" src="<?= url($f['image_path']) ?>" alt="a beautiful disaster">
                <?php else: ?>
                    <div class="fail-img fail-noimg">🍝</div>
                <?php endif; ?>
                <div class="fail-body">
                    <p class="fail-caption" dir="auto"><?= e($f['caption']) ?></p>
                    <div class="fail-meta">
                        <span><i class="bi bi-people"></i> <?= e($f['team_name'] ?? '—') ?></span>
                        <span><i class="bi bi-person"></i> <?= e($f['author_name'] ?? 'Unknown') ?></span>
                        <?php if (!empty($f['printer_name'])): ?><span><i class="bi bi-printer"></i> <?= e($f['printer_name']) ?></span><?php endif; ?>
                        <span><i class="bi bi-fire"></i> <?= number_format((float) $f['grams']) ?> g</span>
                        <span><i class="bi bi-calendar3"></i> <?= fmt_date($f['created_at']) ?></span>
                    </div>
                    <div class="fail-actions">
                        <button class="btn2 sm respect-btn <?= $didRespect ? 'done' : '' ?>"
                                data-respect-url="<?= url('spaghetti/respect/' . $f['id']) ?>" <?= $didRespect ? 'disabled' : '' ?>>
                            <i class="bi bi-emoji-frown"></i> Press F
                            <span class="rcount"><?= (int) $f['respects'] ?></span>
                        </button>
                        <?php if ($canDelete): ?>
                            <form method="post" action="<?= url('spaghetti/delete/' . $f['id']) ?>" style="margin-left:auto;"
                                  data-confirm="Delete this fail from history?">
                                <?= Csrf::field() ?>
                                <button class="btn2 sm danger"><i class="bi bi-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Add-a-fail modal -->
<div class="modal2-backdrop" id="failModal">
    <div class="modal2">
        <form method="post" action="<?= url('spaghetti/store') ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <div class="modal2-head"><h3>🍝 Immortalise a fail</h3>
                <button type="button" class="btn-x" data-close>&times;</button></div>
            <div class="modal2-body">
                <div class="field">
                    <label>Caption <span style="color:var(--red)">*</span></label>
                    <input class="input" type="text" name="caption" required maxlength="255" dir="auto"
                           placeholder="e.g. Attempted: gripper. Achieved: modern art.">
                </div>
                <div class="field">
                    <label>Photo of the disaster</label>
                    <input class="input" type="file" name="image" accept="image/*">
                    <div class="hint">Show us the spaghetti. JPG/PNG · up to <?= MAX_UPLOAD_MB ?> MB.</div>
                </div>
                <div class="form-row">
                    <div class="field" style="margin:0;">
                        <label>Filament wasted (g)</label>
                        <input class="input" type="number" step="1" min="0" name="grams" value="0">
                    </div>
                    <div class="field" style="margin:0;">
                        <label>Printer (optional)</label>
                        <input class="input" type="text" name="printer_name" list="printerList" placeholder="Printer 1 / Printer 2">
                        <datalist id="printerList"><option value="Printer 1"><option value="Printer 2"></datalist>
                    </div>
                </div>
            </div>
            <div class="modal2-foot">
                <button type="button" class="btn2" data-close>Cancel</button>
                <button type="submit" class="btn2 primary"><i class="bi bi-upload"></i> Post to the wall</button>
            </div>
        </form>
    </div>
</div>
