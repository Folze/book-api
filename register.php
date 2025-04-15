<?php

require_once 'Core/DataBase.php';
require_once 'Models/User.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data     = json_decode(file_get_contents('php://input'), true);
    $email    = $data['email']    ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        error_log(json_encode(['error' => 'Email и пароль обязательны']));
        exit;
    }

    $user   = new User($pdo);
    $result = $user->register($email, $password);

    if ($result['success']) {
        error_log(json_encode(['success' => true, 'user_id' => $result['id']]));
    } else {
        http_response_code(400);
        error_log(json_encode(['error' => $result['error']]));
    }
}
