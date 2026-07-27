<?php
/**
 * Dashboard news banner.
 * Expects: $banner (latest news row or null). Shown to admins and members.
 * Admins additionally get "publish update" and "share on WhatsApp".
 */
$isAdmin  = Auth::isAdmin();
$bImage   = !empty($banner['image_path']) ? url($banner['image_path']) : asset('images/banner.jpg');
$bTitle   = $banner['title']   ?? 'مرحباً بكم في مخبر الروبوت والذكاء الصنعي';
$bContent = $banner['content'] ?? 'نظام تنظيم طلبات الطباعة ثلاثية الأبعاد لفرق الروبوتات في المركز الوطني للمتميزين.';
$bDate    = $banner['created_at'] ?? null;

// Message used for the WhatsApp share (admin only).
$waText = $bTitle . "\n\n" . $bContent;
if ($bDate) {
    $waText .= "\n\n" . date('Y/m/d', strtotime($bDate));
}
$waText .= "\n— " . APP_FULL_NAME . ' · مخبر الروبوت والذكاء الصنعي';
?>

<div class="banner">
    <img class="banner-img" src="<?= e($bImage) ?>" alt="" aria-hidden="true">
    <div class="banner-body">
        <span class="banner-tag"><i class="bi bi-megaphone-fill"></i> <?= $banner ? 'آخر خبر' : 'أهلاً بك' ?></span>
        <h2 dir="auto"><?= e($bTitle) ?></h2>
        <?php if ($bContent !== ''): ?>
            <p dir="auto"><?= e($bContent) ?></p>
        <?php endif; ?>

        <div class="banner-meta">
            <?php if ($bDate): ?>
                <span><i class="bi bi-calendar3"></i> <?= fmt_date($bDate, 'Y/m/d') ?></span>
                <span><i class="bi bi-clock"></i> <?= fmt_date($bDate, 'g:i A') ?></span>
            <?php endif; ?>
            <?php if (!empty($banner['author'])): ?>
                <span><i class="bi bi-person"></i> <?= e($banner['author']) ?></span>
            <?php endif; ?>

            <span class="banner-actions">
                <a class="btn-glass" href="<?= url('news') ?>"><i class="bi bi-newspaper"></i> الأخبار السابقة</a>
                <?php if ($isAdmin): ?>
                    <a class="btn-glass wa" target="_blank" rel="noopener"
                       href="https://wa.me/?text=<?= rawurlencode($waText) ?>">
                        <i class="bi bi-whatsapp"></i> مشاركة على واتساب
                    </a>
                    <button type="button" class="btn-glass" data-modal="#newsModal">
                        <i class="bi bi-pencil-square"></i> تحديث الخبر
                    </button>
                <?php endif; ?>
            </span>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Publish a new banner item (the current one moves to the archive) -->
<div class="modal2-backdrop" id="newsModal">
    <div class="modal2">
        <form method="post" action="<?= url('news/publish') ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <input type="hidden" name="back" value="dashboard">
            <div class="modal2-head">
                <h3>تحديث خبر البانر</h3>
                <button type="button" class="btn-x" data-close>&times;</button>
            </div>
            <div class="modal2-body">
                <div class="alert alert-info d-flex align-items-center" style="font-size:13px;">
                    <i class="bi bi-info-circle me-2"></i>
                    <div>عند النشر ينتقل الخبر الحالي تلقائياً إلى «الأخبار السابقة» ويظهر الخبر الجديد في البانر.</div>
                </div>
                <div class="field">
                    <label>عنوان الخبر <span style="color:var(--red)">*</span></label>
                    <input class="input" type="text" name="title" required maxlength="200" dir="auto"
                           placeholder="مثال: تم استلام بكرات فيلامنت جديدة">
                </div>
                <div class="field">
                    <label>نص الخبر</label>
                    <textarea class="textarea" name="content" rows="4" dir="auto"
                              placeholder="اكتب تفاصيل الخبر هنا…"></textarea>
                </div>
                <div class="field" style="margin:0;">
                    <label>صورة البانر (اختياري)</label>
                    <input class="input" type="file" name="image" accept="image/*">
                    <div class="hint">
                        إن لم تُرفَق صورة تُستخدم الصورة الافتراضية للمخبر.
                        يُفضّل صورة عريضة بعرض ١٩٢٠ بكسل تقريباً · الحد الأقصى <?= MAX_UPLOAD_MB ?> ميغابايت.
                    </div>
                </div>
            </div>
            <div class="modal2-foot">
                <button type="button" class="btn2" data-close>إلغاء</button>
                <button type="submit" class="btn2 primary"><i class="bi bi-megaphone"></i> نشر الخبر</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
