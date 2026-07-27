<?php /** @var array $teams */ ?>

<div class="page-head">
    <div>
        <h2>Teams</h2>
        <div class="sub"><?= count($teams) ?> team<?= count($teams) === 1 ? '' : 's' ?></div>
    </div>
    <div class="spacer"></div>
    <button class="btn2 primary" data-modal="#teamModal" data-title="New Team"
            data-set-id="" data-set-name="" data-set-competition="" data-set-supervisor="">
        <i class="bi bi-plus-lg"></i> Add team
    </button>
</div>

<div class="card2">
    <div class="card2-body tight">
        <div class="table-wrap">
            <table class="table2">
                <thead><tr><th>Team</th><th>Competition</th><th>Supervisor</th><th>Members</th><th>Requests</th><th></th></tr></thead>
                <tbody>
                <?php if (!$teams): ?>
                    <tr><td colspan="6"><div class="empty"><i class="bi bi-people"></i>No teams yet. Create your first team.</div></td></tr>
                <?php else: foreach ($teams as $t): ?>
                    <tr>
                        <td><strong><?= e($t['name']) ?></strong></td>
                        <td class="muted"><?= e($t['competition'] ?: '—') ?></td>
                        <td class="muted"><?= e($t['supervisor'] ?: '—') ?></td>
                        <td><span class="pill"><i class="bi bi-person"></i> <?= (int) $t['member_count'] ?></span></td>
                        <td><span class="pill"><i class="bi bi-inbox"></i> <?= (int) $t['request_count'] ?></span></td>
                        <td style="text-align:right; white-space:nowrap;">
                            <button class="btn2 sm" data-modal="#teamModal" data-title="Edit Team"
                                    data-set-id="<?= $t['id'] ?>"
                                    data-set-name="<?= e($t['name']) ?>"
                                    data-set-competition="<?= e($t['competition']) ?>"
                                    data-set-supervisor="<?= e($t['supervisor']) ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="post" action="<?= url('teams/delete/' . $t['id']) ?>" style="display:inline;"
                                  data-confirm="Delete team &quot;<?= e($t['name']) ?>&quot;?">
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

<!-- Team modal (create/edit) -->
<div class="modal2-backdrop" id="teamModal">
    <div class="modal2">
        <form method="post" action="<?= url('teams/save') ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="">
            <div class="modal2-head"><h3 data-modal-title>New Team</h3>
                <button type="button" class="btn-x" data-close>&times;</button></div>
            <div class="modal2-body">
                <div class="field">
                    <label>Team name <span style="color:var(--red)">*</span></label>
                    <input class="input" type="text" name="name" required placeholder="e.g. NCD Robotics A">
                </div>
                <div class="field">
                    <label>Competition</label>
                    <input class="input" type="text" name="competition" placeholder="e.g. FIRST Global Challenge">
                </div>
                <div class="field" style="margin:0;">
                    <label>Supervisor</label>
                    <input class="input" type="text" name="supervisor" placeholder="Supervisor name">
                </div>
            </div>
            <div class="modal2-foot">
                <button type="button" class="btn2" data-close>Cancel</button>
                <button type="submit" class="btn2 primary"><i class="bi bi-save"></i> Save team</button>
            </div>
        </form>
    </div>
</div>
