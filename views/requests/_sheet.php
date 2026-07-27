<?php
/**
 * One printable A4 request sheet.
 * Expects: $request (row incl. team_name/requester_name/filament_color) and $files (array).
 */
$st = $request['status'];
$isRejected  = $st === 'Rejected';
$isCancelled = $st === 'Cancelled';

$statusAr = [
    'Submitted' => 'مُقدَّم',
    'Approved'  => 'موافَق عليه',
    'Printing'  => 'قيد الطباعة',
    'Completed' => 'مكتمل',
    'Rejected'  => 'مرفوض',
    'Cancelled' => 'ملغى',
][$st] ?? $st;

$priorityAr = [
    'Low'    => 'منخفضة',
    'Medium' => 'متوسطة',
    'High'   => 'عالية',
][$request['priority']] ?? $request['priority'];

$teamName = $request['team_name'] ?? '—';
$today    = date('Y/m/d');
?>
<div class="sheet">

    <!-- الترويسة -->
    <div class="letterhead">
        <img class="side"  src="<?= asset('images/dca.jpg') ?>" alt="هيئة التميز والإبداع">
        <img class="eagle" src="<?= asset('images/syrian.jpg') ?>" alt="شعار">
        <img class="side"  src="<?= asset('images/ncd.jpg') ?>" alt="المركز الوطني للمتميزين">
    </div>

    <!-- الرقم / التاريخ / الموضوع / رقم المعاملة -->
    <div class="meta">
        <div class="row"><span class="lbl">الرقم:</span> <span class="blank"></span></div>
        <div class="row"><span class="lbl">التاريخ:</span> <span><?= e($today) ?></span></div>
        <div class="row"><span class="lbl">الموضوع:</span> <span>طلب طباعة قطع (<?= e($teamName) ?>)</span></div>
        <div class="row"><span class="lbl">رقم المعاملة على موقع التسجيل:</span>
            <span><?= ($request['transaction_no'] ?? '') !== '' ? e($request['transaction_no']) : '—' ?></span>
        </div>
    </div>

    <!-- العنوان -->
    <h1 class="title">طلب طباعة ثلاثية الأبعاد في مخبر الروبوت والذكاء الصنعي</h1>

    <?php if ($isRejected): ?>
        <div class="reject-box">
            <div class="h">✗ نأسف لإبلاغكم بأنه تم رفض هذا الطلب.</div>
            <div><strong>الأسباب:</strong>
                <?= ($request['admin_notes'] ?? '') !== '' ? nl2br(e($request['admin_notes'])) : 'لم تُذكر أسباب.' ?>
            </div>
        </div>
    <?php elseif ($isCancelled): ?>
        <div class="cancel-box">
            <strong>تنويه:</strong> تم إلغاء هذا الطلب.
            <?php if (!empty($request['admin_notes'])): ?><br><?= nl2br(e($request['admin_notes'])) ?><?php endif; ?>
        </div>
    <?php else: ?>
        <p class="lead">
            بناءً على طلب الفريق (<?= e($teamName) ?>)، تمت الموافقة على طباعة القطع المذكورة أدناه في مخبر
            الروبوت والذكاء الصنعي، ووفق التفاصيل التالية:
        </p>
    <?php endif; ?>

    <!-- تفاصيل الطلب -->
    <table class="details">
        <tr><th>اسم المشروع</th><td><?= e($request['project_name']) ?></td></tr>
        <tr><th>الفريق</th><td><?= e($teamName) ?></td></tr>
        <tr><th>مُقدِّم الطلب</th><td><?= e($request['requester_name'] ?? '—') ?></td></tr>
        <tr><th>الأولوية</th><td><?= e($priorityAr) ?></td></tr>
        <tr><th>اللون المطلوب</th><td><?= ($request['color'] ?? '') !== '' ? e($request['color']) : '—' ?></td></tr>
        <?php if (!$isRejected && !$isCancelled): ?>
            <tr><th>الفيلامنت المستخدم</th><td><?= ($request['filament_color'] ?? '') !== '' ? e($request['filament_color']) : '—' ?></td></tr>
            <tr><th>الوزن المستخدم</th><td><?= $request['actual_weight'] !== null ? e((string) $request['actual_weight']) . ' غرام' : '—' ?></td></tr>
        <?php endif; ?>
        <tr><th>الوصف</th><td><?= ($request['description'] ?? '') !== '' ? nl2br(e($request['description'])) : '—' ?></td></tr>
        <?php if ($files): ?>
            <tr><th>الملفات المرفقة</th><td><?php foreach ($files as $i => $f) { echo ($i ? '، ' : '') . e($f['file_name']); } ?></td></tr>
        <?php endif; ?>
        <?php if (!$isRejected && !empty($request['admin_notes'])): ?>
            <tr><th>ملاحظات الإدارة</th><td><?= nl2br(e($request['admin_notes'])) ?></td></tr>
        <?php endif; ?>
        <tr><th>حالة الطلب</th>
            <td><span class="stamp <?= ($isRejected || $isCancelled) ? 'no' : 'ok' ?>"><?= e($statusAr) ?></span></td>
        </tr>
    </table>

    <!-- التواقيع -->
    <div class="signatures">
        <div class="sig">مشرف الفريق<div class="line">الاسم والتوقيع</div></div>
        <div class="sig">مسؤول مخبر الروبوت والذكاء الصنعي<div class="line">الاسم والتوقيع</div></div>
    </div>

    <div class="foot">
        وثيقة صادرة عن نظام <?= e(APP_NAME) ?> · معرّف الطلب في النظام: #<?= (int) $request['id'] ?> · تاريخ الطباعة: <?= e($today) ?>
    </div>
</div>
