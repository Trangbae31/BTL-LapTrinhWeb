
<?php

require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AuthController
{
    /**
     * Hiển thị và xử lý đăng nhập.
     */
    public function login()
    {
        // Nếu đã đăng nhập thì chuyển thẳng về dashboard.
        if (isset($_SESSION['user']['user_id'])) {
            header('Location: index.php?action=dashboard');
            exit();
        }

        $username = '';
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');

            // Không trim mật khẩu vì khoảng trắng có thể là ký tự hợp lệ.
            $password = $_POST['password'] ?? '';

            // Kiểm tra email.
            if ($username === '') {
                $errors['username'] = 'Vui lòng nhập email tài khoản!';
            } elseif (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
                $errors['username'] = 'Vui lòng nhập đúng định dạng email!';
            }

            // Kiểm tra mật khẩu.
            if ($password === '') {
                $errors['password'] = 'Vui lòng nhập mật khẩu!';
            }

            if (empty($errors)) {
                try {
                    $pdo = Database::connect();

                    /*
                     * Lấy thông tin tài khoản và toàn bộ vai trò.
                     * Một tài khoản có thể được gán nhiều vai trò.
                     */
                    $sql = "
                        SELECT
                            u.user_id,
                            u.full_name,
                            u.email,
                            u.password_hash,
                            u.status,
                            GROUP_CONCAT(
                                DISTINCT r.role_name
                                ORDER BY r.role_name
                                SEPARATOR ','
                            ) AS roles
                        FROM users u
                        LEFT JOIN user_roles ur
                            ON ur.user_id = u.user_id
                        LEFT JOIN roles r
                            ON r.role_id = ur.role_id
                        WHERE u.email = :email
                        GROUP BY
                            u.user_id,
                            u.full_name,
                            u.email,
                            u.password_hash,
                            u.status
                        LIMIT 1
                    ";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'email' => $username
                    ]);

                    $user = $stmt->fetch();

                    /*
                     * Kiểm tra:
                     * 1. Tài khoản có tồn tại không.
                     * 2. Tài khoản có đang hoạt động không.
                     * 3. Mật khẩu có chính xác không.
                     */
                    if (
                        !$user ||
                        $user['status'] !== 'active' ||
                        !password_verify(
                            $password,
                            $user['password_hash']
                        )
                    ) {
                        $errors['login'] =
                            'Email hoặc mật khẩu không đúng, hoặc tài khoản đã bị khóa.';
                    } else {

                        // Lấy danh sách vai trò.
                        $roles = array_values(array_filter(
                            explode(',', $user['roles'] ?? '')
                        ));

                        // Ngăn tài khoản không có vai trò truy cập hệ thống.
                        if (empty($roles)) {
                            $errors['login'] =
                                'Tài khoản chưa được phân quyền. Vui lòng liên hệ quản trị viên.';
                        } else {

                            // Tạo session ID mới sau khi đăng nhập thành công.
                            session_regenerate_id(true);

                            // Lưu thông tin người dùng vào session.
                            $_SESSION['user'] = [
                                'user_id' => (int) $user['user_id'],
                                'username' => $user['email'],
                                'email' => $user['email'],
                                'full_name' => $user['full_name'],
                                'fullname' => $user['full_name'],
                                'role' => $roles[0],
                                'roles' => $roles
                            ];

                            // Chuyển đến trang chủ.
                            header('Location: index.php?action=dashboard');
                            exit();
                        }
                    }

                } catch (Throwable $e) {

                    // Ghi lỗi chi tiết vào log, không hiển thị thông tin nhạy cảm.
                    error_log('Login error: ' . $e->getMessage());

                    $errors['login'] =
                        'Không thể đăng nhập. Vui lòng kiểm tra kết nối cơ sở dữ liệu.';
                }
            }
        }

        // Hiển thị lại form đăng nhập nếu có lỗi.
        require __DIR__ . '/../views/login.php';
    }

    /**
     * Đăng xuất tài khoản.
     */
    public function logout()
    {
        // Xóa toàn bộ dữ liệu session.
        $_SESSION = [];

        // Xóa cookie session trên trình duyệt.
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Hủy session hiện tại.
        session_destroy();

        // Quay lại trang đăng nhập.
        header('Location: index.php?action=login');
        exit();
    }
}
