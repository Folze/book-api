<?php

require_once __DIR__ . '/../Core/DataBase.php';



$createUsersTable = '
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB;
';


try {
    $pdo->exec($createUsersTable);
} catch (PDOException $e) {
    die('Ошибка при создании таблицы: ' . $e->getMessage());
}

class User
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function login($email, $password)
    {
        try {
            $stmt = $this->pdo->prepare('SELECT id, password FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                return ['success' => true, 'id' => $user['id']];
            }
            return ['success' => false, 'error' => 'Неверный email или пароль'];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Ошибка при входе'];
        }
    }

    public function register($email, $password)
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $this->pdo->prepare('INSERT INTO users (email, password) VALUES (?, ?)');
            $stmt->execute([$email, $hash]);
            return ['success' => true, 'message' => 'Пользователь зарегистрирован'];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Email уже занят'];
        }
    }
}
