<?php
/** @var array|null $current  @var array $archive */
$isAdmin = Auth::isAdmin();

/** Build a WhatsApp share link for one item. */
if (!function_exists('wa_link')) {
    function wa_link(array $n): string
    {
        $text = $n['title'] . "\n\n" . ($n['content'] ?? '');
        $text .= "\n\n" . date('Y/m/d', strtotime($n['created_at']));
        $text .= "\n— " . APP_FULL_NAME . ' · مخبر الروبوت والذكاء الصنعي';
        return 'https://wa.me/?text=' . rawurlencode($text);
    }
}
?>

<div class="page-head">
    <div>
        <h2>الأخبار / News</h2>
        <div class="sub">
            <?= $current ? 'الخبر الحالي في البانر + ' . count($archive) . ' خبر سابق' : 'لا توجد أخبار بعد' ?>
        </div>
    </div>
    <div class="spacer"></div>
    <?php if ($isAdmin): ?>
        <button class="btn2 primary" data-modal="#newsPublishModal">
            <i class="bi bi-megaphone"></i> نشر خبر جديد
        </button>
    <?php endif; ?>
</div>

<?php if ($current): ?>
    <!-- Current banner item -->
    <div class="card2" style="margin-bottom:18px;">
        <div class="card2-head">
            <i class="bi bi-star-fill" style="color:var(--amber);"></i> الخبر الحالي (يظهر في البانر)
        </div>
        <div class="card2-body">
            <div style="display:flex; gap:16px; flex-wrap:wrap;">
                <img class="news-thumb" style="width:150px; height:100px;"
                     src="<?= !empty($current['image_path']) ? url($current['image_path']) : asset('images/banner.jpg') ?>" alt="">
                <div style="flex:1; min-width:220px;">
                    <h3 style="margin:0 0 6px; font-size:17px;" dir="auto"><?= e($current['title']) ?></h3>
                    <div class="news-text" dir="auto"><?= e($current['content'] ?? '') ?></div>
                    <div class="news-date">
                        <span><i class="bi bi-calendar3"></i> <?= fmt_datetime($current['created_at']) ?></span>
                        <?php if (!empty($current['author'])): ?>
                            <span><i class="bi bi-person"></i> <?= e($current['author']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($isAdmin): ?>
                <hr class="divider">
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <a class="btn2 sm" style="background:#25D366; border-color:#25D366; color:#fff;"
                       target="_blank" rel="noopener" href="<?= e(wa_link($current)) ?>">
                        <i class="bi bi-whatsapp"></i> مشاركة على واتساب
                    </a>
                    <button class="btn2 sm" data-modal="#newsEditModal"
                            data-set-id="<?= $current['id'] ?>"
                            data-set-title="<?= e($current['title']) ?>"
                            data-set-content="<?= e($current['content'] ?? '') ?>">
                        <i class="bi bi-pencil"></i> تعديل هذا الخبر
                    </button>
                    <form method="post" action="<?= url('news/delete/' . $current['id']) ?>"
                          data-confirm="حذف هذا الخبر نهائياً؟">
                        <?= Csrf::field() ?>
                        <button class="btn2 sm danger"><i class="bi bi-trash"></i> حذف</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Archive -->
<div class="card2">
    <div class="card2-head">
        <i class="bi bi-archive"></i> الأخبار السابقة
        <span class="badge2 status-submitted" style="margin-right:auto; margin-left:0;"><?= count($archive) ?></span>
    </div>
    <div class="card2-body tight">
        <?php if (!$archive): ?>
            <div class="empty"><i class="bi bi-newspaper"></i>لا توجد أخبار سابقة بعد.</div>
        <?php else: foreach ($archive as $n): ?>
            <div class="news-item">
                <img class="news-thumb"
                     src="<?= !empty($n['image_path']) ? url($n['image_path']) : asset('images/banner.jpg') ?>" alt="">
                <div class="news-body">
                    <h4 dir="auto"><?= e($n['title']) ?></h4>
                    <div class="news-text" dir="auto"><?= e($n['content'] ?? '') ?></div>
                    <div class="news-date">
                        <span><i class="bi bi-calendar3"></i> <?= fmt_datetime($n['created_at']) ?></span>
                        <?php if (!empty($n['author'])): ?>
                            <span><i class="bi bi-person"></i> <?= e($n['author']) ?></span>
                        <?php endif; ?>
                        <?php if ($isAdmin): ?>
                            <a target="_blank" rel="noopener" href="<?= e(wa_link($n)) ?>"
                               style="color:#25D366; font-weight:600;">
                                <i class="bi bi-whatsapp"></i> مشاركة
                            </a>
                            <a href="#" data-modal="#newsEditModal"
                               data-set-id="<?= $n['id'] ?>"
                               data-set-title="<?= e($n['title']) ?>"
                               data-set-content="<?= e($n['content'] ?? '') ?>">
                                <i class="bi bi-pencil"></i> تعديل
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($isAdmin): ?>
                    <form method="post" action="<?= url('news/delete/' . $n['id']) ?>"
                          data-confirm="حذف هذا الخبر نهائياً؟">
                        <?= Csrf::field() ?>
                        <button class="btn2 sm danger"><i class="bi bi-trash"></i></button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Publish new -->
<div class="modal2-backdrop" id="newsPublishModal">
    <div class="modal2">
        <form method="post" action="<?= url('news/publish') ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <input type="hidden" name="back" value="news">
            <div class="modal2-head"><h3>نشر خبر جديد</h3>
                <button type="button" class="btn-x" data-close>&times;</button></div>
            <div class="modal2-body">
                <div class="alert alert-info" style="font-size:13px;">
                    <i class="bi bi-info-circle"></i>
                    الخبر الجديد يظهر في البانر، وينتقل الخبر الحالي تلقائياً إلى الأخبار السابقة.
                </div>
                <div class="field">
                    <label>عنوان الخبر <span style="color:var(--red)">*</span></label>
                    <input class="input" type="text" name="title" required maxlength="200" dir="auto">
                </div>
                <div class="field">
                    <label>نص الخبر</label>
                    <textarea class="textarea" name="content" rows="4" dir="auto"></textarea>
                </div>
                <div class="field" style="margin:0;">
                    <label>صورة البانر (اختياري)</label>
                    <input class="input" type="file" name="image" accept="image/*">
                    <div class="hint">إن لم تُرفَق صورة تُستخدم الصورة الافتراضية · الحد الأقصى <?= MAX_UPLOAD_MB ?> ميغابايت.</div>
                </div>
            </div>
            <div class="modal2-foot">
                <button type="button" class="btn2" data-close>إلغاء</button>
                <button type="submit" class="btn2 primary"><i class="bi bi-megaphone"></i> نشر</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit in place -->
<div class="modal2-backdrop" id="newsEditModal">
    <div class="modal2">
        <form method="post" id="newsEditForm" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <input type="hidden" name="back" value="news">
            <input type="hidden" name="id" value="">
            <div class="modal2-head"><h3>تعديل الخبر</h3>
                <button type="button" class="btn-x" data-close>&times;</button></div>
            <div class="modal2-body">
                <div class="field">
                    <label>عنوان الخبر <span style="color:var(--red)">*</span></label>
                    <input class="input" type="text" name="title" required maxlength="200" dir="auto">
                </div>
                <div class="field">
                    <label>نص الخبر</label>
                    <textarea class="textarea" name="content" rows="4" dir="auto"></textarea>
                </div>
                <div class="field" style="margin:0;">
                    <label>استبدال الصورة (اختياري)</label>
                    <input class="input" type="file" name="image" accept="image/*">
                    <div class="hint">اتركه فارغاً للإبقاء على الصورة الحالية.</div>
                </div>
            </div>
            <div class="modal2-foot">
                <button type="button" class="btn2" data-close>إلغاء</button>
                <button type="submit" class="btn2 primary"><i class="bi bi-save"></i> حفظ التعديل</button>
            </div>
        </form>
    </div>
</div>
<script>
    // Point the edit form at the right item when the modal opens.
    document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-modal="#newsEditModal"]');
        if (!t) return;
        var form = document.getElementById('newsEditForm');
        form.action = '<?= url('news/update/') ?>' + t.getAttribute('data-set-id');
    });
</script>
<?php endif; ?>
