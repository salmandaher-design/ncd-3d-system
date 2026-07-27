<?php /** @var array $spools */
$total = 0; $totalSpools = 0;
foreach ($spools as $s) { $total += (float) $s['remaining_weight']; $totalSpools += (int) ($s['spools'] ?? 1); }
?>

<div class="page-head">
    <div>
        <h2>Filament Inventory</h2>
        <div class="sub"><?= count($spools) ?> filaments · <?= $totalSpools ?> spools · <?= number_format($total) ?> g total remaining</div>
    </div>
    <div class="spacer"></div>
    <button class="btn2 primary" data-modal="#filModal" data-title="Add Filament"
            data-set-id="" data-set-color="" data-set-material="PLA" data-set-remaining_weight="1000" data-set-spools="1" data-set-location="" data-set-notes="">
        <i class="bi bi-plus-lg"></i> Add filament
    </button>
</div>

<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
    <div class="stat">
        <span class="stat-icon i-blue"><i class="bi bi-collection"></i></span>
        <div><div class="stat-num"><?= $totalSpools ?></div><div class="stat-label">Total spools</div></div>
    </div>
    <div class="stat">
        <span class="stat-icon i-green"><i class="bi bi-box-seam"></i></span>
        <div><div class="stat-num"><?= number_format($total) ?> g</div><div class="stat-label">Total remaining</div></div>
    </div>
    <div class="stat">
        <span class="stat-icon i-amber"><i class="bi bi-exclamation-triangle"></i></span>
        <div><div class="stat-num"><?= count(array_filter($spools, fn($s) => filament_level($s['remaining_weight']) !== '')) ?></div><div class="stat-label">Need restock</div></div>
    </div>
</div>

<div class="card2">
    <div class="card2-body tight">
        <div class="table-wrap">
            <table class="table2">
                <thead><tr><th>Color</th><th>Material</th><th>Spools</th><th>Remaining</th><th style="width:180px;">Level</th><th>Location</th><th></th></tr></thead>
                <tbody>
                <?php if (!$spools): ?>
                    <tr><td colspan="7"><div class="empty"><i class="bi bi-palette"></i>No filament recorded yet.</div></td></tr>
                <?php else: foreach ($spools as $s):
                    $lvl = filament_level($s['remaining_weight']);
                    $pct = min(100, round((float) $s['remaining_weight'] / 1000 * 100));
                ?>
                    <tr>
                        <td>
                            <span class="pill"><span class="color-dot" style="background: <?= e(strtolower($s['color'])) ?>;"></span> <strong><?= e($s['color']) ?></strong></span>
                        </td>
                        <td class="muted"><?= e($s['material'] ?: '—') ?></td>
                        <td><span class="pill"><i class="bi bi-collection"></i> <?= (int) ($s['spools'] ?? 1) ?></span></td>
                        <td>
                            <strong><?= number_format((float) $s['remaining_weight']) ?> g</strong>
                            <?php if ($lvl === 'crit'): ?>
                                <span class="badge2 status-rejected" style="margin-left:6px;"><i class="bi bi-exclamation-octagon"></i> Critical</span>
                            <?php elseif ($lvl === 'warn'): ?>
                                <span class="badge2 priority-medium" style="margin-left:6px;"><i class="bi bi-exclamation-triangle"></i> Low</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="meter <?= $lvl ?>"><span style="width: <?= max(3, $pct) ?>%;"></span></div>
                        </td>
                        <td class="muted"><?= e($s['location'] ?: '—') ?></td>
                        <td style="text-align:right; white-space:nowrap;">
                            <button class="btn2 sm" data-modal="#filModal" data-title="Edit Filament"
                                    data-set-id="<?= $s['id'] ?>"
                                    data-set-color="<?= e($s['color']) ?>"
                                    data-set-material="<?= e($s['material']) ?>"
                                    data-set-remaining_weight="<?= e((string) $s['remaining_weight']) ?>"
                                    data-set-spools="<?= (int) ($s['spools'] ?? 1) ?>"
                                    data-set-location="<?= e($s['location']) ?>"
                                    data-set-notes="<?= e($s['notes']) ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="post" action="<?= url('filament/delete/' . $s['id']) ?>" style="display:inline;"
                                  data-confirm="Remove this filament spool?">
                                <?= Csrf::field() ?>
                                <button class="btn2 sm danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<p class="hint" style="margin-top:10px;">
    <i class="bi bi-info-circle"></i>
    Warnings appear below <?= FILAMENT_WARN_LOW ?> g (low) and <?= FILAMENT_WARN_CRIT ?> g (critical).
    When the administrator <strong>approves</strong> a request, the chosen weight is deducted from the selected spool
    (and returned automatically if the request is later cancelled, rejected or deleted).
</p>

<!-- Filament modal -->
<div class="modal2-backdrop" id="filModal">
    <div class="modal2">
        <form method="post" action="<?= url('filament/save') ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="">
            <div class="modal2-head"><h3 data-modal-title>Add Filament</h3>
                <button type="button" class="btn-x" data-close>&times;</button></div>
            <div class="modal2-body">
                <div class="form-row">
                    <div class="field" style="margin:0;">
                        <label>Color / name <span style="color:var(--red)">*</span></label>
                        <input class="input" type="text" name="color" required placeholder="e.g. Black">
                    </div>
                    <div class="field" style="margin:0;">
                        <label>Material</label>
                        <input class="input" type="text" name="material" placeholder="e.g. PLA, PETG, ABS">
                    </div>
                </div>
                <div class="form-row" style="margin-top:16px;">
                    <div class="field" style="margin:0;">
                        <label>Remaining weight (g)</label>
                        <input class="input" type="number" step="1" min="0" name="remaining_weight" value="1000">
                    </div>
                    <div class="field" style="margin:0;">
                        <label>Number of spools</label>
                        <input class="input" type="number" step="1" min="0" name="spools" value="1">
                        <div class="hint">How many physical spools of this filament you have.</div>
                    </div>
                </div>
                <div class="field" style="margin:16px 0 0;">
                    <label>Location</label>
                    <input class="input" type="text" name="location" placeholder="e.g. Shelf B2">
                </div>
                <div class="field" style="margin:16px 0 0;">
                    <label>Notes</label>
                    <textarea class="textarea" name="notes" placeholder="Optional notes"></textarea>
                </div>
            </div>
            <div class="modal2-foot">
                <button type="button" class="btn2" data-close>Cancel</button>
                <button type="submit" class="btn2 primary"><i class="bi bi-save"></i> Save filament</button>
            </div>
        </form>
    </div>
</div>
