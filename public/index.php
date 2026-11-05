
<?php
// Không hiển thị warning/stack trace ra giao diện; lỗi vẫn được ghi log PHP.
$isDevelopment = (getenv('APP_ENV') ?: 'production') === 'development';
ini_set('display_errors', $isDevelopment ? '1' : '0');
ini_set('display_startup_errors', $isDevelopment ? '1' : '0');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once __DIR__ . '/../config/database.php';

$pdo = Database::connect();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Core
require_once __DIR__ . '/../app/core/Authorization.php';

// Controllers
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';
require_once __DIR__ . '/../app/controllers/RoomController.php';
require_once __DIR__ . '/../app/controllers/EquipmentController.php';
require_once __DIR__ . '/../app/controllers/EquipmentApiController.php';
require_once __DIR__ . '/../app/controllers/ReportController.php';
require_once __DIR__ . '/../app/controllers/BookingController.php';
require_once __DIR__ . '/../app/controllers/BookingApprovalController.php';
require_once __DIR__ . '/../app/controllers/BookingCancellationController.php';
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/controllers/NotificationController.php';

$action = $_GET['action'] ?? 'login';

$authController = new AuthController();

// ==========================================
// KIỂM TRA ĐĂNG NHẬP
// ==========================================

function requireLoginForRoute(): void
{
    Authorization::requireLogin();
}

// ==========================================
// KIỂM TRA PHƯƠNG THỨC POST
// ==========================================

function requirePostRequest(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Phương thức yêu cầu không hợp lệ.');
    }
}

