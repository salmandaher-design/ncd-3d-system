<?php /** @var array $request @var array $files — single printable request. */ ?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>طلب طباعة ثلاثية الأبعاد · #<?= (int) $request['id'] ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <?php require __DIR__ . '/_print_styles.php'; ?>
</head>
<body>

<div class="toolbar">
    <button class="primary" onclick="window.print()">🖨 طباعة الطلب</button>
    <a href="<?= url('requests/show/' . $request['id']) ?>">رجوع</a>
</div>

<?php require __DIR__ . '/_sheet.php'; ?>

<script>
    if (new URLSearchParams(location.search).get('auto') === '1') { window.onload = function () { window.print(); }; }
</script>
</body>
</html>
