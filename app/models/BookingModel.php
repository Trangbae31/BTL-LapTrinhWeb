<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/NotificationModel.php';

class BookingModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Lấy danh sách lịch phòng.
     *
     * Manager/Admin: xem tất cả lịch.
     * Teacher: xem lịch đã duyệt và lịch của chính mình.
     * Technician: chỉ xem lịch đã duyệt.
     */
    public function getAllBookings(
        string $keyword = '',
        int $userId = 0,
        bool $canManage = false,
        bool $isTeacher = false
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
                b.cancelled_by_user_id,
                b.cancelled_at,
                b.cancellation_reason,

                COALESCE(u.full_name, 'Hoạt động chung') AS user_name,
                creator.full_name AS creator_name,

                r.room_code,
                r.room_name

            FROM bookings b

            LEFT JOIN users u
                ON b.user_id = u.user_id

            LEFT JOIN users creator
                ON b.created_by_user_id = creator.user_id

            INNER JOIN rooms r
                ON b.room_id = r.room_id

            WHERE (
                :can_manage = 1
                OR b.status = 'approved'
                OR (
                    :is_teacher = 1
                    AND b.user_id = :user_id
                )
            )

            AND (
                :keyword = ''
                OR r.room_code LIKE :search1
                OR r.room_name LIKE :search2
                OR b.title LIKE :search3
                OR b.purpose LIKE :search4
                OR u.full_name LIKE :search5
                OR creator.full_name LIKE :search6
            )

            ORDER BY b.start_time ASC
        ";

        $search = '%' . $keyword . '%';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'can_manage' => $canManage ? 1 : 0,
            'is_teacher' => $isTeacher ? 1 : 0,
            'user_id' => $userId,
            'keyword' => $keyword,
            'search1' => $search,
            'search2' => $search,
            'search3' => $search,
            'search4' => $search,
            'search5' => $search,
            'search6' => $search
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách phòng đang hoạt động.
     */
    public function getActiveRooms(): array
    {
        $stmt = $this->db->query("
            SELECT
                room_id,
                room_code,
                room_name,
                capacity,
                building_id
            FROM rooms
            WHERE status = 'active'
            ORDER BY room_name ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách giáo viên đang hoạt động.
     * Dùng khi Manager/Admin tạo lịch thay giáo viên.
     */
    public function getActiveTeachers(): array
    {
        $sql = "
            SELECT DISTINCT
                u.user_id,
                u.full_name,
                u.email
            FROM users u

            INNER JOIN user_roles ur
                ON u.user_id = ur.user_id

            INNER JOIN roles r
                ON ur.role_id = r.role_id

            WHERE u.status = 'active'
              AND r.role_name = 'teacher'

            ORDER BY u.full_name ASC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Kiểm tra một tài khoản có phải giáo viên đang hoạt động không.
     */
    public function isActiveTeacher(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM users u

            INNER JOIN user_roles ur
                ON u.user_id = ur.user_id

            INNER JOIN roles r
                ON ur.role_id = r.role_id

            WHERE u.user_id = :user_id
              AND u.status = 'active'
              AND r.role_name = 'teacher'
        ");

        $stmt->execute(['user_id' => $userId]);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Lấy thông tin một lịch đặt phòng.
     *
     * Nếu truyền ownerId > 0 thì chỉ lấy lịch của người đó.
     * Manager/Admin có thể truyền ownerId = 0 để lấy mọi lịch.
     */
    public function getBookingById(
        int $bookingId,
        int $ownerId = 0
    ): array|false {
        if ($bookingId <= 0) {
            return false;
        }

        $sql = "
            SELECT
                b.*,

                COALESCE(u.full_name, 'Hoạt động chung') AS user_name,
                creator.full_name AS creator_name,

                r.room_code,
                r.room_name,
                r.status AS room_status

            FROM bookings b

            LEFT JOIN users u
                ON b.user_id = u.user_id

            LEFT JOIN users creator
                ON b.created_by_user_id = creator.user_id

            INNER JOIN rooms r
                ON b.room_id = r.room_id

            WHERE b.booking_id = :booking_id
        ";

        $params = [
            'booking_id' => $bookingId
        ];

        if ($ownerId > 0) {
            $sql .= " AND b.user_id = :owner_id";
            $params['owner_id'] = $ownerId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Kiểm tra có lịch đã duyệt bị trùng thời gian hay không.
     *
     * Chỉ lịch approved mới chiếm phòng.
     *
     * Điều kiện trùng:
     * Lịch mới bắt đầu trước khi lịch cũ kết thúc
     * và lịch mới kết thúc sau khi lịch cũ bắt đầu.
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
              AND status = 'approved'
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
     * Khóa phòng và kiểm tra phòng còn hoạt động.
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

        $stmt->execute([
            'room_id' => $roomId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    /**
     * Kiểm tra thời gian đặt phòng.
     */
    private function isValidTime(
        string $startTime,
        string $endTime
    ): bool {
        $start = DateTime::createFromFormat(
            '!Y-m-d H:i:s',
            $startTime
        );

        $startErrors = DateTime::getLastErrors();

        $end = DateTime::createFromFormat(
            '!Y-m-d H:i:s',
            $endTime
        );

        $endErrors = DateTime::getLastErrors();

        $validStart = $start !== false
            && $start->format('Y-m-d H:i:s') === $startTime
            && (
                $startErrors === false
                || (
                    $startErrors['warning_count'] === 0
                    && $startErrors['error_count'] === 0
                )
            );

        $validEnd = $end !== false
            && $end->format('Y-m-d H:i:s') === $endTime
            && (
                $endErrors === false
                || (
                    $endErrors['warning_count'] === 0
                    && $endErrors['error_count'] === 0
                )
            );

        if (!$validStart || !$validEnd) {
            return false;
        }

        return $start < $end
            && $start->getTimestamp() > time();
    }

    /**
     * Kiểm tra dữ liệu chung của lịch.
     */
    private function isValidBookingData(
        int $roomId,
        string $title,
        string $purpose,
        string $startTime,
        string $endTime
    ): bool {
        return $roomId > 0
            && trim($title) !== ''
            && mb_strlen(trim($title), 'UTF-8') <= 200
            && trim($purpose) !== ''
            && $this->isValidTime($startTime, $endTime);
    }

    /**
     * Tạo lịch đặt phòng.
     *
     * personal:
     * - Giáo viên tạo lịch cá nhân.
     * - Trạng thái pending.
     *
     * on_behalf:
     * - Manager/Admin tạo lịch thay giáo viên.
     * - Trạng thái approved.
     *
     * school_wide:
     * - Manager/Admin tạo lịch hoạt động chung.
     * - user_id được để NULL.
     * - Trạng thái approved.
     */
    public function createBooking(
        ?int $bookingUserId,
        int $createdByUserId,
        int $roomId,
        string $bookingType,
        string $title,
        string $purpose,
        string $startTime,
        string $endTime
    ): bool {
        $allowedTypes = [
            'personal',
            'on_behalf',
            'school_wide'
        ];

        if (
            $createdByUserId <= 0
            || !in_array($bookingType, $allowedTypes, true)
            || !$this->isValidBookingData(
                $roomId,
                $title,
                $purpose,
                $startTime,
                $endTime
            )
        ) {
            return false;
        }

        // Kiểm tra người sử dụng phòng theo loại lịch.
        if ($bookingType === 'personal') {
            if (
                $bookingUserId === null
                || $bookingUserId !== $createdByUserId
            ) {
                return false;
            }
        }

        if ($bookingType === 'on_behalf') {
            if (
                $bookingUserId === null
                || !$this->isActiveTeacher($bookingUserId)
            ) {
                return false;
            }
        }

        if ($bookingType === 'school_wide') {
            $bookingUserId = null;
        }

        // Lịch cá nhân cần duyệt.
        // Lịch do Manager/Admin tạo được duyệt ngay.
        $status = $bookingType === 'personal'
            ? 'pending'
            : 'approved';

        try {
            $this->db->beginTransaction();

            // Khóa phòng để tránh hai thao tác đồng thời
            // cùng kiểm tra một phòng rồi tạo lịch trùng.
            if (!$this->lockActiveRoom($roomId)) {
                $this->db->rollBack();
                return false;
            }

            // Kiểm tra lịch approved đang chiếm phòng.
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
                    created_by_user_id,
                    booking_type,
                    room_id,
                    title,
                    purpose,
                    start_time,
                    end_time,
                    status
                )
                VALUES (
                    :user_id,
                    :created_by_user_id,
                    :booking_type,
                    :room_id,
                    :title,
                    :purpose,
                    :start_time,
                    :end_time,
                    :status
                )
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                'user_id' => $bookingUserId,
                'created_by_user_id' => $createdByUserId,
                'booking_type' => $bookingType,
                'room_id' => $roomId,
                'title' => trim($title),
                'purpose' => trim($purpose),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => $status
            ]);

            $bookingId = (int)$this->db->lastInsertId();

            // Lịch do Manager/Admin tạo được duyệt tự động,
            // nhưng vẫn phải có bản ghi để truy vết người tạo/duyệt.
            if ($status === 'approved') {
                $history = $this->db->prepare("
                    INSERT INTO booking_approvals (
                        booking_id, reviewer_id, action, comment
                    )
                    VALUES (
                        :booking_id, :reviewer_id, 'approved', :comment
                    )
                ");

                $history->execute([
                    'booking_id' => $bookingId,
                    'reviewer_id' => $createdByUserId,
                    'comment' => 'Lịch được Manager/Admin tạo và tự động xác nhận.'
                ]);
                if ($bookingUserId !== null && $bookingUserId !== $createdByUserId) {
                    NotificationModel::create($this->db, $bookingUserId, 'booking_created_for_you',
                        'Quản lý đã tạo và xác nhận lịch đặt phòng cho bạn (Booking #' . $bookingId . ').', $bookingId);
                }
            } else {
                NotificationModel::notifyRoles($this->db, ['manager', 'admin'], 'booking_pending',
                    'Có yêu cầu đặt phòng mới cần phê duyệt (Booking #' . $bookingId . ').', $bookingId, null, $createdByUserId);
            }

            $this->db->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log(
                'Create booking error: ' . $e->getMessage()
            );

            return false;
        }
    }

    /**
     * Giáo viên sửa lịch pending của chính mình.
     *
     * Không có tham số isAdmin vì Manager/Admin
     * không được sửa lịch pending.
     *
     * Lịch approved cũng không được sửa.
     */
    public function updatePendingBooking(
        int $bookingId,
        int $userId,
        int $roomId,
        string $title,
        string $purpose,
        string $startTime,
        string $endTime
    ): bool {
        if (
            $bookingId <= 0
            || $userId <= 0
            || !$this->isValidBookingData(
                $roomId,
                $title,
                $purpose,
                $startTime,
                $endTime
            )
        ) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Khóa lịch trước khi kiểm tra trạng thái.
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

            // Chỉ được sửa lịch pending của chính mình.
            if (
                !$booking
                || $booking['status'] !== 'pending'
            ) {
                $this->db->rollBack();
                return false;
            }

            // Kiểm tra phòng mới còn hoạt động.
            if (!$this->lockActiveRoom($roomId)) {
                $this->db->rollBack();
                return false;
            }

            // Không cho phép lịch sau khi sửa bị trùng
            // với một lịch đã duyệt khác.
            if ($this->hasOverlappingBooking(
                $roomId,
                $startTime,
                $endTime,
                $bookingId
            )) {
                $this->db->rollBack();
                return false;
            }

            $update = $this->db->prepare("
                UPDATE bookings

                SET room_id = :room_id,
                    title = :title,
                    purpose = :purpose,
                    start_time = :start_time,
                    end_time = :end_time

                WHERE booking_id = :booking_id
                  AND user_id = :user_id
                  AND status = 'pending'
            ");

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

            error_log(
                'Update pending booking error: ' . $e->getMessage()
            );

            return false;
        }
    }

    /**
     * Hủy trực tiếp lịch.
     *
     * Teacher:
     * - Chỉ hủy pending của chính mình.
     *
     * Manager/Admin:
     * - Được hủy pending hoặc approved của mọi người.
     *
     * Teacher không được hủy trực tiếp lịch approved.
     * Phải gửi yêu cầu hủy qua BookingCancellationModel.
     */
    public function cancelBooking(
        int $bookingId,
        int $actorId,
        string $actorRole,
        string $reason
    ): bool {
        if (
            $bookingId <= 0
            || $actorId <= 0
            || trim($reason) === ''
        ) {
            return false;
        }

        $isManager = in_array(
            $actorRole,
            ['manager', 'admin'],
            true
        );

        if (!in_array(
            $actorRole,
            ['teacher', 'manager', 'admin'],
            true
        )) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Khóa lịch để tránh xử lý đồng thời.
            $sql = "
                SELECT booking_id, user_id, status, start_time
                FROM bookings
                WHERE booking_id = :booking_id
                FOR UPDATE
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'booking_id' => $bookingId
            ]);

            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                $this->db->rollBack();
                return false;
            }

            // Không cho hủy khi lịch đã bắt đầu hoặc đã qua.
            if (strtotime($booking['start_time']) <= time()) {
                $this->db->rollBack();
                return false;
            }

            if ($isManager) {
                // Manager/Admin được hủy pending hoặc approved.
                if (!in_array(
                    $booking['status'],
                    ['pending', 'approved'],
                    true
                )) {
                    $this->db->rollBack();
                    return false;
                }
            } else {
                // Giáo viên chỉ được hủy pending của mình.
                if (
                    (int)$booking['user_id'] !== $actorId
                    || $booking['status'] !== 'pending'
                ) {
                    $this->db->rollBack();
                    return false;
                }
            }

            $update = $this->db->prepare("
                UPDATE bookings

                SET status = 'cancelled',
                    cancelled_by_user_id = :actor_id,
                    cancelled_at = CURRENT_TIMESTAMP,
                    cancellation_reason = :reason

                WHERE booking_id = :booking_id
                  AND status IN ('pending', 'approved')
            ");

            $update->execute([
                'actor_id' => $actorId,
                'reason' => trim($reason),
                'booking_id' => $bookingId
            ]);

            if ($update->rowCount() !== 1) {
                $this->db->rollBack();
                return false;
            }

            if ($isManager && (int)($booking['user_id'] ?? 0) > 0 && (int)$booking['user_id'] !== $actorId) {
                NotificationModel::create($this->db, (int)$booking['user_id'], 'booking_cancelled',
                    'Lịch đặt phòng của bạn đã bị hủy bởi quản lý (Booking #' . $bookingId . ').', $bookingId);
            }

            // Nếu có yêu cầu hủy đang chờ xử lý,
            // đóng yêu cầu đó vì lịch đã được hủy trực tiếp.
            $closeRequests = $this->db->prepare("
                UPDATE booking_cancellation_requests

                SET status = 'rejected',
                    processed_by = :actor_id,
                    processing_comment = :comment,
                    processed_at = CURRENT_TIMESTAMP

                WHERE booking_id = :booking_id
                  AND status = 'pending'
            ");

            $closeRequests->execute([
                'actor_id' => $actorId,
                'comment' => 'Yêu cầu được đóng vì lịch đã bị hủy trực tiếp.',
                'booking_id' => $bookingId
            ]);

            $this->db->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log(
                'Cancel booking error: ' . $e->getMessage()
            );

            return false;
        }
    }
}