<?php 
require_once '//Core/DataBase.php';

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
    echo "<p>Таблица 'books' создана или уже существует</p>";
} catch (PDOException $e) {
    echo "<p>Ошибка при создании таблицы: " . $e->getMessage() . "</p>";
}