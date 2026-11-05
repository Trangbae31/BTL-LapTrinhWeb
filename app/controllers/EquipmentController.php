<?php

require_once __DIR__ . '/../models/EquipmentModel.php';
require_once __DIR__ . '/../core/Authorization.php';
require_once __DIR__ . '/../core/CSRF.php';

class EquipmentController
{
    private EquipmentModel $model;

    public function __construct()
    {
        $this->model = new EquipmentModel();
    }

    private function authorizeManager(): void
    {
        Authorization::requireRole(['admin', 'manager']);
    }

    private function redirect(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];

        header('Location: index.php?action=equipment');
        exit();
    }

    private function postString(string $key): string
    {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    }

    private function validDate(string $date): bool
    {
        if ($date === '') {
            return true;
        }

        $parsed = DateTime::createFromFormat('!Y-m-d', $date);
        $errors = DateTime::getLastErrors();

        return $parsed !== false
            && $parsed->format('Y-m-d') === $date
            && (
                $errors === false
                || (
                    $errors['warning_count'] === 0
                    && $errors['error_count'] === 0
                )
            );
    }

    public function index(): void
    {
        Authorization::requireRole(['admin', 'manager', 'technician']);

        $filters = [
            'keyword' => is_string($_GET['keyword'] ?? '') ? trim($_GET['keyword']) : '',
            'room_id' => (int)($_GET['room_id'] ?? 0),
            'equipment_type_id' => (int)($_GET['equipment_type_id'] ?? 0),
            'status' => is_string($_GET['status'] ?? '') ? trim($_GET['status']) : '',
            'date_from' => is_string($_GET['date_from'] ?? '') ? trim($_GET['date_from']) : '',
            'date_to' => is_string($_GET['date_to'] ?? '') ? trim($_GET['date_to']) : ''
        ];

        $equipmentList = $this->model->getFiltered($filters);
        $rooms = $this->model->getFilterRooms();
        $types = $this->model->getTypes();
        $canManage = Authorization::hasAnyRole(['admin', 'manager']);

        require __DIR__ . '/../views/equipment_list.php';
    }

    public function form(): void
    {
        $this->authorizeManager();

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $id = $id !== false && $id !== null ? (int)$id : 0;

        $equipment = $id > 0 ? $this->model->getById($id) : null;
        $rooms = $this->model->getRooms();
        $types = $this->model->getTypes();
        $error = '';

        if ($id > 0 && $equipment === null) {
            $error = 'Không tìm thấy thiết bị cần sửa.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRF::verifyOrFail();

            $data = [
                'room_id' => (int)($_POST['room_id'] ?? 0),
                'equipment_type_id' => (int)($_POST['equipment_type_id'] ?? 0),
                'equipment_code' => $this->postString('equipment_code'),
                'equipment_name' => $this->postString('equipment_name'),
                'brand' => $this->postString('brand'),
                'purchase_date' => $this->postString('purchase_date'),
                'status' => $this->postString('status'),
                'description' => $this->postString('description')
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
                $data['room_id'] <= 0
                || $data['equipment_type_id'] <= 0
                || $data['equipment_code'] === ''
                || mb_strlen($data['equipment_code'], 'UTF-8') > 50
                || $data['equipment_name'] === ''
                || mb_strlen($data['equipment_name'], 'UTF-8') > 150
                || mb_strlen($data['brand'], 'UTF-8') > 100
            ) {
                $error = 'Vui lòng nhập đúng mã, tên, phòng, loại và hãng thiết bị.';
            } elseif (!in_array($data['status'], $allowedStatuses, true)) {
                $error = 'Trạng thái thiết bị không hợp lệ.';
            } elseif (!$this->validDate($data['purchase_date'])) {
                $error = 'Ngày mua thiết bị không hợp lệ.';
            } else {
                try {
                    $userId = Authorization::userId();

                    $success = $id > 0
                        ? $this->model->update($id, $data, $userId)
                        : $this->model->create($data, $userId);

                    if ($success) {
                        $this->redirect(
                            'success',
                            $id > 0
                                ? 'Đã cập nhật thiết bị.'
                                : 'Đã thêm thiết bị.'
                        );
                    }

                    $error = 'Không thể lưu thiết bị. Hãy kiểm tra phòng, loại thiết bị và trạng thái bảo trì.';
                } catch (PDOException $e) {
                    error_log('Save equipment error: ' . $e->getMessage());
                    $error = $e->getCode() === '23000'
                        ? 'Mã thiết bị đã tồn tại hoặc dữ liệu liên quan không hợp lệ.'
                        : 'Có lỗi xảy ra khi lưu thiết bị.';
                } catch (Throwable $e) {
                    error_log('Save equipment error: ' . $e->getMessage());
                    $error = 'Có lỗi xảy ra khi lưu thiết bị.';
                }
            }

            $equipment = array_merge($equipment ?? [], $data);
            if ($id > 0) {
                $equipment['equipment_id'] = $id;
            }
        }

        require __DIR__ . '/../views/equipment_form.php';
    }

    public function delete(): void
    {
        $this->authorizeManager();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Phương thức yêu cầu không hợp lệ.');
        }

        CSRF::verifyOrFail();

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $id = $id !== false ? (int)$id : 0;

        if ($id <= 0) {
            $this->redirect('error', 'Mã thiết bị không hợp lệ.');
        }

        try {
            $success = $this->model->delete($id, Authorization::userId());

            $this->redirect(
                $success ? 'success' : 'error',
                $success
                    ? 'Đã chuyển thiết bị sang trạng thái ngừng sử dụng.'
                    : 'Không thể ngừng sử dụng thiết bị. Thiết bị có thể đã ngừng sử dụng hoặc còn phiếu bảo trì chưa hoàn tất.'
            );
        } catch (Throwable $e) {
            error_log('Equipment delete error: ' . $e->getMessage());
            $this->redirect('error', 'Có lỗi xảy ra khi ngừng sử dụng thiết bị.');
        }
    }

    private function redirectType(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
        header('Location: index.php?action=equipment_type');
        exit();
    }

    /**
     * Quản lý loại thiết bị thuộc module Quản lý phòng và thiết bị.
     * Chỉ Admin và Manager được xem/thao tác.
     */
    public function typeIndex(): void
    {
        $this->authorizeManager();
        $equipmentTypes = $this->model->getEquipmentTypes();
        $editId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $editType = ($editId && $editId > 0) ? $this->model->getTypeById((int)$editId) : null;
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/equipment_type.php';
    }

    public function typeStore(): void
    {
        $this->authorizeManager();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Phương thức yêu cầu không hợp lệ.');
        }
        CSRF::verifyOrFail();

        $name = $this->postString('type_name');
        $description = $this->postString('description');
        if ($name === '' || mb_strlen($name, 'UTF-8') > 100) {
            $this->redirectType('error', 'Tên loại thiết bị không được để trống và tối đa 100 ký tự.');
        }

        try {
            $this->model->createType($name, $description);
            $this->redirectType('success', 'Đã thêm loại thiết bị.');
        } catch (PDOException $e) {
            error_log('Create equipment type error: ' . $e->getMessage());
            $this->redirectType('error', $e->getCode() === '23000'
                ? 'Tên loại thiết bị đã tồn tại.'
                : 'Không thể thêm loại thiết bị.');
        }
    }

    public function typeUpdate(): void
    {
        $this->authorizeManager();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Phương thức yêu cầu không hợp lệ.');
        }
        CSRF::verifyOrFail();

        $id = filter_var($_POST['equipment_type_id'] ?? null, FILTER_VALIDATE_INT);
        $name = $this->postString('type_name');
        $description = $this->postString('description');
        if (!$id || $id <= 0 || $name === '' || mb_strlen($name, 'UTF-8') > 100) {
            $this->redirectType('error', 'Thông tin loại thiết bị không hợp lệ.');
        }

        try {
            $this->model->updateType((int)$id, $name, $description);
            $this->redirectType('success', 'Đã cập nhật loại thiết bị.');
        } catch (PDOException $e) {
            error_log('Update equipment type error: ' . $e->getMessage());
            $this->redirectType('error', $e->getCode() === '23000'
                ? 'Tên loại thiết bị đã được sử dụng.'
                : 'Không thể cập nhật loại thiết bị.');
        }
    }

    public function typeDelete(): void
    {
        $this->authorizeManager();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Phương thức yêu cầu không hợp lệ.');
        }
        CSRF::verifyOrFail();

        $id = filter_var($_POST['equipment_type_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id <= 0) {
            $this->redirectType('error', 'Mã loại thiết bị không hợp lệ.');
        }

        try {
            $success = $this->model->deleteType((int)$id);
            $this->redirectType(
                $success ? 'success' : 'error',
                $success
                    ? 'Đã xóa loại thiết bị.'
                    : 'Không thể xóa loại này vì không tồn tại hoặc đang được thiết bị sử dụng.'
            );
        } catch (Throwable $e) {
            error_log('Delete equipment type error: ' . $e->getMessage());
            $this->redirectType('error', 'Có lỗi xảy ra khi xóa loại thiết bị.');
        }
    }

}
