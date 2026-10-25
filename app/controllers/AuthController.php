<?php
session_start();

class AuthController {
    
    // Hàm hiển thị & xử lý Form Đăng Nhập
    public function login() {
        $username = '';
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($username)) {
                $errors['username'] = 'Vui lòng nhập tên tài khoản!';
            } elseif (strlen($username) < 3) {
                $errors['username'] = 'Tên tài khoản phải có ít nhất 3 kí tự.';
            }

            if (empty($password)) {
                $errors['password'] = 'Mật khẩu không được để trống!';
            } elseif (strlen($password) < 6) {
                $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
            }

            // 3. Nếu không có lỗi -> Giả lập đăng nhập thành công
            if (empty($errors)) {
                $_SESSION['user'] = [
                    'username' => $username,
                    'fullname' => 'Trịnh Xuân Trường',
                    'role'     => 'admin'
                ];
                header('Location: index.php?action=dashboard');
                exit();
            }
        }

        // Gọi View hiển thị Form
        require_once __DIR__ . '/../views/login.php';
    }

    // Đăng xuất
    public function logout() {
        unset($_SESSION['user']);
        session_destroy();
        header('Location: index.php?action=login');
        exit();
    }
}