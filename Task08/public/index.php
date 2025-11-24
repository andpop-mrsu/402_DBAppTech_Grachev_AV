<?php

require_once __DIR__ . '/../src/autoload.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    $database = new Database(__DIR__ . '/../db/minesweeper.db');
    $database->initSchema();
    
    $dbConnection = $database->getConnection();
    
    $gameController = new GameController($dbConnection);
    $moveController = new MoveController($dbConnection);
    
    $router = new Router($gameController, $moveController);
    $response = $router->route($method, $uri);
    
    if ($response['type'] === 'redirect') {
        header('Location: ' . $response['location']);
        http_response_code(302);
        exit;
    } elseif ($response['type'] === 'json') {
        http_response_code($response['status']);
        echo json_encode($response['data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
} catch (ValidationException $e) {
    error_log('Validation error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (NotFoundException $e) {
    error_log('Not found: ' . $e->getMessage());
    http_response_code(404);
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Ошибка базы данных'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log('Unexpected error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Внутренняя ошибка сервера'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
