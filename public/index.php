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
        require_once __DIR__ . '/../app/controllers/RoomController.php';
        require_once __DIR__ . '/../app/controllers/EquipmentController.php';
        break;
    case 'rooms':
    if (!isset($_SESSION['user'])) {
        header('Location: index.php?action=login');
        exit();
    }
    (new RoomController())->index();
    break;

case 'room_form':
    if (!isset($_SESSION['user'])) {
        header('Location: index.php?action=login');
        exit();
    }
    (new RoomController())->form();
    break;

case 'room_delete':
    if (!isset($_SESSION['user'])) {
        header('Location: index.php?action=login');
        exit();
    }
    (new RoomController())->delete();
    break;

case 'equipment':
    if (!isset($_SESSION['user'])) {
        header('Location: index.php?action=login');
        exit();
    }
    (new EquipmentController())->index();
    break;

case 'equipment_form':
    if (!isset($_SESSION['user'])) {
        header('Location: index.php?action=login');
        exit();
    }
    (new EquipmentController())->form();
    break;

case 'equipment_delete':
    if (!isset($_SESSION['user'])) {
        header('Location: index.php?action=login');
        exit();
    }
    (new EquipmentController())->delete();
    break;
    default:
        $authController->login();
        break;
}