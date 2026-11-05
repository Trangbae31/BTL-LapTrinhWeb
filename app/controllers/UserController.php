<?php

require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../core/Authorization.php';
require_once __DIR__ . '/../core/CSRF.php';

class UserController
{
    private UserModel $model;

    public function __construct()
    {
        $this->model = new UserModel();
    }

    private function authorizeAdmin(): void
    {
        Authorization::requireRole(['admin']);
    }

    private function redirect(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
        header('Location: index.php?action=users');
        exit();
    }

    public function index(): void
    {
        $this->authorizeAdmin();
        $users = $this->model->getAll();
        $roles = $this->model->getRoles();
        $editId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $editUser = ($editId && $editId > 0) ? $this->model->getById((int)$editId) : null;
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/user_list.php';
    }

    public function store(): void
    {
        $this->authorizeAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Phương thức yêu cầu không hợp lệ.');
        }
        CSRF::verifyOrFail();

        $data = [
            'full_name' => trim((string)($_POST['full_name'] ?? '')),
            'email' => trim((string)($_POST['email'] ?? '')),
            'password' => (string)($_POST['password'] ?? ''),
            'role_ids' => array_values(array_unique(array_map('intval', (array)($_POST['role_ids'] ?? [])))),
            'status' => (string)($_POST['status'] ?? 'active')
        ];

        if ($data['full_name'] === '' || mb_strlen($data['full_name'], 'UTF-8') > 100
            || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)
            || strlen($data['password']) < 8
            || empty($data['role_ids'])) {
            $this->redirect('error', 'Vui lòng nhập tên, email hợp lệ và mật khẩu tối thiểu 8 ký tự.');
        }

        try {
            $created = $this->model->create($data);
            $this->redirect(
                $created ? 'success' : 'error',
                $created ? 'Đã tạo tài khoản và gán vai trò.' : 'Dữ liệu tài khoản hoặc vai trò không hợp lệ.'
            );
        } catch (PDOException $e) {
            error_log('Create user error: ' . $e->getMessage());
            $this->redirect('error', $e->getCode() === '23000' ? 'Email đã được sử dụng.' : 'Không thể tạo tài khoản.');
        } catch (Throwable $e) {
            error_log('Create user error: ' . $e->getMessage());
            $this->redirect('error', 'Không thể tạo tài khoản.');
        }
    }

    public function update(): void
    {
        $this->authorizeAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Phương thức yêu cầu không hợp lệ.');
        }
        CSRF::verifyOrFail();

        $id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id <= 0) {
            $this->redirect('error', 'Mã tài khoản không hợp lệ.');
        }

        $data = [
            'full_name' => trim((string)($_POST['full_name'] ?? '')),
            'email' => trim((string)($_POST['email'] ?? '')),
            'password' => (string)($_POST['password'] ?? ''),
            'role_ids' => array_values(array_unique(array_map('intval', (array)($_POST['role_ids'] ?? [])))),
            'status' => (string)($_POST['status'] ?? 'active')
        ];

        if ((int)$id === Authorization::userId() && $data['status'] !== 'active') {
            $this->redirect('error', 'Bạn không thể tự khóa tài khoản đang đăng nhập.');
        }

        if ($data['full_name'] === '' || mb_strlen($data['full_name'], 'UTF-8') > 100
            || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)
            || ($data['password'] !== '' && strlen($data['password']) < 8)
            || empty($data['role_ids'])) {
            $this->redirect('error', 'Thông tin không hợp lệ. Mật khẩu mới nếu có phải tối thiểu 8 ký tự.');
        }

        try {
            $success = $this->model->update((int)$id, $data);
            $this->redirect(
                $success ? 'success' : 'error',
                $success ? 'Đã cập nhật tài khoản.' : 'Không thể cập nhật. Hãy bảo đảm hệ thống còn ít nhất một Admin đang hoạt động.'
            );
        } catch (PDOException $e) {
            error_log('Update user error: ' . $e->getMessage());
            $this->redirect('error', $e->getCode() === '23000' ? 'Email đã được sử dụng.' : 'Không thể cập nhật tài khoản.');
        } catch (Throwable $e) {
            error_log('Update user error: ' . $e->getMessage());
            $this->redirect('error', 'Có lỗi xảy ra khi cập nhật tài khoản.');
        }
    }
}
