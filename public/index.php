<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

$action = $_GET['action'] ?? 'login';
$authController = new AuthController();

switch ($action) {
    case 'login':
        $authController->login();
        break;
    case 'logout':
        $authController->logout();
        break;
    case 'dashboard':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit();
        }
        require_once __DIR__ . '/../app/views/dashboard.php';
        break;
    default:
        $authController->login();
        break;
}