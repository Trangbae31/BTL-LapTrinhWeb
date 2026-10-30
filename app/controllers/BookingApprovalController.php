<?php

require_once __DIR__ . '/../models/BookingApprovalModel.php';
require_once __DIR__ . '/../core/Authorization.php';

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

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        try {
            $bookings = $this->model->getBookings($status);

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

        $bookingId = filter_input(
            INPUT_POST,
            'booking_id',
            FILTER_VALIDATE_INT
        );

        $comment = trim($_POST['comment'] ?? '');

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
                'Không thể duyệt yêu cầu. Yêu cầu có thể đã được xử lý hoặc phòng đã có lịch trùng.'
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

        $bookingId = filter_input(
            INPUT_POST,
            'booking_id',
            FILTER_VALIDATE_INT
        );

        $comment = trim($_POST['comment'] ?? '');

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