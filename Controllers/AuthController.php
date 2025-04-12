<?php
require_once 'Core/DataBase.php';
$config = require 'includes/config.php';
require_once 'vendor/autoload.php';
$secret_key = $config['jwt_secret'];


use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');

function validateInput($email,$password){
    if (!$email) {
        http_response_code(400);
        echo json_encode(['error' => 'Email обязателен']);
        exit;
    }
    if (!$password) {
        http_response_code(400);
        echo json_encode(['error' => 'Пароль обязателен']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Некорректный email']);
        exit;
    }
}

try {
    $data = json_decode(file_get_contents("php://input"), true)?: [];
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    validateInput($email,$password);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['error' => 'Неверный логин или пароль']);
        exit;
    }

    $payload = [
        "iss" => "http://localhost",
        "aud" => "http://localhost",
        "iat" => time(),
        "exp" => time() +  3600, 
        "user_id" => $user['id']
    ];

    $jwt = JWT::encode($payload, $secret_key, 'HS256');

    echo json_encode([
        'success' => true,
        'token' => $jwt,
        'expires_in' => 3600,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

