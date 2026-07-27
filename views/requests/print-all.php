<?php
/**
 * Multiple printable request sheets in one document (one A4 page per request).
 * @var array  $sheets    list of ['request' => row, 'files' => array]
 * @var string $heading   short description of the selection (shown in the toolbar)
 * @var int    $skipped   number of requests excluded because the admin hasn't responded yet
 */
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>طلبات الطباعة ثلاثية الأبعاد · <?= count($sheets) ?> طلب</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <?php require __DIR__ . '/_print_styles.php'; ?>
</head>
<body>

<div class="toolbar">
    <button class="primary" onclick="window.print()">🖨 طباعة الكل</button>
    <a href="<?= url('requests') ?>">رجوع</a>
    <span class="count">
        <?= count($sheets) ?> طلب · كل طلب في صفحة مستقلة
        <?php if ($skipped > 0): ?>
            — (تم استثناء <?= (int) $skipped ?> طلب لم يردّ عليها المدير بعد)
        <?php endif; ?>
    </span>
</div>

<?php if (!$sheets): ?>
    <div class="sheet">
        <div class="empty-note">
            لا توجد طلبات قابلة للطباعة حالياً.<br>
            يمكن طباعة الطلب فقط بعد أن يردّ عليه المدير (بالموافقة أو الرفض).
        </div>
    </div>
<?php else: ?>
    <?php foreach ($sheets as $sheet):
        // The partial reads $request and $files from this scope.
        $request = $sheet['request'];
        $files   = $sheet['files'];
        require __DIR__ . '/_sheet.php';
    endforeach; ?>
<?php endif; ?>

<script>
    if (new URLSearchParams(location.search).get('auto') === '1') { window.onload = function () { window.print(); }; }
</script>
</body>
</html>
