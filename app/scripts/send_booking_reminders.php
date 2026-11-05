<?php
// Chạy bằng PHP CLI mỗi 5 phút (XAMPP Task Scheduler/cron).
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');
$db = Database::connect();
$sql = "
    INSERT IGNORE INTO notifications (user_id, booking_id, notification_type, message)
    SELECT DISTINCT recipients.user_id, b.booking_id, 'booking_reminder_30m',
           CONCAT('Booking #', b.booking_id, ' tại phòng ', r.room_code, ' sẽ bắt đầu trong 30 phút.')
    FROM bookings b
    INNER JOIN rooms r ON r.room_id = b.room_id
    INNER JOIN (
        SELECT booking_id, created_by_user_id AS user_id
        FROM bookings
        WHERE created_by_user_id IS NOT NULL
        UNION
        SELECT booking_id, user_id
        FROM bookings
        WHERE user_id IS NOT NULL
    ) recipients ON recipients.booking_id = b.booking_id
    WHERE b.status = 'approved'
      AND b.start_time > CURRENT_TIMESTAMP
      AND b.start_time <= DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 30 MINUTE)
";
$stmt = $db->prepare($sql);
$stmt->execute();
echo 'Inserted reminders: ' . $stmt->rowCount() . PHP_EOL;
