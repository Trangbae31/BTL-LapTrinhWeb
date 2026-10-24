
<?php
require_once __DIR__ . '/../../config/database.php';

class BookingModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // Lấy danh sách booking kèm thông tin người đặt và phòng
    public function getAllBookings(): array
    {
        $sql = "
            SELECT
                b.booking_id,
                u.full_name AS user_name,
                r.room_code,
                r.room_name,
                b.title,
                b.purpose,
                b.start_time,
                b.end_time,
                b.status
            FROM bookings b
            INNER JOIN users u ON b.user_id = u.user_id
            INNER JOIN rooms r ON b.room_id = r.room_id
            ORDER BY b.start_time ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy danh sách phòng đang hoạt động
    public function getActiveRooms(): array
    {
        $sql = "
            SELECT room_id, room_code, room_name, capacity
            FROM rooms
            WHERE status = 'active'
            ORDER BY room_name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy booking đang chờ duyệt
    public function getPendingBookings(): array
    {
        $sql = "
            SELECT
                b.booking_id,
                u.full_name AS user_name,
                r.room_name,
                b.title,
                b.start_time,
                b.end_time
            FROM bookings b
            INNER JOIN users u ON b.user_id = u.user_id
            INNER JOIN rooms r ON b.room_id = r.room_id
            WHERE b.status = 'pending'
            ORDER BY b.start_time ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Kiểm tra phòng có booking bị trùng thời gian hay không
    public function hasOverlappingBooking(
        int $roomId,
        string $startTime,
        string $endTime
    ): bool {
        $sql = "
            SELECT COUNT(*)
            FROM bookings
            WHERE room_id = :room_id
              AND status IN ('pending', 'approved')
              AND start_time < :end_time
              AND end_time > :start_time
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'room_id' => $roomId,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);

    return (int) $stmt->fetchColumn() > 0;
    }

    // Tạo yêu cầu đặt phòng mới
    public function createBooking(
        int $userId,
        int $roomId,
        string $title,
        string $purpose,
        string $startTime,
        string $endTime
    ): bool {
        // Kiểm tra thời gian hợp lệ
        if (strtotime($startTime) === false ||
            strtotime($endTime) === false ||
            strtotime($startTime) >= strtotime($endTime)) {
            return false;
        }

        // Kiểm tra phòng có đang hoạt động không
        $roomSql = "
            SELECT COUNT(*)
            FROM rooms
            WHERE room_id = :room_id
            AND status = 'active'
        ";

        $roomStmt = $this->db->prepare($roomSql);
        $roomStmt->execute(['room_id' => $roomId]);

        if ((int) $roomStmt->fetchColumn() === 0) {
            return false;
        }

        // Không cho đặt trùng lịch
        if ($this->hasOverlappingBooking($roomId, $startTime, $endTime)) {
            return false;
        }

        // Lưu yêu cầu với trạng thái chờ duyệt
        $sql = "
            INSERT INTO bookings (
                user_id,
                room_id,
                title,
                purpose,
                start_time,
                end_time,
                status
            ) VALUES (
                :user_id,
                :room_id,
                :title,
                :purpose,
                :start_time,
                :end_time,
                'pending'
            )
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'user_id' => $userId,
            'room_id' => $roomId,
            'title' => $title,
            'purpose' => $purpose,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);
    }

}
