
<?php

require_once __DIR__ . '/../../config/database.php';

class DashboardModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Trả về các chỉ số tổng quan.
     */
    public function getStatistics(int $userId, array $roles): array
    {
        $isManager = in_array('admin', $roles, true)
            || in_array('manager', $roles, true);

        $isTechnician = in_array('technician', $roles, true);

        $stats = [
            'rooms' => 0,
            'active_rooms' => 0,
            'equipment' => 0,
            'available_equipment' => 0,
            'pending_bookings' => 0,
            'my_bookings' => 0,
            'maintenance_reports' => 0,
            'pending_reports' => 0
        ];

        if ($isManager) {
            $stats['rooms'] = (int) $this->db->query("
                SELECT COUNT(*) FROM rooms
            ")->fetchColumn();

            $stats['active_rooms'] = (int) $this->db->query("
                SELECT COUNT(*) FROM rooms WHERE status = 'active'
            ")->fetchColumn();

            $stats['equipment'] = (int) $this->db->query("
                SELECT COUNT(*) FROM equipment
            ")->fetchColumn();

            $stats['available_equipment'] = (int) $this->db->query("
                SELECT COUNT(*)
                FROM equipment
                WHERE status = 'available'
            ")->fetchColumn();

            $stats['pending_bookings'] = (int) $this->db->query("
                SELECT COUNT(*)
                FROM bookings
                WHERE status = 'pending'
            ")->fetchColumn();

            $stats['maintenance_reports'] = (int) $this->db->query("
                SELECT COUNT(*)
                FROM maintenance_reports
            ")->fetchColumn();

            $stats['pending_reports'] = (int) $this->db->query("
                SELECT COUNT(*)
                FROM maintenance_reports
                WHERE status IN ('reported', 'processing')
            ")->fetchColumn();
        }

        if (in_array('teacher', $roles, true)) {
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM bookings
                WHERE user_id = :user_id
            ");

            $stmt->execute(['user_id' => $userId]);
            $stats['my_bookings'] = (int) $stmt->fetchColumn();

            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM bookings
                WHERE user_id = :user_id
                  AND status = 'pending'
            ");

            $stmt->execute(['user_id' => $userId]);
            $stats['pending_bookings'] = (int) $stmt->fetchColumn();

            $stats['active_rooms'] = (int) $this->db->query("
                SELECT COUNT(*)
                FROM rooms
                WHERE status = 'active'
            ")->fetchColumn();

            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM maintenance_reports
                WHERE reporter_id = :user_id
            ");

            $stmt->execute(['user_id' => $userId]);
            $stats['maintenance_reports'] = (int) $stmt->fetchColumn();
        }

        if ($isTechnician) {
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM maintenance_reports
                WHERE assigned_to = :user_id
            ");

            $stmt->execute(['user_id' => $userId]);
            $stats['maintenance_reports'] = (int) $stmt->fetchColumn();

            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM maintenance_reports
                WHERE assigned_to = :user_id
                  AND status IN ('reported', 'processing')
            ");

            $stmt->execute(['user_id' => $userId]);
            $stats['pending_reports'] = (int) $stmt->fetchColumn();
        }

        return $stats;
    }

    /**
     * Lấy các yêu cầu đặt phòng gần đây của người dùng.
     */
    public function getRecentBookings(int $userId, bool $isManager): array
    {
        $sql = "
            SELECT
                b.booking_id,
                b.title,
                b.start_time,
                b.end_time,
                b.status,
                r.room_code,
                r.room_name,
                u.full_name
            FROM bookings b
            INNER JOIN rooms r ON r.room_id = b.room_id
            INNER JOIN users u ON u.user_id = b.user_id
        ";

        if (!$isManager) {
            $sql .= " WHERE b.user_id = :user_id";
        }

        $sql .= "
            ORDER BY b.created_at DESC
            LIMIT 5
        ";

        $stmt = $this->db->prepare($sql);

        if (!$isManager) {
            $stmt->execute(['user_id' => $userId]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy các phiếu bảo trì gần đây.
     * Manager xem tất cả, technician chỉ xem phiếu được giao.
     */
    public function getRecentReports(
        int $userId,
        bool $isManager
    ): array {
        $sql = "
            SELECT
                mr.report_id,
                mr.title,
                mr.status,
                mr.reported_at,
                e.equipment_code,
                e.equipment_name,
                reporter.full_name AS reporter_name
            FROM maintenance_reports mr
            INNER JOIN equipment e
                ON e.equipment_id = mr.equipment_id
            INNER JOIN users reporter
                ON reporter.user_id = mr.reporter_id
        ";

        if (!$isManager) {
            $sql .= " WHERE mr.assigned_to = :user_id";
        }

        $sql .= "
            ORDER BY mr.reported_at DESC
            LIMIT 5
        ";

        $stmt = $this->db->prepare($sql);

        if (!$isManager) {
            $stmt->execute(['user_id' => $userId]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
