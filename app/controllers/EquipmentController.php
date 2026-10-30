<?php

require_once __DIR__ . '/../models/EquipmentModel.php';
require_once __DIR__ . '/../core/Authorization.php';

class EquipmentController
{
    private EquipmentModel $model;

    public function __construct()
    {
        $this->model = new EquipmentModel();
    }

    /**
     * Admin và manager được quản lý thiết bị.
     */
    private function authorizeManager(): void
    {
        Authorization::requireRole(['admin', 'manager']);
    }

    /**
     * Hiển thị danh sách thiết bị.
     * Technician được phép xem để phục vụ công việc bảo trì.
     */
    public function index(): void
    {
        Authorization::requireRole(['admin', 'manager', 'technician']);

        $keyword = trim($_GET['keyword'] ?? '');

        $equipmentList = $keyword !== ''
            ? $this->model->search($keyword)
            : $this->model->getAll();

        $canManage = Authorization::hasAnyRole(['admin', 'manager']);

        require __DIR__ . '/../views/equipment_list.php';
    }

    /**
     * Thêm hoặc sửa thiết bị.
     */
    public function form(): void
    {
        $this->authorizeManager();

        $id = (int)($_GET['id'] ?? 0);

        $equipment = $id > 0
            ? $this->model->getById($id)
            : null;

        $rooms = $this->model->getRooms();
        $types = $this->model->getTypes();
        $error = '';

        if ($id > 0 && $equipment === null) {
            $error = 'Không tìm thấy thiết bị cần sửa.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'room_id' => (int)($_POST['room_id'] ?? 0),
                'equipment_type_id' => (int)($_POST['equipment_type_id'] ?? 0),
                'equipment_code' => trim($_POST['equipment_code'] ?? ''),
                'equipment_name' => trim($_POST['equipment_name'] ?? ''),
                'brand' => trim($_POST['brand'] ?? ''),
                'purchase_date' => trim($_POST['purchase_date'] ?? ''),
                'status' => $_POST['status'] ?? 'available',
                'description' => trim($_POST['description'] ?? '')
            ];

            $allowedStatuses = [
                'available',
                'maintenance',
                'broken',
                'retired'
            ];

            if ($id > 0 && $equipment === null) {
                $error = 'Không tìm thấy thiết bị cần sửa.';
            } elseif (
                $data['room_id'] <= 0 ||
                $data['equipment_type_id'] <= 0 ||
                $data['equipment_code'] === '' ||
                $data['equipment_name'] === ''
            ) {
                $error = 'Vui lòng nhập đủ mã, tên, phòng và loại thiết bị.';
            } elseif (
                !in_array($data['status'], $allowedStatuses, true)
            ) {
                $error = 'Trạng thái thiết bị không hợp lệ.';
            } elseif (
                $data['purchase_date'] !== '' &&
                !DateTime::createFromFormat('!Y-m-d', $data['purchase_date'])
            ) {
                $error = 'Ngày mua thiết bị không hợp lệ.';
            } else {
                try {
                    if ($id > 0) {
                        $this->model->update($id, $data);
                    } else {
                        $this->model->create($data);
                    }

                    header('Location: index.php?action=equipment');
                    exit();

                } catch (PDOException $e) {
                    error_log('Save equipment error: ' . $e->getMessage());
                    $error = 'Không lưu được thiết bị. Hãy kiểm tra mã thiết bị và dữ liệu.';
                }
            }

            $equipment = $data;
        }

        require __DIR__ . '/../views/equipment_form.php';
    }

    /**
     * Xóa thiết bị.
     */
    public function delete(): void
    {
        $this->authorizeManager();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Phương thức yêu cầu không hợp lệ.');
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Mã thiết bị không hợp lệ.'
            ];

            header('Location: index.php?action=equipment');
            exit();
        }

        try {
            $success = $this->model->delete($id);

            $_SESSION['flash'] = [
                'type' => $success ? 'success' : 'error',
                'message' => $success
                    ? 'Đã xóa thiết bị.'
                    : 'Không thể xóa thiết bị này.'
            ];

        } catch (PDOException $e) {
            error_log('Delete equipment error: ' . $e->getMessage());

            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Không thể xóa thiết bị vì đang có phiếu báo hỏng hoặc lịch sử bảo trì liên quan.'
            ];
        }

        header('Location: index.php?action=equipment');
        exit();
    }
}