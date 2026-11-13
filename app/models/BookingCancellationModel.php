<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/NotificationModel.php';

class BookingCancellationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Giảng viên gửi yêu cầu hủy lịch đã được duyệt.
     */
    public function createRequest(
        int $bookingId,
        int $requesterId,
        string $reason
    ): bool {
        if ($reason === '') {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                SELECT booking_id, user_id, status, start_time
                FROM bookings
                WHERE booking_id = :booking_id
                FOR UPDATE
            ");

            $stmt->execute(['booking_id' => $bookingId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                $this->db->rollBack();
                return false;
            }

            // Không nhận yêu cầu hủy nếu lịch đã bắt đầu hoặc đã qua.
            if (strtotime($booking['start_time']) <= time()) {
                $this->db->rollBack();
                return false;
            }

            if (
                (int)$booking['user_id'] !== $requesterId ||
                $booking['status'] !== 'approved'
            ) {
                $this->db->rollBack();
                return false;
            }

            $check = $this->db->prepare("
                SELECT request_id
                FROM booking_cancellation_requests
                WHERE booking_id = :booking_id
                  AND status = 'pending'
                LIMIT 1
            ");

            $check->execute(['booking_id' => $bookingId]);

            if ($check->fetch(PDO::FETCH_ASSOC)) {
                $this->db->rollBack();
                return false;
            }

            $insert = $this->db->prepare("
                INSERT INTO booking_cancellation_requests (
                    booking_id,
                    requester_id,
                    reason,
                    status,
                    requested_at
                )
                VALUES (
                    :booking_id,
                    :requester_id,
                    :reason,
                    'pending',
                    CURRENT_TIMESTAMP
                )
            ");

            $insert->execute([
                'booking_id' => $bookingId,
                'requester_id' => $requesterId,
                'reason' => $reason
            ]);
            NotificationModel::notifyRoles($this->db, ['manager', 'admin'], 'booking_cancel_request',
                'Có yêu cầu hủy lịch đặt phòng cần xử lý (Booking #' . $bookingId . ').', $bookingId, null, $requesterId);

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
     * Lấy danh sách yêu cầu hủy.
     */
    public function getRequests(string $status = 'pending'): array
    {
        $sql = "
            SELECT
                cr.request_id,
                cr.booking_id,
                cr.requester_id,
                cr.reason,
                cr.status,
                cr.processed_by,
                cr.processing_comment,
                cr.requested_at,
                cr.processed_at,

                requester.full_name AS requester_name,
                processor.full_name AS processor_name,

                b.user_id,
                b.booking_type,
                b.title,
                b.start_time,
                b.end_time,
                b.status AS booking_status,

                r.room_code,
                r.room_name

            FROM booking_cancellation_requests cr

            INNER JOIN bookings b
                ON cr.booking_id = b.booking_id

            INNER JOIN users requester
                ON cr.requester_id = requester.user_id

            LEFT JOIN users processor
                ON cr.processed_by = processor.user_id

            INNER JOIN rooms r
                ON b.room_id = r.room_id
        ";

        $params = [];

        if ($status !== 'all') {
            $sql .= " WHERE cr.status = :status";
            $params['status'] = $status;
        }

        $sql .= "
            ORDER BY
                CASE WHEN cr.status = 'pending' THEN 0 ELSE 1 END,
                cr.requested_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Manager/Admin xử lý yêu cầu hủy.
     *
     * decision chỉ nhận approved hoặc rejected.
     */
    public function processRequest(
        int $requestId,
        int $processorId,
        string $decision,
        string $comment = ''
    ): bool {
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Lấy booking_id để khóa lịch trước.
            $find = $this->db->prepare("
                SELECT booking_id
                FROM booking_cancellation_requests
                WHERE request_id = :request_id
                LIMIT 1
            ");

            $find->execute(['request_id' => $requestId]);
            $requestInfo = $find->fetch(PDO::FETCH_ASSOC);

            if (!$requestInfo) {
                $this->db->rollBack();
                return false;
            }

            $bookingId = (int)$requestInfo['booking_id'];

            // Khóa lịch để tránh đồng thời bị hủy trực tiếp hoặc xử lý lần nữa.
            $bookingStmt = $this->db->prepare("
                SELECT booking_id, user_id, status, start_time
                FROM bookings
                WHERE booking_id = :booking_id
                FOR UPDATE
            ");

            $bookingStmt->execute(['booking_id' => $bookingId]);
            $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);

            // Khóa yêu cầu hủy.
            $requestStmt = $this->db->prepare("
                SELECT *
                FROM booking_cancellation_requests
                WHERE request_id = :request_id
                FOR UPDATE
            ");

            $requestStmt->execute(['request_id' => $requestId]);
            $request = $requestStmt->fetch(PDO::FETCH_ASSOC);

            if (
                !$booking ||
                !$request ||
                (int)$request['requester_id'] !== (int)$booking['user_id'] ||
                $request['status'] !== 'pending' ||
                $booking['status'] !== 'approved' ||
                strtotime($booking['start_time']) <= time()
            ) {
                $this->db->rollBack();
                return false;
            }

            if ($decision === 'approved') {
                // Chấp thuận hủy: lịch không còn chiếm phòng.
                $updateBooking = $this->db->prepare("
                    UPDATE bookings
                    SET status = 'cancelled',
                        cancelled_by_user_id = :processor_id,
                        cancelled_at = CURRENT_TIMESTAMP,
                        cancellation_reason = :reason
                    WHERE booking_id = :booking_id
                      AND status = 'approved'
                ");

                $updateBooking->execute([
                    'processor_id' => $processorId,
                    'reason' => $request['reason'],
                    'booking_id' => $bookingId
                ]);

                if ($updateBooking->rowCount() !== 1) {
                    $this->db->rollBack();
                    return false;
                }
            }

            // Từ chối: booking vẫn giữ trạng thái approved.
            $updateRequest = $this->db->prepare("
                UPDATE booking_cancellation_requests
                SET status = :status,
                    processed_by = :processor_id,
                    processing_comment = :comment,
                    processed_at = CURRENT_TIMESTAMP
                WHERE request_id = :request_id
                  AND status = 'pending'
            ");

            $updateRequest->execute([
                'status' => $decision,
                'processor_id' => $processorId,
                'comment' => $comment,
                'request_id' => $requestId
            ]);

            if ($updateRequest->rowCount() !== 1) {
                $this->db->rollBack();
                return false;
            }
            NotificationModel::create($this->db, (int)$request['requester_id'],
                $decision === 'approved' ? 'booking_cancel_approved' : 'booking_cancel_rejected',
                $decision === 'approved'
                    ? 'Yêu cầu hủy lịch của bạn đã được chấp thuận (Booking #' . $bookingId . ').'
                    : 'Yêu cầu hủy lịch của bạn đã bị từ chối (Booking #' . $bookingId . ').', $bookingId);

            $this->db->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
}
