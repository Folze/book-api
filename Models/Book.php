<?php 
require_once __DIR__ . '/../Core/DataBase.php';


$createBooksTable = "CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    genre VARCHAR(100),
    year INT,
    description TEXT,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

try {
    $pdo->exec($createBooksTable);
} catch (PDOException $e) {
    echo "<p>Ошибка при создании таблицы: " . $e->getMessage() . "</p>";
}