switch ($action) {

    // ==========================================
    // ĐĂNG NHẬP / ĐĂNG XUẤT
    // ==========================================

    case 'login':
        $authController->login();
        break;

    case 'logout':
        requireLoginForRoute();
        requirePostRequest();
        require_once __DIR__ . '/../app/core/CSRF.php';
        CSRF::verifyOrFail();
        $authController->logout();
        break;


    // ==========================================
    // DASHBOARD
    // ==========================================

    case 'dashboard':
        requireLoginForRoute();
        (new DashboardController())->index();
        break;


    // ==========================================
    // QUẢN LÝ PHÒNG
    // ==========================================

    case 'rooms':
        requireLoginForRoute();
        (new RoomController())->index();
        break;

    case 'room_form':
        requireLoginForRoute();
        (new RoomController())->form();
        break;

    case 'room_delete':
        requireLoginForRoute();
        requirePostRequest();
        (new RoomController())->delete();
        break;


    // ==========================================
    // QUẢN LÝ THIẾT BỊ
    // ==========================================

    case 'equipment':
        requireLoginForRoute();
        (new EquipmentController())->index();
        break;

    case 'equipment_api':
        (new EquipmentApiController())->index();
        break;

    case 'equipment_detail':
        requireLoginForRoute();
        (new EquipmentController())->detail();
        break;

    case 'equipment_form':
        requireLoginForRoute();
        (new EquipmentController())->form();
        break;

    case 'equipment_delete':
        requireLoginForRoute();
        requirePostRequest();
        (new EquipmentController())->delete();
        break;

    // CRUD LOẠI THIẾT BỊ - thuộc module Quản lý phòng và thiết bị
    case 'equipment_type':
        requireLoginForRoute();
        (new EquipmentController())->typeIndex();
        break;

    case 'equipment_type_store':
        requireLoginForRoute();
        requirePostRequest();
        (new EquipmentController())->typeStore();
        break;

    case 'equipment_type_update':
        requireLoginForRoute();
        requirePostRequest();
        (new EquipmentController())->typeUpdate();
        break;

    case 'equipment_type_delete':
        requireLoginForRoute();
        requirePostRequest();
        (new EquipmentController())->typeDelete();
        break;

    // API JSON kiểm tra phòng trống
    case 'room_availability_api':
    case 'room_availability':
        (new EquipmentApiController())->roomAvailability();
        break;

    case 'notifications':
        requireLoginForRoute();
        (new NotificationController())->index();
        break;

    case 'notification_read':
        requireLoginForRoute();
        requirePostRequest();
        (new NotificationController())->markRead();
        break;

    // QUẢN LÝ TÀI KHOẢN - chỉ Admin
    case 'users':
        requireLoginForRoute();
        (new UserController())->index();
        break;

    case 'user_store':
        requireLoginForRoute();
        requirePostRequest();
        (new UserController())->store();
        break;

    case 'user_update':
        requireLoginForRoute();
        requirePostRequest();
        (new UserController())->update();
        break;


    // ==========================================
    // LỊCH PHÒNG VÀ ĐẶT PHÒNG
    // ==========================================

    case 'calendar':
        requireLoginForRoute();
        (new BookingController())->calendar();
        break;

    case 'booking_create':
        requireLoginForRoute();
        (new BookingController())->create();
        break;

    case 'booking_store':
        requireLoginForRoute();
        requirePostRequest();
        (new BookingController())->store();
        break;

    case 'booking_edit':
        requireLoginForRoute();
        (new BookingController())->edit();
        break;

    case 'booking_update':
        requireLoginForRoute();
        requirePostRequest();
        (new BookingController())->update();
        break;

    case 'booking_cancel':
        requireLoginForRoute();
        requirePostRequest();
        (new BookingController())->cancel();
        break;


    // ==========================================
    // DUYỆT YÊU CẦU ĐẶT PHÒNG
    // ==========================================

    case 'booking_approvals':
        requireLoginForRoute();
        (new BookingApprovalController())->index();
        break;

    case 'booking_approve':
        requireLoginForRoute();
        requirePostRequest();
        (new BookingApprovalController())->approve();
        break;

    case 'booking_reject':
        requireLoginForRoute();
        requirePostRequest();
        (new BookingApprovalController())->reject();
        break;

    // ==========================================
    // YÊU CẦU HỦY LỊCH ĐÃ ĐƯỢC DUYỆT
    // ==========================================

    case 'booking_cancellation_request':
        requireLoginForRoute();
        requirePostRequest();
        (new BookingCancellationController())->request();
        break;

    case 'booking_cancellation_requests':
        requireLoginForRoute();
        (new BookingCancellationController())->index();
        break;

    case 'booking_cancellation_approve':
        requireLoginForRoute();
        requirePostRequest();
        (new BookingCancellationController())->approve();
        break;

    case 'booking_cancellation_reject':
        requireLoginForRoute();
        requirePostRequest();
        (new BookingCancellationController())->reject();
        break;

    // ==========================================
    // QUẢN LÝ BÁO HỎNG VÀ BẢO TRÌ
    // ==========================================

    case 'reports':
    case 'report_list':
        requireLoginForRoute();
        (new ReportController())->index();
        break;

    // Hiển thị form tạo báo hỏng
    case 'report_form':
        requireLoginForRoute();
        (new ReportController())->create();
        break;

    // Lưu báo hỏng mới
    case 'report_store':
        requireLoginForRoute();
        requirePostRequest();
        (new ReportController())->store();
        break;

    // Phân công kỹ thuật viên
    case 'report_assign':
        requireLoginForRoute();
        requirePostRequest();
        (new ReportController())->assign();
        break;

    // Xem lịch sử xử lý báo hỏng
    case 'report_history':
        requireLoginForRoute();
        (new ReportController())->history();
        break;

    // Cập nhật trạng thái và kết quả xử lý
    case 'report_update':
        requireLoginForRoute();
        requirePostRequest();
        (new ReportController())->updateStatus();
        break;



    // ==========================================
    // KHÔNG TÌM THẤY ACTION
    // ==========================================

    default:
        http_response_code(404);
        echo 'Không tìm thấy chức năng này.';
        break;
}
