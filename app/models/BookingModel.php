<?php

require_once __DIR__ . '/../../config/database.php';

class BookingModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Lấy lịch phòng.
     * Hiển thị lịch đã duyệt và yêu cầu của chính người dùng.
     */
    public function getAllBookings(
        string $keyword = '',
        int $userId = 0
    ): array {
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
                b.status = 'approved'
                OR (:user_id > 0 AND b.user_id = :user_id_2)
            )
            AND (
                :keyword = ''
                OR r.room_code LIKE :search1
                OR r.room_name LIKE :search2
                OR b.title LIKE :search3
                OR b.purpose LIKE :search4
                OR u.full_name LIKE :search5
            )
            ORDER BY b.start_time ASC
        ";

        $search = '%' . $keyword . '%';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'user_id' => $userId,
            'user_id_2' => $userId,
            'keyword' => $keyword,
            'search1' => $search,
            'search2' => $search,
            'search3' => $search,
            'search4' => $search,
            'search5' => $search
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách phòng đang hoạt động.
     */
    public function getActiveRooms(): array
    {
        $sql = "
            SELECT
                room_id,
                room_code,
                room_name,
                capacity
            FROM rooms
            WHERE status = 'active'
            ORDER BY room_name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy yêu cầu đặt phòng của đúng người dùng.
     */
    public function getBookingById(
        int $bookingId,
        int $userId
    ): array|false {
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

    /**
     * Kiểm tra phòng có lịch bị trùng không.
     * Hai khoảng thời gian trùng nếu:
     * Lịch cũ bắt đầu trước khi lịch mới kết thúc
     * và lịch cũ kết thúc sau khi lịch mới bắt đầu.
     */
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
              AND booking_id <> :exclude_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'room_id' => $roomId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'exclude_id' => $excludeBookingId
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Khóa và kiểm tra phòng đang hoạt động.
     * Phải được gọi bên trong transaction.
     */
    private function lockActiveRoom(int $roomId): bool
    {
        $stmt = $this->db->prepare("
            SELECT room_id
            FROM rooms
            WHERE room_id = :room_id
              AND status = 'active'
            FOR UPDATE
        ");

        $stmt->execute(['room_id' => $roomId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    /**
     * Kiểm tra khoảng thời gian hợp lệ.
     */
    private function isValidTime(
        string $startTime,
        string $endTime
    ): bool {
        $start = strtotime($startTime);
        $end = strtotime($endTime);

        return $start !== false
            && $end !== false
            && $start < $end;
    }

    /**
     * Tạo yêu cầu đặt phòng.
     */
    public function createBooking(
        int $userId,
        int $roomId,
        string $title,
        string $purpose,
        string $startTime,
        string $endTime
    ): bool {
        if (
            $userId <= 0 ||
            $roomId <= 0 ||
            trim($title) === '' ||
            trim($purpose) === '' ||
            !$this->isValidTime($startTime, $endTime)
        ) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Khóa phòng để tránh xử lý đồng thời gây đặt trùng.
            if (!$this->lockActiveRoom($roomId)) {
                $this->db->rollBack();
                return false;
            }

            if ($this->hasOverlappingBooking(
                $roomId,
                $startTime,
                $endTime
            )) {
                $this->db->rollBack();
                return false;
            }

            $sql = "
                INSERT INTO bookings (
                    user_id,
                    room_id,
                    title,
                    purpose,
                    start_time,
                    end_time,
                    status
                )
                VALUES (
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

            $stmt->execute([
                'user_id' => $userId,
                'room_id' => $roomId,
                'title' => trim($title),
                'purpose' => trim($purpose),
                'start_time' => $startTime,
                'end_time' => $endTime
            ]);

            $this->db->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('Create booking error: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Cập nhật yêu cầu đang chờ duyệt.
     */
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
            $bookingId <= 0 ||
            $userId <= 0 ||
            $roomId <= 0 ||
            trim($title) === '' ||
            trim($purpose) === '' ||
            !$this->isValidTime($startTime, $endTime)
        ) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                SELECT booking_id, status
                FROM bookings
                WHERE booking_id = :booking_id
                  AND user_id = :user_id
                FOR UPDATE
            ");

            $stmt->execute([
                'booking_id' => $bookingId,
                'user_id' => $userId
            ]);

            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking || $booking['status'] !== 'pending') {
                $this->db->rollBack();
                return false;
            }

            if (!$this->lockActiveRoom($roomId)) {
                $this->db->rollBack();
                return false;
            }

            if ($this->hasOverlappingBooking(
                $roomId,
                $startTime,
                $endTime,
                $bookingId
            )) {
                $this->db->rollBack();
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

            $update = $this->db->prepare($sql);

            $update->execute([
                'room_id' => $roomId,
                'title' => trim($title),
                'purpose' => trim($purpose),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'booking_id' => $bookingId,
                'user_id' => $userId
            ]);

            $this->db->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('Update booking error: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Hủy yêu cầu đang chờ duyệt.
     */
    public function cancelBooking(
        int $bookingId,
        int $userId
    ): bool {
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