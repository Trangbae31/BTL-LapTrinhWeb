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
     * Lấy danh sách yêu cầu đặt phòng.
     * Manager xem tất cả; có thể lọc theo trạng thái.
     */
    public function getBookings(string $status = 'pending'): array
    {
        $sql = "
            SELECT
                b.booking_id,
                b.user_id,
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
                r.room_code,
                r.room_name,
                r.capacity,
                bu.building_name
            FROM bookings b
            INNER JOIN users u ON b.user_id = u.user_id
            INNER JOIN rooms r ON b.room_id = r.room_id
            LEFT JOIN buildings bu ON r.building_id = bu.building_id
        ";

        $params = [];

        if ($status !== 'all') {
            $sql .= " WHERE b.status = :status";
            $params['status'] = $status;
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
     * Lấy thông tin chi tiết một yêu cầu đặt phòng.
     */
    public function getBookingById(int $bookingId): array|false
    {
        $sql = "
            SELECT
                b.*,
                u.full_name AS requester_name,
                u.email AS requester_email,
                r.room_code,
                r.room_name,
                r.capacity,
                bu.building_name
            FROM bookings b
            INNER JOIN users u ON b.user_id = u.user_id
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
     * Kiểm tra xem phòng có yêu cầu đã duyệt nào bị trùng giờ không.
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

            if ($this->hasApprovedOverlap(
                (int)$booking['room_id'],
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