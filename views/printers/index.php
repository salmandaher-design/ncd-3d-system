<?php /** @var array $printers */ $isAdmin = Auth::isAdmin(); ?>

<div class="page-head">
    <div>
        <h2>Printers</h2>
        <div class="sub">Live status of the center's 3D printers</div>
    </div>
</div>

<div class="grid-2">
    <?php foreach ($printers as $p):
        $busy = $p['status'] === 'Busy'; ?>
        <div class="card2">
            <div class="card2-body">
                <div style="display:flex; align-items:center; gap:14px; margin-bottom:16px;">
                    <span class="stat-icon <?= $busy ? 'i-purple' : 'i-green' ?>" style="width:52px;height:52px;font-size:26px;">
                        <i class="bi bi-printer-fill"></i>
                    </span>
                    <div style="flex:1;">
                        <div style="font-size:17px; font-weight:700;"><?= e($p['name']) ?></div>
                        <span class="badge2 <?= $busy ? 'status-printing' : 'status-completed' ?>">
                            <i class="bi bi-<?= $busy ? 'printer' : 'check-circle' ?>"></i> <?= $busy ? 'Busy' : 'Idle' ?>
                        </span>
                    </div>
                    <?php if ($isAdmin): ?>
                    <button class="btn2 sm" data-modal="#printerModal" data-title="Rename Printer"
                            data-set-id="<?= $p['id'] ?>" data-set-name="<?= e($p['name']) ?>">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <?php endif; ?>
                </div>

                <?php if ($busy): ?>
                    <dl class="kv">
                        <dt>Current project</dt><dd><?= e($p['current_project'] ?: '—') ?></dd>
                        <dt>Team</dt><dd><?= e($p['current_team'] ?: '—') ?></dd>
                        <dt>Operator</dt><dd><?= e($p['current_operator'] ?: '—') ?></dd>
                    </dl>
                    <?php if ($isAdmin): ?>
                        <hr class="divider">
                        <form method="post" action="<?= url('printers/free/' . $p['id']) ?>" data-confirm="Set this printer back to idle?">
                            <?= Csrf::field() ?>
                            <button class="btn2 sm block"><i class="bi bi-stop-circle"></i> Set to idle</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty" style="padding:20px;">
                        <i class="bi bi-check2-circle" style="color:var(--green)"></i>
                        Available for the next print job.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($isAdmin): ?>
<!-- Printer rename modal -->
<div class="modal2-backdrop" id="printerModal">
    <div class="modal2">
        <form method="post" action="<?= url('printers/save') ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="">
            <div class="modal2-head"><h3 data-modal-title>Rename Printer</h3>
                <button type="button" class="btn-x" data-close>&times;</button></div>
            <div class="modal2-body">
                <div class="field" style="margin:0;">
                    <label>Printer name</label>
                    <input class="input" type="text" name="name" required>
                </div>
            </div>
            <div class="modal2-foot">
                <button type="button" class="btn2" data-close>Cancel</button>
                <button type="submit" class="btn2 primary"><i class="bi bi-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
