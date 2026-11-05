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
        <h2>🔔 Thông báo lịch sắp tới</h2>
        <p>Nhắc trước 30 phút đối với booking đã được duyệt.</p>

        <?php if (!$notifications): ?>
            <p>Chưa có thông báo.</p>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <div style="padding:14px;margin:10px 0;border:1px solid #ddd;border-radius:9px;<?= $n['read_at'] ? 'opacity:.7' : 'background:#eef8f1' ?>">
                    <strong><?= notificationEscape($n['message']) ?></strong>
                    <p>
                        <?= notificationEscape($n['room_code'].' - '.$n['room_name']) ?>
                        · <?= notificationEscape($n['start_time']) ?>
                    </p>
                    <a href="index.php?action=calendar#booking-<?= (int)($n['booking_id'] ?? 0) ?>">Xem booking liên quan</a>

                    <?php if (!$n['read_at']): ?>
                        <form method="post" action="index.php?action=notification_read" style="display:inline;margin-left:12px">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="notification_id" value="<?= (int)$n['notification_id'] ?>">
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