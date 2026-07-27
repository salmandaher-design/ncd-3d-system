<?php /** @var array $teams */ $isAdmin = Auth::isAdmin(); ?>

<div class="page-head">
    <div>
        <h2>New Print Request</h2>
        <div class="sub">Tell us what you need printed — the lab team handles the technical details.</div>
    </div>
    <div class="spacer"></div>
    <a class="btn2 ghost" href="<?= url('requests') ?>"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div style="max-width:760px;">
<form method="post" action="<?= url('requests/store') ?>" enctype="multipart/form-data">
    <?= Csrf::field() ?>

    <div class="card2" style="margin-bottom:18px;">
        <div class="card2-head"><i class="bi bi-info-circle"></i> Project details</div>
        <div class="card2-body">
            <div class="field">
                <label for="project_name">Project name <span style="color:var(--red)">*</span></label>
                <input class="input" type="text" id="project_name" name="project_name" required
                       placeholder="e.g. Robot arm gripper v2">
            </div>

            <div class="field">
                <label for="description">Short description</label>
                <textarea class="textarea" id="description" name="description"
                          placeholder="Briefly describe the part, its purpose, or anything the lab should know."></textarea>
            </div>

            <div class="form-row">
                <?php if ($isAdmin): ?>
                <div class="field">
                    <label for="team_id">Team <span style="color:var(--red)">*</span></label>
                    <select class="select" id="team_id" name="team_id" required>
                        <option value="">Select a team…</option>
                        <?php foreach ($teams as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="field">
                    <label for="priority">Priority</label>
                    <select class="select" id="priority" name="priority">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>

                <div class="field">
                    <label for="color">Desired color</label>
                    <input class="input" type="text" id="color" name="color" placeholder="e.g. Black, White, Red…">
                </div>
            </div>

            <div class="hint" style="margin-bottom:0; display:flex; align-items:center; gap:6px;">
                <i class="bi bi-info-circle"></i>
                A transaction number (<span dir="rtl">رقم المعاملة</span>) will be assigned automatically when you submit.
            </div>
        </div>
    </div>

    <div class="card2" style="margin-bottom:18px;">
        <div class="card2-head"><i class="bi bi-image"></i> Project image (optional)</div>
        <div class="card2-body">
            <div class="field" style="margin:0;">
                <label for="imageInput">Upload one image of your model / reference</label>
                <input class="input" type="file" id="imageInput" name="image" accept="image/*">
                <div class="hint">JPG, PNG, WEBP or GIF · up to <?= MAX_UPLOAD_MB ?> MB</div>
                <img id="imagePreview" class="thumb-lg" style="display:none; margin-top:12px;" alt="preview">
            </div>
        </div>
    </div>

    <div class="card2" style="margin-bottom:18px;">
        <div class="card2-head"><i class="bi bi-file-earmark-arrow-up"></i> Print files</div>
        <div class="card2-body">
            <div class="dropzone" id="fileDrop">
                <i class="bi bi-cloud-arrow-up"></i>
                <div><strong>Click to browse</strong> or drag &amp; drop your files here</div>
                <div class="hint">Allowed: <?= strtoupper(implode(', ', ALLOWED_FILE_EXT)) ?> · up to <?= MAX_UPLOAD_MB ?> MB each</div>
            </div>
            <input type="file" id="fileInput" name="files[]" multiple
                   accept=".stl,.3mf,.zip" style="display:none;">
            <div class="file-list" id="fileList"></div>
        </div>
    </div>

    <div style="display:flex; gap:10px; justify-content:flex-end;">
        <a href="<?= url('requests') ?>" class="btn2">Cancel</a>
        <button type="submit" class="btn2 primary"><i class="bi bi-send"></i> Submit request</button>
    </div>
</form>
</div>
