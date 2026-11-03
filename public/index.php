
<?php

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

$action = $_GET['action'] ?? 'login';

$authController = new AuthController();

// ==========================================
// KIỂM TRA ĐĂNG NHẬP
// ==========================================

function requireLoginForRoute(): void
{
    if (!isset($_SESSION['user']['user_id'])) {
        header('Location: index.php?action=login');
        exit();
    }
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
        requireLoginForRoute();
        (new EquipmentApiController())->index();
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

    // Hiển thị form chỉnh sửa báo hỏng
    case 'report_edit':
        requireLoginForRoute();
        (new ReportController())->edit();
        break;

    // Lưu thông tin chỉnh sửa báo hỏng
    case 'report_update_info':
        requireLoginForRoute();
        requirePostRequest();
        (new ReportController())->update();
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

    // Xóa báo hỏng
    case 'report_delete':
        requireLoginForRoute();
        requirePostRequest();
        (new ReportController())->delete();
        break;


    // ==========================================
    // KHÔNG TÌM THẤY ACTION
    // ==========================================

    default:
        http_response_code(404);
        echo 'Không tìm thấy chức năng này.';
        break;
}
