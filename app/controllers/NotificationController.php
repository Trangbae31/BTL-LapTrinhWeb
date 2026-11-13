<?php
require_once __DIR__ . '/../core/Authorization.php';
require_once __DIR__ . '/../core/CSRF.php';
require_once __DIR__ . '/../../config/database.php';

class NotificationController
{
    private PDO $db;
    public function __construct() { $this->db = Database::connect(); }

    public function index(): void
    {
        Authorization::requireLogin();
        $stmt = $this->db->prepare("
            SELECT n.notification_id, n.booking_id, n.message, n.created_at, n.read_at,
                   b.start_time, b.end_time, r.room_code, r.room_name
            FROM notifications n
            INNER JOIN bookings b ON b.booking_id = n.booking_id
            INNER JOIN rooms r ON r.room_id = b.room_id
            WHERE n.user_id = :user_id
            ORDER BY n.created_at DESC
            LIMIT 100
        ");
        $stmt->execute(['user_id' => Authorization::userId()]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($notifications as &$notification) $notification['notification_source'] = 'reminder';
        unset($notification);

        $eventStmt = $this->db->prepare("
            SELECT en.event_notification_id AS notification_id, en.booking_id, en.report_id,
                   en.notification_type, en.message, en.created_at, en.read_at,
                   b.start_time, b.end_time, r.room_code, r.room_name,
                   mr.title AS report_title, e.equipment_code, e.equipment_name,
                   'event' AS notification_source
            FROM event_notifications en
            LEFT JOIN bookings b ON b.booking_id = en.booking_id
            LEFT JOIN rooms r ON r.room_id = b.room_id
            LEFT JOIN maintenance_reports mr ON mr.report_id = en.report_id
            LEFT JOIN equipment e ON e.equipment_id = mr.equipment_id
            WHERE en.user_id = :user_id
            ORDER BY en.created_at DESC, en.event_notification_id DESC
            LIMIT 100
        ");
        $eventStmt->execute(['user_id' => Authorization::userId()]);
        $notifications = array_merge($notifications, $eventStmt->fetchAll(PDO::FETCH_ASSOC));
        usort($notifications, static fn(array $a, array $b): int => strcmp((string)$b['created_at'], (string)$a['created_at']));
        $notifications = array_slice($notifications, 0, 100);
        require __DIR__ . '/../views/notifications.php';
    }

    public function markRead(): void
    {
        Authorization::requireLogin();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405); exit('Phương thức yêu cầu không hợp lệ.');
        }
        CSRF::verifyOrFail();
        $id = filter_var($_POST['notification_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) { http_response_code(400); exit('Thông báo không hợp lệ.'); }
        $source = ($_POST['notification_source'] ?? 'reminder') === 'event' ? 'event' : 'reminder';
        if ($source === 'event') {
            $stmt = $this->db->prepare("UPDATE event_notifications SET read_at = COALESCE(read_at, CURRENT_TIMESTAMP) WHERE event_notification_id = :id AND user_id = :user_id");
        } else {
            $stmt = $this->db->prepare("UPDATE notifications SET read_at = COALESCE(read_at, CURRENT_TIMESTAMP) WHERE notification_id = :id AND user_id = :user_id");
        }
        $stmt->execute(['id' => $id, 'user_id' => Authorization::userId()]);
        header('Location: index.php?action=notifications'); exit();
    }
}
