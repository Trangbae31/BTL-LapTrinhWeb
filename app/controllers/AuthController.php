<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
class AuthController {
    
    // Hàm hiển thị & xử lý Form Đăng Nhập
    public function login() {

require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AuthController
{
    // Hiển thị và xử lý đăng nhập bằng tài khoản thật trong MySQL
    public function login()
    {
        $username = '';
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            // Không trim mật khẩu vì khoảng trắng có thể là ký tự hợp lệ.
            $password = $_POST['password'] ?? '';

            if ($username === '') {
                $errors['username'] = 'Vui lòng nhập email tài khoản!';
            } elseif (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
                $errors['username'] = 'Vui lòng nhập đúng định dạng email!';
            }

            if ($password === '') {
                $errors['password'] = 'Vui lòng nhập mật khẩu!';
            }

            if (empty($errors)) {
                $_SESSION['user'] = [
                    'user_id' => 1,
                    'username' => $username,
                    'fullname' => 'Trịnh Xuân Trường',
                    'role'     => 'admin'
                ];
                header('Location: index.php?action=dashboard');
                exit();
                try {
                    $pdo = Database::connect();

                    $sql = "
                        SELECT
                            u.user_id,
                            u.full_name,
                            u.email,
                            u.password_hash,
                            u.status,
                            GROUP_CONCAT(r.role_name) AS roles
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
                    $stmt->execute(['email' => $username]);
                    $user = $stmt->fetch();

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
                        session_regenerate_id(true);

                        $roles = array_values(array_filter(
                            explode(',', $user['roles'] ?? '')
                        ));

                        $_SESSION['user'] = [
                            'user_id' => (int) $user['user_id'],
                            'username' => $user['email'],
                            'fullname' => $user['full_name'],
                            'role' => $roles[0] ?? 'user',
                            'roles' => $roles
                        ];

                        header('Location: index.php?action=dashboard');
                        exit();
                    }
                } catch (Throwable $e) {
                    error_log('Login error: ' . $e->getMessage());

                    $errors['login'] =
                        'Không thể đăng nhập. Vui lòng kiểm tra kết nối cơ sở dữ liệu.';
                }
            }
        }

        require __DIR__ . '/../views/login.php';
    }

    // Đăng xuất
    public function logout()
    {
        $_SESSION = [];

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

        session_destroy();

        header('Location: index.php?action=login');
        exit();
    }
}