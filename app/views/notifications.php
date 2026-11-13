<?php
require_once __DIR__ . '/../core/CSRF.php';

function notificationEscape($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thông báo - Smart Campus</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container" style="max-width:1050px;margin:30px auto;padding:24px;background:#fff;border-radius:14px">
        <h2>🔔 Trung tâm thông báo</h2>
        <p>Thông báo về lịch đặt phòng và quá trình bảo trì thiết bị.</p>

        <?php if (!$notifications): ?>
            <p>Chưa có thông báo.</p>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <div style="padding:14px;margin:10px 0;border:1px solid #ddd;border-radius:9px;<?= $n['read_at'] ? 'opacity:.7' : 'background:#eef8f1' ?>">
                    <strong><?= notificationEscape($n['message']) ?></strong>
                    <?php if (!empty($n['booking_id'])): ?>
                        <p><?= notificationEscape(($n['room_code'] ?? '') . ' - ' . ($n['room_name'] ?? '')) ?><?php if (!empty($n['start_time'])): ?> · <?= notificationEscape($n['start_time']) ?><?php endif; ?></p>
                        <a href="index.php?action=calendar#booking-<?= (int)$n['booking_id'] ?>">Xem lịch đặt phòng</a>
                    <?php elseif (!empty($n['report_id'])): ?>
                        <p><?= notificationEscape(($n['equipment_code'] ?? '') . ' - ' . ($n['equipment_name'] ?? '')) ?><?php if (!empty($n['report_title'])): ?> · <?= notificationEscape($n['report_title']) ?><?php endif; ?></p>
                        <a href="index.php?action=report_list">Xem phiếu bảo trì</a>
                    <?php endif; ?>

                    <?php if (!$n['read_at']): ?>
                        <form method="post" action="index.php?action=notification_read" style="display:inline;margin-left:12px">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="notification_id" value="<?= (int)$n['notification_id'] ?>">
                            <input type="hidden" name="notification_source" value="<?= notificationEscape($n['notification_source'] ?? 'reminder') ?>">
                            <button type="submit">Đánh dấu đã đọc</button>
                        </form>
                    <?php else: ?>
                        <span> · Đã đọc</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <p><a href="index.php?action=dashboard">← Dashboard</a></p>
    </div>
</body>
</html>