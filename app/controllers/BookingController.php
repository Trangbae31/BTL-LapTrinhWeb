<?php

require_once __DIR__ . '/../models/BookingModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class BookingController
{
    private BookingModel $bookingModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
    }

    private function requireTeacher(): int
    {
        if (
            !isset($_SESSION['user']) ||
            !in_array('teacher', $_SESSION['user']['roles'] ?? [], true)
        ) {
            header('Location: index.php?action=login');
            exit();
        }

        return (int) $_SESSION['user']['user_id'];
    }

    // Xem lịch phòng và tìm kiếm yêu cầu đặt phòng
    public function calendar(): void
    {
        $userId = $this->requireTeacher();
        $keyword = trim($_GET['keyword'] ?? '');

        $bookings = $this->bookingModel->getAllBookings($keyword);
        $rooms = $this->bookingModel->getActiveRooms();

        require __DIR__ . '/../views/booking_calendar.php';
    }

    // Hiển thị form tạo yêu cầu
    public function create(): void
    {
        $this->requireTeacher();

        $rooms = $this->bookingModel->getActiveRooms();
        $errors = [];

        require __DIR__ . '/../views/booking_form.php';
    }

    // Lưu yêu cầu đặt phòng
    public function store(): void
    {
        $userId = $this->requireTeacher();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=booking_create');
            exit();
        }

        $roomId = (int) ($_POST['room_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $purpose = trim($_POST['purpose'] ?? '');
        $bookingDate = $_POST['booking_date'] ?? '';
        $startHour = $_POST['start_time'] ?? '';
        $endHour = $_POST['end_time'] ?? '';

        $startTime = $bookingDate . ' ' . $startHour . ':00';
        $endTime = $bookingDate . ' ' . $endHour . ':00';

        $errors = [];

        if ($roomId <= 0) {
            $errors[] = 'Vui lòng chọn phòng.';
        }

        if ($title === '') {
            $errors[] = 'Vui lòng nhập tiêu đề đặt phòng.';
        }

        if ($purpose === '') {
            $errors[] = 'Vui lòng nhập mục đích sử dụng.';
        }

        if (
            strtotime($startTime) === false ||
            strtotime($endTime) === false ||
            strtotime($startTime) >= strtotime($endTime)
        ) {
            $errors[] = 'Thời gian kết thúc phải sau thời gian bắt đầu.';
        }

        if (empty($errors)) {
            $success = $this->bookingModel->createBooking(
                $userId,
                $roomId,
                $title,
                $purpose,
                $startTime,
                $endTime
            );

            if ($success) {
                header('Location: index.php?action=calendar&message=created');
                exit();
            }

            $errors[] = 'Không thể đặt phòng. Phòng có thể đã được đặt trong khoảng thời gian này.';
        }

        $rooms = $this->bookingModel->getActiveRooms();
        require __DIR__ . '/../views/booking_form.php';
    }

    // Hiển thị form sửa yêu cầu
    public function edit(): void
    {
        $userId = $this->requireTeacher();
        $bookingId = (int) ($_GET['id'] ?? 0);

        $booking = $this->bookingModel->getBookingById($bookingId, $userId);

        if (!$booking || $booking['status'] !== 'pending') {
            header('Location: index.php?action=calendar&message=not_allowed');
            exit();
        }

        $rooms = $this->bookingModel->getActiveRooms();
        $errors = [];

        require __DIR__ . '/../views/booking_edit.php';
    }

    // Cập nhật yêu cầu
    public function update(): void
    {
        $userId = $this->requireTeacher();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=calendar');
            exit();
        }

        $bookingId = (int) ($_GET['id'] ?? 0);
        $roomId = (int) ($_POST['room_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $purpose = trim($_POST['purpose'] ?? '');

        $bookingDate = $_POST['booking_date'] ?? '';
        $startHour = $_POST['start_time'] ?? '';
        $endHour = $_POST['end_time'] ?? '';

        $startTime = $bookingDate . ' ' . $startHour . ':00';
        $endTime = $bookingDate . ' ' . $endHour . ':00';

        if (
            $roomId <= 0 ||
            $title === '' ||
            $purpose === '' ||
            strtotime($startTime) === false ||
            strtotime($endTime) === false ||
            strtotime($startTime) >= strtotime($endTime)
        ) {
            header('Location: index.php?action=booking_edit&id=' . $bookingId . '&message=invalid');
            exit();
        }

        $success = $this->bookingModel->updateBooking(
            $bookingId,
            $userId,
            $roomId,
            $title,
            $purpose,
            $startTime,
            $endTime
        );

        $message = $success ? 'updated' : 'update_failed';

        header('Location: index.php?action=calendar&message=' . $message);
        exit();
    }

    // Hủy yêu cầu đặt phòng
    public function cancel(): void
    {
        $userId = $this->requireTeacher();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=calendar');
            exit();
        }

        $bookingId = (int) ($_GET['id'] ?? 0);

        $success = $this->bookingModel->cancelBooking($bookingId, $userId);

        $message = $success ? 'cancelled' : 'cancel_failed';

        header('Location: index.php?action=calendar&message=' . $message);
        exit();
    }
}