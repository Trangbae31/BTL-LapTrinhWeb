<?php

require_once __DIR__ . '/../models/BookingModel.php';
require_once __DIR__ . '/../core/Authorization.php';
require_once __DIR__ . '/../core/CSRF.php';

class BookingController
{
    private BookingModel $bookingModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
    }

    /**
     * Xác định vai trò hiện tại.
     */
    private function currentRole(): string
    {
        foreach (['admin', 'manager', 'teacher', 'technician'] as $role) {
            if (Authorization::hasRole($role)) {
                return $role;
            }
        }

        return '';
    }

    /**
     * Kiểm tra quyền xem lịch.
     */
    private function requireCalendarAccess(): string
    {
        Authorization::requireLogin();

        $role = $this->currentRole();

        if (!in_array($role, ['admin', 'manager', 'teacher', 'technician'], true)) {
            http_response_code(403);
            exit('Bạn không có quyền truy cập lịch phòng.');
        }

        return $role;
    }

    /**
     * Kiểm tra quyền tạo/sửa/hủy đặt phòng.
     */
    private function requireBookingAccess(): string
    {
        Authorization::requireLogin();

        $role = $this->currentRole();

        if (!in_array($role, ['admin', 'manager', 'teacher'], true)) {
            http_response_code(403);
            exit('Bạn không có quyền thực hiện chức năng đặt phòng.');
        }

        return $role;
    }

    /**
     * Chuyển hướng về lịch phòng kèm thông báo.
     */
    private function redirect(string $message = ''): void
    {
        $url = 'index.php?action=calendar';

        if ($message !== '') {
            $url .= '&message=' . urlencode($message);
        }

        header('Location: ' . $url);
        exit();
    }

    private function redirectToEdit(int $bookingId): void
    {
        header('Location: index.php?action=booking_edit&id=' . $bookingId);
        exit();
    }

    private function postString(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    private function postInt(string $key): int
    {
        $value = $_POST[$key] ?? null;

        if (!is_string($value) && !is_int($value)) {
            return 0;
        }

        $result = filter_var($value, FILTER_VALIDATE_INT);

        return $result !== false ? (int)$result : 0;
    }

    private function getInt(string $key): int
    {
        $value = $_GET[$key] ?? null;

        if (!is_string($value) && !is_int($value)) {
            return 0;
        }

        $result = filter_var($value, FILTER_VALIDATE_INT);

        return $result !== false ? (int)$result : 0;
    }

    private function isValidDate(string $date): bool
    {
        $dateObject = DateTime::createFromFormat('!Y-m-d', $date);
        $errors = DateTime::getLastErrors();

        return $dateObject !== false
            && $dateObject->format('Y-m-d') === $date
            && (
                $errors === false ||
                (
                    $errors['warning_count'] === 0
                    && $errors['error_count'] === 0
                )
            );
    }

    private function isValidHour(string $hour): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hour) === 1;
    }

    /**
     * Kiểm tra dữ liệu đặt phòng.
     */
    private function validateBookingData(
        int $roomId,
        string $title,
        string $purpose,
        string $bookingDate,
        string $startHour,
        string $endHour
    ): array {
        $errors = [];

        if ($roomId <= 0) {
            $errors[] = 'Vui lòng chọn phòng.';
        }

        if ($title === '') {
            $errors[] = 'Vui lòng nhập tiêu đề đặt phòng.';
        } elseif (mb_strlen($title, 'UTF-8') > 200) {
            $errors[] = 'Tiêu đề không được vượt quá 200 ký tự.';
        }

        if ($purpose === '') {
            $errors[] = 'Vui lòng nhập mục đích sử dụng.';
        }

        if (
            !$this->isValidDate($bookingDate) ||
            !$this->isValidHour($startHour) ||
            !$this->isValidHour($endHour)
        ) {
            $errors[] = 'Ngày hoặc giờ đặt phòng không hợp lệ.';
            return $errors;
        }

        $startTime = strtotime($bookingDate . ' ' . $startHour);
        $endTime = strtotime($bookingDate . ' ' . $endHour);

        if ($startTime === false || $endTime === false) {
            $errors[] = 'Không thể xác định thời gian đặt phòng.';
            return $errors;
        }

        if ($startTime <= time()) {
            $errors[] = 'Thời gian đặt phòng phải ở trong tương lai.';
        }

        if ($startTime >= $endTime) {
            $errors[] = 'Thời gian kết thúc phải sau thời gian bắt đầu.';
        }

        return $errors;
    }

    private function getOldBookingData(): array
    {
        return [
            'room_id' => $this->postInt('room_id'),
            'title' => $this->postString('title'),
            'purpose' => $this->postString('purpose'),
            'booking_date' => $this->postString('booking_date'),
            'start_time' => $this->postString('start_time'),
            'end_time' => $this->postString('end_time'),
            'booking_type' => $this->postString('booking_type'),
            'teacher_id' => $this->postInt('teacher_id')
        ];
    }

    /**
     * Xem lịch phòng.
     *
     * Admin/Manager: xem tất cả.
     * Teacher: xem lịch đã duyệt và lịch của bản thân.
     * Technician: chỉ xem lịch đã duyệt.
     */
    public function calendar(): void
    {
        $role = $this->requireCalendarAccess();
        $userId = Authorization::userId();

        $keyword = $_GET['keyword'] ?? '';
        $keyword = is_string($keyword) ? trim($keyword) : '';

        $canManage = in_array($role, ['admin', 'manager'], true);
        $isTeacher = $role === 'teacher';

        $bookings = $this->bookingModel->getAllBookings(
            $keyword,
            $userId,
            $canManage,
            $isTeacher
        );

        $rooms = $this->bookingModel->getActiveRooms();

        require __DIR__ . '/../views/booking_calendar.php';
    }

    /**
     * Hiển thị form tạo đặt phòng.
     */
    public function create(): void
    {
        $role = $this->requireBookingAccess();

        $rooms = $this->bookingModel->getActiveRooms();
        $teachers = [];

        if (in_array($role, ['admin', 'manager'], true)) {
            $teachers = $this->bookingModel->getActiveTeachers();
        }

        $errors = [];
        $old = [];

        require __DIR__ . '/../views/booking_form.php';
    }

    /**
     * Tạo đặt phòng.
     *
     * Teacher: tạo cho bản thân, trạng thái pending.
     * Manager/Admin: tạo cho giảng viên hoặc hoạt động chung,
     * trạng thái approved.
     */
    public function store(): void
    {
        $role = $this->requireBookingAccess();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect();
        }

        CSRF::verifyOrFail();

        $actorId = Authorization::userId();
        $old = $this->getOldBookingData();

        // Tương thích với dữ liệu form cũ chỉ gửi trường purpose.
        if ($old['title'] === '') {
            $old['title'] = $old['purpose'];
        }

        $errors = $this->validateBookingData(
            $old['room_id'],
            $old['title'],
            $old['purpose'],
            $old['booking_date'],
            $old['start_time'],
            $old['end_time']
        );

        $bookingType = '';
        $bookingUserId = null;

        if ($role === 'teacher') {
            // Giảng viên chỉ được tạo yêu cầu cá nhân.
            $bookingType = 'personal';
            $bookingUserId = $actorId;
        } else {
            // Manager/Admin không được tạo lịch cá nhân cho chính mình.
            $bookingType = $this->postString('booking_type');

            if (!in_array($bookingType, ['on_behalf', 'school_wide'], true)) {
                $errors[] = 'Vui lòng chọn hình thức đặt phòng hợp lệ.';
            } elseif ($bookingType === 'on_behalf') {
                $teacherId = $this->postInt('teacher_id');

                if ($teacherId <= 0 || !$this->bookingModel->isActiveTeacher($teacherId)) {
                    $errors[] = 'Vui lòng chọn giảng viên hợp lệ.';
                } else {
                    $bookingUserId = $teacherId;
                }
            } else {
                // Hoạt động chung của nhà trường không gắn với một giảng viên.
                $bookingUserId = null;
            }
        }

        if (!empty($errors)) {
            $rooms = $this->bookingModel->getActiveRooms();
            $teachers = in_array($role, ['admin', 'manager'], true)
                ? $this->bookingModel->getActiveTeachers()
                : [];

            require __DIR__ . '/../views/booking_form.php';
            return;
        }

        $startTime = $old['booking_date'] . ' ' . $old['start_time'] . ':00';
        $endTime = $old['booking_date'] . ' ' . $old['end_time'] . ':00';

        try {
            $success = $this->bookingModel->createBooking(
                $bookingUserId,
                $actorId,
                $old['room_id'],
                $bookingType,
                $old['title'],
                $old['purpose'],
                $startTime,
                $endTime
            );

            if ($success) {
                $this->redirect(
                    $role === 'teacher' ? 'created' : 'created_approved'
                );
            }
        } catch (Throwable $e) {
            error_log('Create booking error: ' . $e->getMessage());
        }

        $errors[] = 'Không thể tạo lịch. Phòng có thể đã có lịch được duyệt trùng giờ hoặc đã ngừng hoạt động.';

        $rooms = $this->bookingModel->getActiveRooms();
        $teachers = in_array($role, ['admin', 'manager'], true)
            ? $this->bookingModel->getActiveTeachers()
            : [];

        require __DIR__ . '/../views/booking_form.php';
    }

    /**
     * Hiển thị form sửa.
     * Chỉ giảng viên được sửa yêu cầu pending của chính mình.
     */
    public function edit(): void
    {
        $role = $this->requireBookingAccess();

        if ($role !== 'teacher') {
            http_response_code(403);
            exit('Chỉ giảng viên mới được chỉnh sửa yêu cầu của bản thân.');
        }

        $userId = Authorization::userId();
        $bookingId = $this->getInt('id');

        $booking = $this->bookingModel->getBookingById($bookingId, $userId);

        if (!$booking || $booking['status'] !== 'pending') {
            $this->redirect('not_allowed');
        }

        $rooms = $this->bookingModel->getActiveRooms();

        $errors = $_SESSION['booking_errors'] ?? [];
        $old = $_SESSION['booking_old'] ?? [];

        unset($_SESSION['booking_errors'], $_SESSION['booking_old']);

        require __DIR__ . '/../views/booking_edit.php';
    }

    /**
     * Cập nhật yêu cầu pending của giảng viên.
     */
    public function update(): void
    {
        $role = $this->requireBookingAccess();

        if ($role !== 'teacher') {
            http_response_code(403);
            exit('Bạn không có quyền chỉnh sửa yêu cầu này.');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect();
        }

        CSRF::verifyOrFail();

        $userId = Authorization::userId();
        $bookingId = $this->getInt('id');

        $booking = $this->bookingModel->getBookingById($bookingId, $userId);

        if (!$booking || $booking['status'] !== 'pending') {
            $this->redirect('not_allowed');
        }

        $old = $this->getOldBookingData();

        $errors = $this->validateBookingData(
            $old['room_id'],
            $old['title'],
            $old['purpose'],
            $old['booking_date'],
            $old['start_time'],
            $old['end_time']
        );

        if (!empty($errors)) {
            $_SESSION['booking_errors'] = $errors;
            $_SESSION['booking_old'] = $old;
            $this->redirectToEdit($bookingId);
        }

        $startTime = $old['booking_date'] . ' ' . $old['start_time'] . ':00';
        $endTime = $old['booking_date'] . ' ' . $old['end_time'] . ':00';

        try {
            $success = $this->bookingModel->updatePendingBooking(
                $bookingId,
                $userId,
                $old['room_id'],
                $old['title'],
                $old['purpose'],
                $startTime,
                $endTime
            );

            if ($success) {
                $this->redirect('updated');
            }
        } catch (Throwable $e) {
            error_log('Update booking error: ' . $e->getMessage());
        }

        $_SESSION['booking_errors'] = [
            'Không thể cập nhật yêu cầu. Phòng có thể đã ngừng hoạt động hoặc lịch vừa bị trùng.'
        ];
        $_SESSION['booking_old'] = $old;

        $this->redirectToEdit($bookingId);
    }

    /**
     * Hủy trực tiếp:
     * - Teacher: chỉ hủy yêu cầu pending của bản thân.
     * - Manager/Admin: hủy pending hoặc approved của bất kỳ ai.
     */
    public function cancel(): void
    {
        $role = $this->requireBookingAccess();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect();
        }

        CSRF::verifyOrFail();

        $bookingId = $this->postInt('booking_id');
        $reason = $this->postString('reason');

        if ($bookingId <= 0) {
            $this->redirect('cancel_failed');
        }

        // Lý do hủy được lưu cùng thông tin người thực hiện và thời điểm.
        if ($reason === '') {
            $reason = $role === 'teacher'
                ? 'Giảng viên chủ động hủy yêu cầu đang chờ duyệt.'
                : '';

            if ($reason === '') {
                $this->redirect('cancel_reason_required');
            }
        }

        try {
            $success = $this->bookingModel->cancelBooking(
                $bookingId,
                Authorization::userId(),
                $role,
                $reason
            );

            $this->redirect($success ? 'cancelled' : 'cancel_failed');
        } catch (Throwable $e) {
            error_log('Cancel booking error: ' . $e->getMessage());
            $this->redirect('cancel_failed');
        }
    }
}
