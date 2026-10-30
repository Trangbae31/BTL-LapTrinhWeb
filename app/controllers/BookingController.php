<?php

require_once __DIR__ . '/../models/BookingModel.php';
require_once __DIR__ . '/../core/Authorization.php';

class BookingController
{
    private BookingModel $bookingModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
    }

    /**
     * Chỉ giảng viên được sử dụng chức năng đặt phòng.
     */
    private function requireTeacher(): int
    {
        Authorization::requireRole(['teacher']);
        return Authorization::userId();
    }

    /**
     * Chuyển hướng kèm thông báo.
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

    /**
     * Kiểm tra dữ liệu thời gian.
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
        } elseif (mb_strlen($title) > 200) {
            $errors[] = 'Tiêu đề không được vượt quá 200 ký tự.';
        }

        if ($purpose === '') {
            $errors[] = 'Vui lòng nhập mục đích sử dụng.';
        }

        $date = DateTime::createFromFormat('!Y-m-d', $bookingDate);
        $validDate = $date && $date->format('Y-m-d') === $bookingDate;

        $validStart = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $startHour);
        $validEnd = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $endHour);

        if (!$validDate || !$validStart || !$validEnd) {
            $errors[] = 'Ngày hoặc giờ đặt phòng không hợp lệ.';
            return $errors;
        }

        $startTime = strtotime($bookingDate . ' ' . $startHour);
        $endTime = strtotime($bookingDate . ' ' . $endHour);

        if ($startTime <= time()) {
            $errors[] = 'Thời gian đặt phòng phải ở trong tương lai.';
        }

        if ($startTime >= $endTime) {
            $errors[] = 'Thời gian kết thúc phải sau thời gian bắt đầu.';
        }

        return $errors;
    }

    /**
     * Xem lịch phòng.
     */
    public function calendar(): void
    {
        $userId = $this->requireTeacher();

        $keyword = trim($_GET['keyword'] ?? '');

        $bookings = $this->bookingModel->getAllBookings($keyword, $userId);
        $rooms = $this->bookingModel->getActiveRooms();

        require __DIR__ . '/../views/booking_calendar.php';
    }

    /**
     * Hiển thị form tạo yêu cầu.
     */
    public function create(): void
    {
        $this->requireTeacher();

        $rooms = $this->bookingModel->getActiveRooms();
        $errors = [];

        require __DIR__ . '/../views/booking_form.php';
    }

    /**
     * Lưu yêu cầu đặt phòng.
     */
    public function store(): void
    {
        $userId = $this->requireTeacher();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect();
        }

        $roomId = (int)($_POST['room_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $purpose = trim($_POST['purpose'] ?? '');
        $bookingDate = trim($_POST['booking_date'] ?? '');
        $startHour = trim($_POST['start_time'] ?? '');
        $endHour = trim($_POST['end_time'] ?? '');

        $errors = $this->validateBookingData(
            $roomId,
            $title,
            $purpose,
            $bookingDate,
            $startHour,
            $endHour
        );

        if (empty($errors)) {
            $startTime = $bookingDate . ' ' . $startHour . ':00';
            $endTime = $bookingDate . ' ' . $endHour . ':00';

            $success = $this->bookingModel->createBooking(
                $userId,
                $roomId,
                $title,
                $purpose,
                $startTime,
                $endTime
            );

            if ($success) {
                $this->redirect('created');
            }

            $errors[] = 'Không thể gửi yêu cầu. Phòng có thể đã có lịch trùng hoặc không còn hoạt động.';
        }

        $rooms = $this->bookingModel->getActiveRooms();

        require __DIR__ . '/../views/booking_form.php';
    }

    /**
     * Hiển thị form sửa yêu cầu.
     */
    public function edit(): void
    {
        $userId = $this->requireTeacher();
        $bookingId = (int)($_GET['id'] ?? 0);

        $booking = $this->bookingModel->getBookingById(
            $bookingId,
            $userId
        );

        if (!$booking || $booking['status'] !== 'pending') {
            $this->redirect('not_allowed');
        }

        $rooms = $this->bookingModel->getActiveRooms();
        $errors = [];

        require __DIR__ . '/../views/booking_edit.php';
    }

    /**
     * Cập nhật yêu cầu đặt phòng.
     */
    public function update(): void
    {
        $userId = $this->requireTeacher();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect();
        }

        $bookingId = (int)($_GET['id'] ?? 0);

        $roomId = (int)($_POST['room_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $purpose = trim($_POST['purpose'] ?? '');
        $bookingDate = trim($_POST['booking_date'] ?? '');
        $startHour = trim($_POST['start_time'] ?? '');
        $endHour = trim($_POST['end_time'] ?? '');

        $booking = $this->bookingModel->getBookingById(
            $bookingId,
            $userId
        );

        if (!$booking || $booking['status'] !== 'pending') {
            $this->redirect('not_allowed');
        }

        $errors = $this->validateBookingData(
            $roomId,
            $title,
            $purpose,
            $bookingDate,
            $startHour,
            $endHour
        );

        if (!empty($errors)) {
            $_SESSION['booking_errors'] = $errors;
            header('Location: index.php?action=booking_edit&id=' . $bookingId);
            exit();
        }

        $startTime = $bookingDate . ' ' . $startHour . ':00';
        $endTime = $bookingDate . ' ' . $endHour . ':00';

        $success = $this->bookingModel->updateBooking(
            $bookingId,
            $userId,
            $roomId,
            $title,
            $purpose,
            $startTime,
            $endTime
        );

        $this->redirect($success ? 'updated' : 'update_failed');
    }

    /**
     * Hủy yêu cầu đặt phòng.
     */
    public function cancel(): void
    {
        $userId = $this->requireTeacher();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect();
        }

        $bookingId = (int)($_POST['booking_id'] ?? $_GET['id'] ?? 0);

        if ($bookingId <= 0) {
            $this->redirect('cancel_failed');
        }

        $success = $this->bookingModel->cancelBooking(
            $bookingId,
            $userId
        );

        $this->redirect($success ? 'cancelled' : 'cancel_failed');
    }
}