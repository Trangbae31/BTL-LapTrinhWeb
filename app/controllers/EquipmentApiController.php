<?php

require_once __DIR__ . '/../models/EquipmentModel.php';
require_once __DIR__ . '/../core/Authorization.php';

class EquipmentApiController
{
    private ?EquipmentModel $model = null;

    private function model(): EquipmentModel
    {
        if ($this->model === null) {
            $this->model = new EquipmentModel();
        }
        return $this->model;
    }

    private function authorizeJson(): bool
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        if (!Authorization::isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.'], JSON_UNESCAPED_UNICODE);
            return false;
        }
        try {
            $stmt = Database::connect()->prepare('SELECT status FROM users WHERE user_id = :id LIMIT 1');
            $stmt->execute(['id' => Authorization::userId()]);
            if ($stmt->fetchColumn() !== 'active') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Tài khoản không hoạt động.'], JSON_UNESCAPED_UNICODE);
                return false;
            }
        } catch (Throwable $e) {
            error_log('API authorization error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Không thể xác thực tài khoản.'], JSON_UNESCAPED_UNICODE);
            return false;
        }
        if (!Authorization::hasAnyRole(['admin', 'manager', 'technician', 'teacher'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập API.'], JSON_UNESCAPED_UNICODE);
            return false;
        }
        return true;
    }

    /**
     * API lấy danh sách và tìm kiếm thiết bị.
     */
    public function index(): void
    {
        if (!$this->authorizeJson()) return;
        if (!Authorization::hasAnyRole(['admin', 'manager', 'technician'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập API.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $keywordRaw = $_GET['keyword'] ?? '';
            $keyword = is_string($keywordRaw) ? trim($keywordRaw) : '';

            $equipmentList = $keyword !== ''
                ? $this->model()->search($keyword)
                : $this->model()->getAll();

            http_response_code(200);

            echo json_encode([
                'success' => true,
                'message' => 'Lấy danh sách thiết bị thành công.',
                'data' => $equipmentList
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            error_log('Equipment API error: ' . $e->getMessage());

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'Không thể lấy danh sách thiết bị.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Endpoint JSON kiểm tra phòng còn trống trong khoảng thời gian.
     * GET: room_id, start_time, end_time (datetime-local hoặc Y-m-d H:i:s).
     */
    public function roomAvailability(): void
    {
        if (!$this->authorizeJson()) return;
        if (!Authorization::hasAnyRole(['admin', 'manager', 'teacher'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền kiểm tra phòng.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $roomId = filter_var($_GET['room_id'] ?? null, FILTER_VALIDATE_INT);
        $startRaw = $_GET['start_time'] ?? '';
        $endRaw = $_GET['end_time'] ?? '';

        $parse = static function ($value): ?DateTime {
            if (!is_string($value) || trim($value) === '') return null;
            $value = trim($value);
            foreach (['!Y-m-d\TH:i', '!Y-m-d H:i:s', '!Y-m-d H:i'] as $format) {
                $date = DateTime::createFromFormat($format, $value);
                $errors = DateTime::getLastErrors();
                if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                    return $date;
                }
            }
            return null;
        };

        $start = $parse($startRaw);
        $end = $parse($endRaw);
        if (!$roomId || $roomId <= 0 || !$start || !$end || $start >= $end) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Vui lòng cung cấp phòng và khoảng thời gian hợp lệ.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $result = $this->model()->checkRoomAvailability(
                (int)$roomId,
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s')
            );

            if (!$result['exists']) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy phòng.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            echo json_encode([
                'success' => true,
                'available' => $result['available'],
                'room' => $result['room'] ?? null,
                'conflicts' => $result['conflicts'] ?? [],
                'message' => $result['available']
                    ? 'Phòng còn trống trong khoảng thời gian đã chọn.'
                    : (($result['reason'] ?? '') === 'inactive'
                        ? 'Phòng hiện không hoạt động.'
                        : 'Phòng đã có lịch được duyệt trùng thời gian.')
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('Room availability API error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Không thể kiểm tra tình trạng phòng.'], JSON_UNESCAPED_UNICODE);
        }
    }

}
