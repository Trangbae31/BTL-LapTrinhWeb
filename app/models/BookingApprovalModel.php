<?php

require_once __DIR__ . '/../../config/database.php';

class BookingApprovalModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Lấy danh sách yêu cầu đặt phòng theo trạng thái.
     */
    public function getBookings(
        string $status = 'pending',
        int $roomId = 0,
        int $requesterId = 0,
        string $dateFrom = '',
        string $dateTo = ''
    ): array {
        $sql = "
            SELECT
                b.booking_id,
                b.user_id,
                b.created_by_user_id,
                b.booking_type,
                b.room_id,
                b.title,
                b.purpose,
                b.start_time,
                b.end_time,
                b.status,
                b.note,
                b.created_at,
                u.full_name AS requester_name,
                u.email AS requester_email,
                creator.full_name AS creator_name,
                r.room_code,
                r.room_name,
                r.capacity,
                bu.building_name
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.user_id
            LEFT JOIN users creator ON b.created_by_user_id = creator.user_id
            INNER JOIN rooms r ON b.room_id = r.room_id
            LEFT JOIN buildings bu ON r.building_id = bu.building_id
        ";

        $conditions = [];
        $params = [];

        if ($status !== 'all') {
            $conditions[] = 'b.status = :status';
            $params['status'] = $status;
        }
        if ($roomId > 0) {
            $conditions[] = 'b.room_id = :room_id';
            $params['room_id'] = $roomId;
        }
        if ($requesterId > 0) {
            $conditions[] = 'b.user_id = :requester_id';
            $params['requester_id'] = $requesterId;
        }
        // Lọc theo khoảng giao nhau với toàn bộ ngày [date_from, date_to].
        if ($dateFrom !== '') {
            $conditions[] = 'b.end_time >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo !== '') {
            $conditions[] = 'b.start_time < DATE_ADD(:date_to, INTERVAL 1 DAY)';
            $params['date_to'] = $dateTo;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= "
            ORDER BY
                CASE WHEN b.status = 'pending' THEN 0 ELSE 1 END,
                b.start_time ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Dữ liệu cho các danh sách chọn trong bộ lọc.
     */
    public function getBookingFilterOptions(): array
    {
        $rooms = $this->db->query("
            SELECT room_id, room_code, room_name
            FROM rooms
            ORDER BY room_code ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $requesters = $this->db->query("
            SELECT DISTINCT u.user_id, u.full_name, u.email
            FROM users u
            INNER JOIN bookings b ON b.user_id = u.user_id
            ORDER BY u.full_name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        return [
            'rooms' => $rooms,
            'requesters' => $requesters
        ];
    }

    /**
     * Lấy thông tin chi tiết một yêu cầu đặt phòng.
     */
    public function getBookingById(int $bookingId): array|false
    {
        $sql = "
            SELECT
                b.*,
                u.full_name AS requester_name,
                u.email AS requester_email,
                creator.full_name AS creator_name,
                r.room_code,
                r.room_name,
                r.capacity,
                bu.building_name
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.user_id
            LEFT JOIN users creator ON b.created_by_user_id = creator.user_id
            INNER JOIN rooms r ON b.room_id = r.room_id
            LEFT JOIN buildings bu ON r.building_id = bu.building_id
            WHERE b.booking_id = :booking_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['booking_id' => $bookingId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Kiểm tra lịch đã duyệt có trùng thời gian hay không.
     */
    private function hasApprovedOverlap(
        int $roomId,
        string $startTime,
        string $endTime,
        int $excludeBookingId
    ): bool {
        $sql = "
            SELECT COUNT(*)
            FROM bookings
            WHERE room_id = :room_id
              AND status = 'approved'
              AND start_time < :end_time
              AND end_time > :start_time
              AND booking_id <> :booking_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'room_id' => $roomId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'booking_id' => $excludeBookingId
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Khóa phòng và kiểm tra phòng còn hoạt động.
     */
    private function lockActiveRoom(int $roomId): bool
    {
        $stmt = $this->db->prepare("
            SELECT status
            FROM rooms
            WHERE room_id = :room_id
            FOR UPDATE
        ");

        $stmt->execute(['room_id' => $roomId]);

        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        return $room !== false && $room['status'] === 'active';
    }

    /**
     * Duyệt yêu cầu đặt phòng.
     */
    public function approve(
        int $bookingId,
        int $reviewerId,
        string $comment = ''
    ): bool {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                SELECT *
                FROM bookings
                WHERE booking_id = :booking_id
                FOR UPDATE
            ");

            $stmt->execute(['booking_id' => $bookingId]);

            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking || $booking['status'] !== 'pending') {
                $this->db->rollBack();
                return false;
            }

            // Người tạo/người sử dụng booking không được tự duyệt booking của mình.
            if (
                (int)($booking['created_by_user_id'] ?? 0) === $reviewerId
                || (int)($booking['user_id'] ?? 0) === $reviewerId
            ) {
                $this->db->rollBack();
                return false;
            }

            // Không duyệt lịch đã bắt đầu hoặc đã qua.
            if (strtotime($booking['start_time']) <= time()) {
                $this->db->rollBack();
                return false;
            }

            $roomId = (int)$booking['room_id'];

            if (!$this->lockActiveRoom($roomId)) {
                $this->db->rollBack();
                return false;
            }

            if ($this->hasApprovedOverlap(
                $roomId,
                $booking['start_time'],
                $booking['end_time'],
                $bookingId
            )) {
                $this->db->rollBack();
                return false;
            }

            $update = $this->db->prepare("
                UPDATE bookings
                SET status = 'approved',
                    note = :note
                WHERE booking_id = :booking_id
                  AND status = 'pending'
            ");

            $update->execute([
                'note' => $comment,
                'booking_id' => $bookingId
            ]);

            if ($update->rowCount() !== 1) {
                $this->db->rollBack();
                return false;
            }

            $history = $this->db->prepare("
                INSERT INTO booking_approvals (
                    booking_id,
                    reviewer_id,
                    action,
                    comment
                )
                VALUES (
                    :booking_id,
                    :reviewer_id,
                    'approved',
                    :comment
                )
            ");

            $history->execute([
                'booking_id' => $bookingId,
                'reviewer_id' => $reviewerId,
                'comment' => $comment
            ]);

            $this->db->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Từ chối yêu cầu đặt phòng.
     */
    public function reject(
        int $bookingId,
        int $reviewerId,
        string $comment
    ): bool {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                SELECT booking_id, status
                FROM bookings
                WHERE booking_id = :booking_id
                FOR UPDATE
            ");

            $stmt->execute(['booking_id' => $bookingId]);

            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking || $booking['status'] !== 'pending') {
                $this->db->rollBack();
                return false;
            }

            $update = $this->db->prepare("
                UPDATE bookings
                SET status = 'rejected',
                    note = :note
                WHERE booking_id = :booking_id
                  AND status = 'pending'
            ");

            $update->execute([
                'note' => $comment,
                'booking_id' => $bookingId
            ]);

            if ($update->rowCount() !== 1) {
                $this->db->rollBack();
                return false;
            }

            $history = $this->db->prepare("
                INSERT INTO booking_approvals (
                    booking_id,
                    reviewer_id,
                    action,
                    comment
                )
                VALUES (
                    :booking_id,
                    :reviewer_id,
                    'rejected',
                    :comment
                )
            ");

            $history->execute([
                'booking_id' => $bookingId,
                'reviewer_id' => $reviewerId,
                'comment' => $comment
            ]);

            $this->db->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Lấy lịch sử duyệt của một yêu cầu.
     */
    public function getApprovalHistory(int $bookingId): array
    {
        $sql = "
            SELECT
                ba.approval_id,
                ba.action,
                ba.comment,
                ba.reviewed_at,
                u.full_name AS reviewer_name
            FROM booking_approvals ba
            INNER JOIN users u ON ba.reviewer_id = u.user_id
            WHERE ba.booking_id = :booking_id
            ORDER BY ba.reviewed_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['booking_id' => $bookingId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
