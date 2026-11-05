
<?php

require_once __DIR__ . '/../models/DashboardModel.php';
require_once __DIR__ . '/../core/Authorization.php';

class DashboardController
{
    private DashboardModel $dashboardModel;

    public function __construct()
    {
        $this->dashboardModel = new DashboardModel();
    }

    public function index(): void
    {
        Authorization::requireLogin();

        $user = Authorization::user();
        $userId = Authorization::userId();
        $roles = Authorization::roles();

        $isManager = Authorization::hasAnyRole([
            'admin',
            'manager'
        ]);

        $isTeacher = Authorization::hasRole('teacher');
        $isTechnician = Authorization::hasRole('technician');

        $userName = $user['full_name']
            ?? $user['fullname']
            ?? 'Người dùng';

        $roleLabel = Authorization::roleLabel();
        $unreadNotifications = 0;
        try {
            $noticeStmt = Database::connect()->prepare(
                "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND read_at IS NULL"
            );
            $noticeStmt->execute(['user_id' => $userId]);
            $unreadNotifications = (int)$noticeStmt->fetchColumn();
        } catch (Throwable $e) {
            // Bảng thông báo có thể chưa được tạo bằng migration.
            error_log('Notification count unavailable: ' . $e->getMessage());
        }

        $stats = $this->dashboardModel->getStatistics(
            $userId,
            $roles
        );

        $recentBookings = $this->dashboardModel->getRecentBookings(
            $userId,
            $isManager,
            $roles
        );

        $recentReports = $this->dashboardModel->getRecentReports(
            $userId,
            $isManager,
            $roles
        );

        require __DIR__ . '/../views/dashboard.php';
    }
}
