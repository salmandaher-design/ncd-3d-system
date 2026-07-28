<?php /** @var array $users @var array $teams */
$teamName = [];
foreach ($teams as $t) { $teamName[$t['id']] = $t['name']; }
?>

<div class="page-head">
    <div>
        <h2>Members &amp; Accounts</h2>
        <div class="sub"><?= count($users) ?> account<?= count($users) === 1 ? '' : 's' ?></div>
    </div>
    <div class="spacer"></div>
    <button class="btn2 primary" data-modal="#userModal" data-title="New Account"
            data-set-id="" data-set-name="" data-set-email="" data-set-role="member" data-set-team_id="" data-set-is_active="1">
        <i class="bi bi-person-plus"></i> Add account
    </button>
</div>

<div class="card2">
    <div class="card2-body tight">
        <div class="table-wrap">
            <table class="table2">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Team</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span class="avatar" style="width:30px;height:30px;font-size:12px;"><?= e(strtoupper(mb_substr($u['name'], 0, 1))) ?></span>
                                <strong><?= e($u['name']) ?></strong>
                            </div>
                        </td>
                        <td class="muted"><?= e($u['email']) ?></td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="badge2 status-approved"><i class="bi bi-shield-check"></i> Admin</span>
                            <?php else: ?>
                                <span class="badge2 status-submitted"><i class="bi bi-person"></i> Member</span>
                            <?php endif; ?>
                        </td>
                        <td class="muted"><?= e($u['team_name'] ?? '—') ?></td>
                        <td>
                            <?php if ((int) $u['is_active'] === 1): ?>
                                <span class="badge2 status-completed">Active</span>
                            <?php else: ?>
                                <span class="badge2 status-cancelled">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right; white-space:nowrap;">
                            <button class="btn2 sm" data-modal="#userModal" data-title="Edit Account"
                                    data-set-id="<?= $u['id'] ?>"
                                    data-set-name="<?= e($u['name']) ?>"
                                    data-set-email="<?= e($u['email']) ?>"
                                    data-set-phone="<?= e($u['phone'] ?? '') ?>"
                                    data-set-role="<?= e($u['role']) ?>"
                                    data-set-team_id="<?= e((string) ($u['team_id'] ?? '')) ?>"
                                    data-set-is_active="<?= (int) $u['is_active'] ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php if ($u['id'] !== Auth::id()): ?>
                            <form method="post" action="<?= url('users/delete/' . $u['id']) ?>" style="display:inline;"
                                  data-confirm="Delete account for <?= e($u['name']) ?>?">
                                <?= Csrf::field() ?>
                                <button class="btn2 sm danger"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User modal (create/edit) -->
<div class="modal2-backdrop" id="userModal">
    <div class="modal2">
        <form method="post" action="<?= url('users/save') ?>" autocomplete="off">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="">
            <div class="modal2-head"><h3 data-modal-title>New Account</h3>
                <button type="button" class="btn-x" data-close>&times;</button></div>
            <div class="modal2-body">
                <div class="field">
                    <label>Full name <span style="color:var(--red)">*</span></label>
                    <input class="input" type="text" name="name" required>
                </div>
                <div class="field">
                    <label>Email <span style="color:var(--red)">*</span></label>
                    <input class="input" type="email" name="email" required>
                </div>
                <div class="field">
                    <label>WhatsApp number
                        <span class="hint" style="font-weight:400;">for status notifications</span>
                    </label>
                    <input class="input" type="tel" name="phone" placeholder="e.g. 0912345678 or +963912345678">
                    <div class="hint">Used to open a pre-filled WhatsApp message when a request changes status.</div>
                </div>
                <div class="form-row">
                    <div class="field" style="margin:0;">
                        <label>Role</label>
                        <select class="select" name="role">
                            <option value="member">Team Member</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div class="field" style="margin:0;">
                        <label>Team</label>
                        <select class="select" name="team_id">
                            <option value="">— none —</option>
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label>Password</label>
                    <input class="input" type="password" name="password" placeholder="Leave blank to keep current (min 6 chars)">
                    <div class="hint">Required for new accounts. For existing accounts, fill only to change it.</div>
                </div>
                <label class="check" style="margin:0;">
                    <input type="checkbox" name="is_active" value="1" checked> Account is active
                </label>
            </div>
            <div class="modal2-foot">
                <button type="button" class="btn2" data-close>Cancel</button>
                <button type="submit" class="btn2 primary"><i class="bi bi-save"></i> Save account</button>
            </div>
        </form>
    </div>
</div>
