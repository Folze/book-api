<?php

require_once __DIR__ . '/../Core/DataBase.php';
require_once __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../Core/Config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

$headers = getallheaders();
$token   = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

try {
    $decoded = JWT::decode($token, new Key($config['jwt_secret'], 'HS256'));
    $user_id = $decoded->user_id;
} catch (Exception $e) {
    http_response_code(401);
    error_log(json_encode(['error' => 'Неавторизованный доступ']));
    exit;
}

switch ($method) {
    case 'POST':
        // Обработка создания новой книги
        $data = json_decode(file_get_contents('php://input'), true);

        $title       = $data['title']       ?? '';
        $author      = $data['author']      ?? '';
        $genre       = $data['genre']       ?? null;
        $year        = $data['year']        ?? null;
        $description = $data['description'] ?? null;

        if (!$data) {
            http_response_code(400);
            error_log(json_encode(['error' => 'Нет данных']));
            break;
        }
        if (!$title || !$author) {
            http_response_code(400);
            error_log(json_encode(['error' => 'Поле title и author обязательны']));
            break;
        }

        try {
            $stmt = $pdo->prepare('INSERT INTO books (user_id, title, author, genre, year, description) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$user_id, $title, $author, $genre, $year, $description]);
            error_log(json_encode(['success' => true, 'book_id' => $pdo->lastInsertId()]));
        } catch (PDOException $e) {
            http_response_code(500);
            error_log(json_encode(['error' => $e->getMessage()]));
        }
        break;

    case 'GET':
        // Обработка GET-запроса
        if (isset($_GET['search'])) {
            // Поиск через Google Books API
            $query   = urlencode($_GET['search']);
            $api_url = "https://www.googleapis.com/books/v1/volumes?q={$query}&maxResults=5";

            $response = @file_get_contents($api_url);
            if ($response === false) {
                http_response_code(500);
                error_log(json_encode(['error' => 'Ошибка при запросе к Google Books']));
                break;
            }

            $data  = json_decode($response, true);
            $books = array_map(function ($item) {
                return [
                    'title'       => $item['volumeInfo']['title']       ?? 'Без названия',
                    'author'      => $item['volumeInfo']['authors'][0]  ?? 'Неизвестен',
                    'description' => $item['volumeInfo']['description'] ?? ''
                ];
            }, $data['items'] ?? []);

            error_log(json_encode(['books' => $books]));
        } else {
            // Получение книг из базы данных
            try {
                if (isset($_GET['id'])) {
                    $id   = (int) $_GET['id'];
                    $stmt = $pdo->prepare('SELECT * FROM books WHERE id = ? AND user_id = ? AND is_deleted = FALSE');
                    $stmt->execute([$id, $user_id]);
                    $book = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($book) {
                        error_log(json_encode(['success' => true, 'book' => $book]));
                    } else {
                        http_response_code(404);
                        error_log(json_encode(['error' => 'Книга не найдена']));
                    }
                } else {
                    $stmt = $pdo->prepare('SELECT * FROM books WHERE user_id = ? AND is_deleted = FALSE');
                    $stmt->execute([$user_id]);
                    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    error_log(json_encode(['success' => true, 'books' => $books]));
                }
            } catch (PDOException $e) {
                http_response_code(500);
                error_log(json_encode(['error' => $e->getMessage()]));
            }
        }
        break;

    case 'PUT':
        // Обновление данных книг
        $id = (int) ($_GET['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            error_log(json_encode(['error' => 'Не указан ID книги для обновления']));
            break;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            http_response_code(400);
            error_log(json_encode(['error' => 'Нет данных для обновления']));
            break;
        }

        try {
            $stmt = $pdo->prepare('UPDATE books SET 
                title = COALESCE(?, title), 
                author = COALESCE(?, author),
                genre = COALESCE(?, genre),
                year = COALESCE(?, year),
                description = COALESCE(?, description)
                WHERE id = ? AND user_id = ? AND is_deleted = FALSE');

            $stmt->execute([
                $data['title']       ?? null,
                $data['author']      ?? null,
                $data['genre']       ?? null,
                $data['year']        ?? null,
                $data['description'] ?? null,
                $id,
                $user_id
            ]);

            if ($stmt->rowCount()) {
                error_log(json_encode(['success' => true, 'message' => 'Книга обновлена']));
            } else {
                http_response_code(404);
                error_log(json_encode(['error' => 'Книга не найдена или данные те же']));
            }
        } catch (PDOException $e) {
            http_response_code(500);
            error_log(json_encode(['error' => $e->getMessage()]));
        }
        break;

    case 'PATCH':
        // Восстановление удаленной книги
        if (isset($_GET['restore'])) {
            $id = (int) $_GET['id'];

            try {
                $stmt = $pdo->prepare('UPDATE books SET is_deleted = FALSE WHERE id = ? AND user_id = ?');
                $stmt->execute([$id, $user_id]);

                if ($stmt->rowCount()) {
                    error_log(json_encode(['success' => true, 'message' => 'Книга восстановлена']));
                } else {
                    http_response_code(404);
                    error_log(json_encode(['error' => 'Книга не найдена или уже восстановлена']));
                }
            } catch (PDOException $e) {
                http_response_code(500);
                error_log(json_encode(['error' => $e->getMessage()]));
            }
        } else {
            http_response_code(400);
            error_log(json_encode(['error' => 'Неверный запрос']));
        }
        break;

    case 'DELETE':
        // Удаление книги
        $id = (int) ($_GET['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            error_log(json_encode(['error' => 'Не указан ID книги для удаления']));
            break;
        }

        try {
            $stmt = $pdo->prepare('UPDATE books SET is_deleted = TRUE WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $user_id]);

            if ($stmt->rowCount()) {
                error_log(json_encode(['success' => true, 'message' => 'Книга помечена как удаленная']));
            } else {
                http_response_code(404);
                error_log(json_encode(['error' => 'Книга не найдена']));
            }
        } catch (PDOException $e) {
            http_response_code(500);
            error_log(json_encode(['error' => $e->getMessage()]));
        }
        break;

    default:
        http_response_code(405);
        error_log(json_encode(['error' => 'Метод не поддерживается']));
        break;
}
