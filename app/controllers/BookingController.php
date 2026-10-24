
<?php

require_once __DIR__ . '/../models/BookingModel.php';

class BookingController
{
    private BookingModel $model;

    public function __construct()
    {
        $this->model = new BookingModel();
    }

    // Hiển thị lịch phòng
    public function calendar(): void
    {
        $bookings = $this->model->getAllBookings();

        require __DIR__ . '/../views/booking_calendar.php';
    }

    // Hiển thị form đặt phòng
    public function create(): void
    {
        $rooms = $this->model->getActiveRooms();

        require __DIR__ . '/../views/booking_form.php';
    }


    // Xử lý dữ liệu gửi từ form đặt phòng
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=booking_create');
            exit();
        }

        // Lấy dữ liệu từ form
        $roomId = (int) ($_POST['room_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $purpose = trim($_POST['purpose'] ?? '');
        $bookingDate = $_POST['booking_date'] ?? '';
        $start = $_POST['start_time'] ?? '';
        $end = $_POST['end_time'] ?? '';

        // Kiểm tra dữ liệu cơ bản
        if (
            $roomId <= 0 ||
            $title === '' ||
            $purpose === '' ||
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $bookingDate) ||
            !preg_match('/^\d{2}:\d{2}$/', $start) ||
            !preg_match('/^\d{2}:\d{2}$/', $end)
        ) {
            exit('Dữ liệu đặt phòng không hợp lệ. Vui lòng quay lại và kiểm tra.');
        }

        $startTime = $bookingDate . ' ' . $start . ':00';
        $endTime = $bookingDate . ' ' . $end . ':00';

        // Tạm thời lấy user_id từ session khi tích hợp đăng nhập
        $userId = (int) ($_SESSION['user']['user_id'] ?? 0);

        if ($userId <= 0) {
            exit(
                'Chưa có user_id trong session. ' .
                'Chức năng cần được kết nối với phần đăng nhập.'
            );
        }

        // Lưu yêu cầu đặt phòng
        $success = $this->model->createBooking(
            $userId,
            $roomId,
            $title,
            $purpose,
            $startTime,
            $endTime
        );

        if ($success) {
            header('Location: index.php?action=calendar');
            exit();
        }

        exit(
            'Không thể đặt phòng. Có thể thời gian không hợp lệ, ' .
            'phòng đã bị trùng lịch hoặc dữ liệu không hợp lệ.'
        );
    }
}
