<?php

require_once __DIR__ . '/../../config/database.php';

class DashboardModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getStatistics(int $userId, array $roles): array
    {
        $isManager = in_array('admin', $roles, true)
            || in_array('manager', $roles, true);
        $isTeacher = in_array('teacher', $roles, true);
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
            $stats['rooms'] = (int)$this->db->query(
                "SELECT COUNT(*) FROM rooms"
            )->fetchColumn();
            $stats['active_rooms'] = (int)$this->db->query(
                "SELECT COUNT(*) FROM rooms WHERE status = 'active'"
            )->fetchColumn();
            $stats['equipment'] = (int)$this->db->query(
                "SELECT COUNT(*) FROM equipment"
            )->fetchColumn();
            $stats['available_equipment'] = (int)$this->db->query(
                "SELECT COUNT(*) FROM equipment WHERE status = 'available'"
            )->fetchColumn();
            $stats['pending_bookings'] = (int)$this->db->query(
                "SELECT COUNT(*) FROM bookings WHERE status = 'pending'"
            )->fetchColumn();
            $stats['maintenance_reports'] = (int)$this->db->query(
                "SELECT COUNT(*) FROM maintenance_reports"
            )->fetchColumn();
            $stats['pending_reports'] = (int)$this->db->query(
                "SELECT COUNT(*) FROM maintenance_reports
                 WHERE status IN ('reported', 'processing')"
            )->fetchColumn();
        } else {
            $stats['active_rooms'] = (int)$this->db->query(
                "SELECT COUNT(*) FROM rooms WHERE status = 'active'"
            )->fetchColumn();
        }

        if ($isTeacher) {
            $stmt = $this->db->prepare("
                SELECT
                    COUNT(*) AS total,
                    SUM(status = 'pending') AS pending
                FROM bookings
                WHERE user_id = :user_id
            ");
            $stmt->execute(['user_id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $stats['my_bookings'] = (int)($row['total'] ?? 0);
            if (!$isManager) {
                $stats['pending_bookings'] = (int)($row['pending'] ?? 0);
            }

            $stmt = $this->db->prepare("
                SELECT
                    COUNT(*) AS total,
                    SUM(status IN ('reported', 'processing')) AS pending
                FROM maintenance_reports
                WHERE reporter_id = :user_id
            ");
            $stmt->execute(['user_id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            if (!$isManager) {
                $stats['maintenance_reports'] = (int)($row['total'] ?? 0);
                $stats['pending_reports'] = (int)($row['pending'] ?? 0);
            }
        }

        if ($isTechnician && !$isManager) {
            $stmt = $this->db->prepare("
                SELECT
                    COUNT(*) AS total,
                    SUM(status IN ('reported', 'processing')) AS pending
                FROM maintenance_reports
                WHERE assigned_to = :user_id
            ");
            $stmt->execute(['user_id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $stats['maintenance_reports'] = (int)($row['total'] ?? 0);
            $stats['pending_reports'] = (int)($row['pending'] ?? 0);
        }

        return $stats;
    }

    /**
     * Manager/Admin xem toàn bộ; Teacher xem lịch đã duyệt và lịch của mình;
     * Technician chỉ xem lịch đã duyệt.
     */
    public function getRecentBookings(
        int $userId,
        bool $isManager,
        array $roles = []
    ): array {
        $isTeacher = in_array('teacher', $roles, true);
        $isTechnician = in_array('technician', $roles, true);

        $sql = "
            SELECT
                b.booking_id,
                b.title,
                b.start_time,
                b.end_time,
                b.status,
                b.booking_type,
                r.room_code,
                r.room_name,
                COALESCE(u.full_name, 'Hoạt động chung của nhà trường') AS full_name
            FROM bookings b
            INNER JOIN rooms r ON r.room_id = b.room_id
            LEFT JOIN users u ON u.user_id = b.user_id
        ";

        $params = [];

        if (!$isManager) {
            if ($isTeacher) {
                $sql .= " WHERE (b.status = 'approved' OR b.user_id = :user_id)";
                $params['user_id'] = $userId;
            } elseif ($isTechnician) {
                $sql .= " WHERE b.status = 'approved'";
            } else {
                $sql .= " WHERE 1 = 0";
            }
        }

        $sql .= " ORDER BY b.created_at DESC LIMIT 5";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Manager/Admin xem toàn bộ; Teacher xem phiếu do mình báo;
     * Technician xem phiếu được phân công.
     */
    public function getRecentReports(
        int $userId,
        bool $isManager,
        array $roles = []
    ): array {
        $isTeacher = in_array('teacher', $roles, true);
        $isTechnician = in_array('technician', $roles, true);

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
            INNER JOIN equipment e ON e.equipment_id = mr.equipment_id
            INNER JOIN users reporter ON reporter.user_id = mr.reporter_id
        ";

        $params = [];
        if (!$isManager) {
            if ($isTeacher) {
                $sql .= " WHERE mr.reporter_id = :user_id";
                $params['user_id'] = $userId;
            } elseif ($isTechnician) {
                $sql .= " WHERE mr.assigned_to = :user_id";
                $params['user_id'] = $userId;
            } else {
                $sql .= " WHERE 1 = 0";
            }
        }

        $sql .= " ORDER BY mr.reported_at DESC LIMIT 5";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
