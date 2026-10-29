
<?php

require_once __DIR__ . '/../config/database.php';

// Khoi tao ket noi database
$pdo = Database::connect();

// Khoi tao session neu chua co
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/RoomController.php';
require_once __DIR__ . '/../app/controllers/EquipmentController.php';
require_once __DIR__ . '/../app/controllers/ReportController.php';
require_once __DIR__ . '/../app/controllers/BookingController.php';

// Lay action tu URL
$action = $_GET['action'] ?? 'login';

// Khoi tao controller dang nhap
$authController = new AuthController();

switch ($action) {

    // DANG NHAP / DANG XUAT
    case 'login':
        $authController->login();
        break;

    case 'logout':
        $authController->logout();
        break;

    // TRANG CHU
    case 'dashboard':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }

        require_once __DIR__ . '/../app/views/dashboard.php';
        break;

    // QUAN LY PHONG
    case 'rooms':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }

        (new RoomController())->index();
        break;

    case 'room_form':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }

        (new RoomController())->form();
        break;

    case 'room_delete':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }

        (new RoomController())->delete();
        break;

    // QUAN LY THIET BI
    case 'equipment':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }

        (new EquipmentController())->index();
        break;

    case 'equipment_form':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }

        (new EquipmentController())->form();
        break;

    case 'equipment_delete':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }

        (new EquipmentController())->delete();
        break;

    // QUAN LY LICH PHONG VA BOOKING
    case 'calendar':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }

        (new BookingController())->calendar();
        break;

    case 'booking_create':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }

        (new BookingController())->create();
        break;

    case 'booking_store':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=booking_create');
            exit();
        }

        (new BookingController())->store();
        break;

    // QUAN LY BAO HONG VA BAO TRI
    case 'reports':
    case 'report_list':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }

        (new ReportController())->index();
        break;

    // HIEN THI FORM TAO PHIEU
    case 'report_form':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }

        (new ReportController())->create();
        break;

    // LUU PHIEU MOI
    case 'report_store':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=report_form');
            exit();
        }

        (new ReportController())->store();
        break;

    // HIEN THI FORM SUA PHIEU
    case 'report_edit':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }

        (new ReportController())->edit();
        break;

    // LUU THAY DOI THONG TIN PHIEU
    case 'report_update_info':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=report_list');
            exit();
        }

        (new ReportController())->update();
        break;

    // XEM LICH SU BAO HONG
    case 'report_history':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }

        (new ReportController())->history();
        break;

    // CAP NHAT TRANG THAI BAO HONG
    case 'report_update':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=report_list');
            exit();
        }

        (new ReportController())->updateStatus();
        break;

    // ACTION KHONG TON TAI
    default:
        http_response_code(404);
        echo 'Khong tim thay chuc nang nay.';
        break;
}
