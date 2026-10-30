
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

        $stats = $this->dashboardModel->getStatistics(
            $userId,
            $roles
        );

        $recentBookings = $this->dashboardModel->getRecentBookings(
            $userId,
            $isManager
        );

        $recentReports = $this->dashboardModel->getRecentReports(
            $userId,
            $isManager
        );

        require __DIR__ . '/../views/dashboard.php';
    }
}
