<?php

// Подключить автозагрузчик Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Подключить кастомный автозагрузчик для классов из src/
require_once __DIR__ . '/../src/autoload.php';

// Подключить Router.php для доступа к классам исключений
require_once __DIR__ . '/../src/Router.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

// Создать экземпляр Slim приложения через AppFactory
$app = AppFactory::create();

// Добавить ErrorMiddleware для обработки ошибок
// Параметры: displayErrorDetails, logErrors, logErrorDetails
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Добавить middleware для установки CORS заголовков
$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Инициализировать Database с путём к БД
$database = new Database(__DIR__ . '/../db/minesweeper.db');
$database->initSchema();

// Получить PDO соединение
$dbConnection = $database->getConnection();

// Создать экземпляры контроллеров с PDO
$gameController = new GameController($dbConnection);
$moveController = new MoveController($dbConnection);

// Определение маршрутов

// GET / - редирект на /index.html
$app->get('/', function (Request $request, Response $response) {
    return $response
        ->withHeader('Location', '/index.html')
        ->withStatus(302);
});

// GET /games - получение списка всех игр
$app->get('/games', function (Request $request, Response $response) use ($gameController) {
    try {
        $games = $gameController->getAllGames();
        $response->getBody()->write(json_encode($games));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withStatus(200);
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        $response->getBody()->write(json_encode(['error' => 'Ошибка базы данных']));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    } catch (Exception $e) {
        error_log('Error: ' . $e->getMessage());
        $response->getBody()->write(json_encode(['error' => 'Внутренняя ошибка сервера']));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});

// GET /games/{id} - получение игры по ID
$app->get('/games/{id}', function (Request $request, Response $response, array $args) use ($gameController) {
    try {
        $id = (int)$args['id'];
        $game = $gameController->getGameById($id);
        $response->getBody()->write(json_encode($game));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withStatus(200);
    } catch (NotFoundException $e) {
        error_log('Not found: ' . $e->getMessage());
        $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(404);
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        $response->getBody()->write(json_encode(['error' => 'Ошибка базы данных']));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    } catch (Exception $e) {
        error_log('Error: ' . $e->getMessage());
        $response->getBody()->write(json_encode(['error' => 'Внутренняя ошибка сервера']));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});

// POST /games - создание новой игры
$app->post('/games', function (Request $request, Response $response) use ($gameController) {
    try {
        $data = json_decode($request->getBody()->getContents(), true);
        $result = $gameController->createGame($data);
        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withStatus(201);
    } catch (ValidationException $e) {
        error_log('Validation error: ' . $e->getMessage());
        $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(400);
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        $response->getBody()->write(json_encode(['error' => 'Ошибка базы данных']));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    } catch (Exception $e) {
        error_log('Error: ' . $e->getMessage());
        $response->getBody()->write(json_encode(['error' => 'Внутренняя ошибка сервера']));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});

// POST /step/{id} - добавление хода к игре
$app->post('/step/{id}', function (Request $request, Response $response, array $args) use ($moveController) {
    try {
        $gameId = (int)$args['id'];
        $data = json_decode($request->getBody()->getContents(), true);
        $result = $moveController->addMove($gameId, $data);
        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withStatus(200);
    } catch (NotFoundException $e) {
        error_log('Not found: ' . $e->getMessage());
        $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(404);
    } catch (ValidationException $e) {
        error_log('Validation error: ' . $e->getMessage());
        $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(400);
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        $response->getBody()->write(json_encode(['error' => 'Ошибка базы данных']));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    } catch (Exception $e) {
        error_log('Error: ' . $e->getMessage());
        $response->getBody()->write(json_encode(['error' => 'Внутренняя ошибка сервера']));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});

// Обработка OPTIONS запросов для CORS preflight
$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response
        ->withStatus(200);
});

// Запуск приложения
$app->run();
