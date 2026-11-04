<?php

require_once __DIR__ . '/../models/BookingCancellationModel.php';
require_once __DIR__ . '/../core/Authorization.php';
require_once __DIR__ . '/../core/CSRF.php';

class BookingCancellationController
{
    private BookingCancellationModel $model;

    public function __construct()
    {
        $this->model = new BookingCancellationModel();
    }

    private function currentRole(): string
    {
        foreach (['admin', 'manager', 'teacher', 'technician'] as $role) {
            if (Authorization::hasRole($role)) {
                return $role;
            }
        }

        return '';
    }

    private function requireTeacher(): void
    {
        Authorization::requireLogin();

        if ($this->currentRole() !== 'teacher') {
            http_response_code(403);
            exit('Chỉ giảng viên mới được gửi yêu cầu hủy lịch.');
        }
    }

    private function requireManager(): void
    {
        Authorization::requireLogin();

        if (!in_array($this->currentRole(), ['admin', 'manager'], true)) {
            http_response_code(403);
            exit('Bạn không có quyền xử lý yêu cầu hủy lịch.');
        }
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

    private function postString(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    private function redirectCalendar(string $message): void
    {
        header('Location: index.php?action=calendar&message=' . urlencode($message));
        exit();
    }

    private function redirectRequests(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];

        header('Location: index.php?action=booking_cancellation_requests');
        exit();
    }

    /**
     * Giảng viên gửi yêu cầu hủy lịch đã được duyệt.
     */
    public function request(): void
    {
        $this->requireTeacher();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Phương thức yêu cầu không hợp lệ.');
        }

        CSRF::verifyOrFail();

        $bookingId = $this->postInt('booking_id');
        $reason = $this->postString('reason');

        if ($bookingId <= 0 || $reason === '') {
            $this->redirectCalendar('cancellation_request_invalid');
        }

        try {
            $success = $this->model->createRequest(
                $bookingId,
                Authorization::userId(),
                $reason
            );

            $this->redirectCalendar(
                $success
                    ? 'cancellation_request_sent'
                    : 'cancellation_request_failed'
            );

        } catch (Throwable $e) {
            error_log('Create cancellation request error: ' . $e->getMessage());
            $this->redirectCalendar('cancellation_request_failed');
        }
    }

    /**
     * Manager/Admin xem danh sách yêu cầu hủy.
     */
    public function index(): void
    {
        $this->requireManager();

        $status = $_GET['status'] ?? 'pending';

        $allowedStatuses = [
            'pending',
            'approved',
            'rejected',
            'all'
        ];

        if (!is_string($status) || !in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        try {
            $requests = $this->model->getRequests($status);

            require __DIR__ . '/../views/booking_cancellation_requests.php';

        } catch (Throwable $e) {
            error_log('Cancellation request index error: ' . $e->getMessage());

            http_response_code(500);
            exit('Không thể tải danh sách yêu cầu hủy lịch.');
        }
    }

    /**
     * Manager/Admin chấp thuận yêu cầu hủy.
     */
    public function approve(): void
    {
        $this->process('approved');
    }

    /**
     * Manager/Admin từ chối yêu cầu hủy.
     */
    public function reject(): void
    {
        $this->process('rejected');
    }

    private function process(string $decision): void
    {
        $this->requireManager();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Phương thức yêu cầu không hợp lệ.');
        }

        CSRF::verifyOrFail();

        $requestId = $this->postInt('request_id');
        $comment = $this->postString('comment');

        if ($requestId <= 0) {
            $this->redirectRequests('error', 'Mã yêu cầu không hợp lệ.');
        }

        if ($decision === 'rejected' && $comment === '') {
            $this->redirectRequests('error', 'Vui lòng nhập lý do từ chối yêu cầu hủy.');
        }

        try {
            $success = $this->model->processRequest(
                $requestId,
                Authorization::userId(),
                $decision,
                $comment
            );

            if ($success) {
                $message = $decision === 'approved'
                    ? 'Đã chấp thuận yêu cầu hủy lịch.'
                    : 'Đã từ chối yêu cầu hủy lịch.';

                $this->redirectRequests('success', $message);
            }

            $this->redirectRequests(
                'error',
                'Không thể xử lý. Yêu cầu có thể đã được xử lý hoặc lịch không còn ở trạng thái đã duyệt.'
            );

        } catch (Throwable $e) {
            error_log('Process cancellation request error: ' . $e->getMessage());

            $this->redirectRequests(
                'error',
                'Có lỗi xảy ra khi xử lý yêu cầu hủy lịch.'
            );
        }
    }
}
