<?php

require_once __DIR__ . '/../../config/database.php';

class BookingModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // Lấy danh sách lịch phòng, có tìm kiếm
    public function getAllBookings(string $keyword = ''): array
    {
        $sql = "
            SELECT
                b.booking_id,
                b.user_id,
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
            WHERE (
                :keyword = ''
                OR r.room_code LIKE :search1
                OR r.room_name LIKE :search2
                OR b.title LIKE :search3
                OR b.purpose LIKE :search4
                OR u.full_name LIKE :search5
            )
            ORDER BY b.start_time ASC
        ";

        $stmt = $this->db->prepare($sql);
       $search = '%' . $keyword . '%';

        $stmt->execute([
            'keyword' => $keyword,
            'search1' => $search,
            'search2' => $search,
            'search3' => $search,
            'search4' => $search,
            'search5' => $search
        ]);

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

    // Lấy một yêu cầu đặt phòng của đúng người dùng
    public function getBookingById(int $bookingId, int $userId): array|false
    {
        $sql = "
            SELECT *
            FROM bookings
            WHERE booking_id = :booking_id
              AND user_id = :user_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'booking_id' => $bookingId,
            'user_id' => $userId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Kiểm tra trùng lịch
    public function hasOverlappingBooking(
        int $roomId,
        string $startTime,
        string $endTime,
        int $excludeBookingId = 0
    ): bool {
        $sql = "
            SELECT COUNT(*)
            FROM bookings
            WHERE room_id = :room_id
              AND status IN ('pending', 'approved')
              AND start_time < :end_time
              AND end_time > :start_time
              AND booking_id != :exclude_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'room_id' => $roomId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'exclude_id' => $excludeBookingId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    // Tạo yêu cầu đặt phòng
    public function createBooking(
        int $userId,
        int $roomId,
        string $title,
        string $purpose,
        string $startTime,
        string $endTime
    ): bool {
        if (
            strtotime($startTime) === false ||
            strtotime($endTime) === false ||
            strtotime($startTime) >= strtotime($endTime)
        ) {
            return false;
        }

        $roomStmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM rooms
            WHERE room_id = :room_id
              AND status = 'active'
        ");
        $roomStmt->execute(['room_id' => $roomId]);

        if ((int) $roomStmt->fetchColumn() === 0) {
            return false;
        }

        if ($this->hasOverlappingBooking($roomId, $startTime, $endTime)) {
            return false;
        }

        $sql = "
            INSERT INTO bookings (
                user_id, room_id, title, purpose,
                start_time, end_time, status
            )
            VALUES (
                :user_id, :room_id, :title, :purpose,
                :start_time, :end_time, 'pending'
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

    // Cập nhật yêu cầu đang chờ duyệt
    public function updateBooking(
        int $bookingId,
        int $userId,
        int $roomId,
        string $title,
        string $purpose,
        string $startTime,
        string $endTime
    ): bool {
        if (
            strtotime($startTime) === false ||
            strtotime($endTime) === false ||
            strtotime($startTime) >= strtotime($endTime)
        ) {
            return false;
        }

        $booking = $this->getBookingById($bookingId, $userId);

        if (!$booking || $booking['status'] !== 'pending') {
            return false;
        }

        $roomStmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM rooms
            WHERE room_id = :room_id
              AND status = 'active'
        ");
        $roomStmt->execute(['room_id' => $roomId]);

        if ((int) $roomStmt->fetchColumn() === 0) {
            return false;
        }

        if ($this->hasOverlappingBooking(
            $roomId,
            $startTime,
            $endTime,
            $bookingId
        )) {
            return false;
        }

        $sql = "
            UPDATE bookings
            SET room_id = :room_id,
                title = :title,
                purpose = :purpose,
                start_time = :start_time,
                end_time = :end_time
            WHERE booking_id = :booking_id
              AND user_id = :user_id
              AND status = 'pending'
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'room_id' => $roomId,
            'title' => $title,
            'purpose' => $purpose,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'booking_id' => $bookingId,
            'user_id' => $userId
        ]);
    }

    // Hủy yêu cầu đang chờ duyệt
    public function cancelBooking(int $bookingId, int $userId): bool
    {
        $sql = "
            UPDATE bookings
            SET status = 'cancelled'
            WHERE booking_id = :booking_id
              AND user_id = :user_id
              AND status = 'pending'
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'booking_id' => $bookingId,
            'user_id' => $userId
        ]);

        return $stmt->rowCount() > 0;
    }
}