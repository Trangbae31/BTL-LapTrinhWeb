<?php

require_once __DIR__ . '/../models/BookingApprovalModel.php';
require_once __DIR__ . '/../core/Authorization.php';
require_once __DIR__ . '/../core/CSRF.php';

class BookingApprovalController
{
    private BookingApprovalModel $model;

    public function __construct()
    {
        $this->model = new BookingApprovalModel();
    }

    /**
     * Kiểm tra quyền quản lý.
     */
    private function authorizeManager(): void
    {
        Authorization::requireRole(['admin', 'manager']);
    }

    /**
     * Hiển thị danh sách yêu cầu đặt phòng.
     */
    public function index(): void
    {
        $this->authorizeManager();

        $status = $_GET['status'] ?? 'pending';

        $allowedStatuses = [
            'pending',
            'approved',
            'rejected',
            'cancelled',
            'all'
        ];

        if (!is_string($status) || !in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        $roomId = filter_var($_GET['room_id'] ?? 0, FILTER_VALIDATE_INT);
        $requesterId = filter_var($_GET['requester_id'] ?? 0, FILTER_VALIDATE_INT);
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        $roomId = $roomId !== false ? max(0, (int)$roomId) : 0;
        $requesterId = $requesterId !== false ? max(0, (int)$requesterId) : 0;
        $dateFrom = is_string($dateFrom) ? trim($dateFrom) : '';
        $dateTo = is_string($dateTo) ? trim($dateTo) : '';

        $validDate = static function (string $date): bool {
            if ($date === '') {
                return true;
            }
            $parsed = DateTime::createFromFormat('!Y-m-d', $date);
            $errors = DateTime::getLastErrors();
            return $parsed !== false
                && $parsed->format('Y-m-d') === $date
                && ($errors === false || (
                    $errors['warning_count'] === 0
                    && $errors['error_count'] === 0
                ));
        };

        if (!$validDate($dateFrom)) {
            $dateFrom = '';
        }
        if (!$validDate($dateTo)) {
            $dateTo = '';
        }
        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        try {
            $bookings = $this->model->getBookings(
                $status,
                $roomId,
                $requesterId,
                $dateFrom,
                $dateTo
            );
            $filterOptions = $this->model->getBookingFilterOptions();
            $rooms = $filterOptions['rooms'];
            $requesters = $filterOptions['requesters'];

            require __DIR__ . '/../views/booking_approvals.php';

        } catch (Throwable $e) {
            error_log('Booking approval index error: ' . $e->getMessage());

            http_response_code(500);
            exit('Không thể tải danh sách yêu cầu đặt phòng.');
        }
    }

    /**
     * Duyệt yêu cầu đặt phòng.
     */
    public function approve(): void
    {
        $this->authorizeManager();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Phương thức yêu cầu không hợp lệ.');
        }

        // Kiểm tra CSRF trước khi xử lý dữ liệu.
        CSRF::verifyOrFail();

        $bookingId = filter_input(
            INPUT_POST,
            'booking_id',
            FILTER_VALIDATE_INT
        );

        $comment = $_POST['comment'] ?? '';

        if (!is_string($comment)) {
            $this->redirectWithMessage(
                'booking_approvals',
                'error',
                'Dữ liệu nhận xét không hợp lệ.'
            );
        }

        $comment = trim($comment);

        if (!$bookingId || $bookingId <= 0) {
            $this->redirectWithMessage(
                'booking_approvals',
                'error',
                'Mã yêu cầu đặt phòng không hợp lệ.'
            );
        }

        try {
            $success = $this->model->approve(
                (int)$bookingId,
                Authorization::userId(),
                $comment
            );

            if ($success) {
                $this->redirectWithMessage(
                    'booking_approvals',
                    'success',
                    'Đã duyệt yêu cầu đặt phòng.'
                );
            }

            $this->redirectWithMessage(
                'booking_approvals',
                'error',
                'Không thể duyệt yêu cầu. Yêu cầu có thể đã được xử lý, phòng đã ngừng hoạt động hoặc lịch bị trùng.'
            );

        } catch (Throwable $e) {
            error_log('Booking approval error: ' . $e->getMessage());

            $this->redirectWithMessage(
                'booking_approvals',
                'error',
                'Có lỗi xảy ra khi duyệt yêu cầu đặt phòng.'
            );
        }
    }

    /**
     * Từ chối yêu cầu đặt phòng.
     */
    public function reject(): void
    {
        $this->authorizeManager();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Phương thức yêu cầu không hợp lệ.');
        }

        // Kiểm tra CSRF trước khi xử lý dữ liệu.
        CSRF::verifyOrFail();

        $bookingId = filter_input(
            INPUT_POST,
            'booking_id',
            FILTER_VALIDATE_INT
        );

        $comment = $_POST['comment'] ?? '';

        if (!is_string($comment)) {
            $this->redirectWithMessage(
                'booking_approvals',
                'error',
                'Dữ liệu lý do từ chối không hợp lệ.'
            );
        }

        $comment = trim($comment);

        if (!$bookingId || $bookingId <= 0) {
            $this->redirectWithMessage(
                'booking_approvals',
                'error',
                'Mã yêu cầu đặt phòng không hợp lệ.'
            );
        }

        if ($comment === '') {
            $this->redirectWithMessage(
                'booking_approvals',
                'error',
                'Vui lòng nhập lý do từ chối.'
            );
        }

        try {
            $success = $this->model->reject(
                (int)$bookingId,
                Authorization::userId(),
                $comment
            );

            if ($success) {
                $this->redirectWithMessage(
                    'booking_approvals',
                    'success',
                    'Đã từ chối yêu cầu đặt phòng.'
                );
            }

            $this->redirectWithMessage(
                'booking_approvals',
                'error',
                'Không thể từ chối yêu cầu. Yêu cầu có thể đã được xử lý trước đó.'
            );

        } catch (Throwable $e) {
            error_log('Booking rejection error: ' . $e->getMessage());

            $this->redirectWithMessage(
                'booking_approvals',
                'error',
                'Có lỗi xảy ra khi từ chối yêu cầu đặt phòng.'
            );
        }
    }

    /**
     * Chuyển hướng kèm thông báo.
     */
    private function redirectWithMessage(
        string $action,
        string $type,
        string $message
    ): void {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];

        header('Location: index.php?action=' . $action);
        exit();
    }
}