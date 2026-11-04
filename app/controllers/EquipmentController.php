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

        $keyword = $_GET['keyword'] ?? '';
        $keyword = is_string($keyword) ? trim($keyword) : '';

        $equipmentList = $keyword !== ''
            ? $this->model->search($keyword)
            : $this->model->getAll();

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
}
