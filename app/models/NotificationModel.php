<?php
/** Helper for business-event notifications. The legacy notifications table is untouched. */
class NotificationModel
{
    public static function create(PDO $db, int $userId, string $type, string $message, ?int $bookingId = null, ?int $reportId = null): void
    {
        if ($userId <= 0) return;
        $stmt = $db->prepare("INSERT INTO event_notifications (user_id, booking_id, report_id, notification_type, message) VALUES (:user_id, :booking_id, :report_id, :type, :message)");
        $stmt->execute([
            'user_id' => $userId, 'booking_id' => $bookingId, 'report_id' => $reportId,
            'type' => $type, 'message' => mb_substr($message, 0, 255, 'UTF-8')
        ]);
    }

    public static function notifyRoles(PDO $db, array $roles, string $type, string $message, ?int $bookingId = null, ?int $reportId = null, int $excludeUserId = 0): void
    {
        if (!$roles) return;
        $marks = implode(',', array_fill(0, count($roles), '?'));
        $sql = "SELECT DISTINCT u.user_id FROM users u INNER JOIN user_roles ur ON ur.user_id=u.user_id INNER JOIN roles r ON r.role_id=ur.role_id WHERE u.status='active' AND r.role_name IN ($marks)";
        $params = array_values($roles);
        if ($excludeUserId > 0) { $sql .= ' AND u.user_id <> ?'; $params[] = $excludeUserId; }
        $stmt = $db->prepare($sql); $stmt->execute($params);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) self::create($db, (int)$id, $type, $message, $bookingId, $reportId);
    }
}
