<?php

class Router {
    private $gameController;
    private $moveController;

    public function __construct($gameController, $moveController) {
        $this->gameController = $gameController;
        $this->moveController = $moveController;
    }

    public function route(string $method, string $uri): array {
        $uri = strtok($uri, '?');
        
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }
        
        if (empty($uri)) {
            $uri = '/';
        }

        if ($method === 'GET' && $uri === '/') {
            return [
                'type' => 'redirect',
                'location' => '/index.html'
            ];
        }

        if ($method === 'GET' && $uri === '/games') {
            return [
                'type' => 'json',
                'data' => $this->gameController->getAllGames(),
                'status' => 200
            ];
        }

        if ($method === 'GET' && preg_match('#^/games/(\d+)$#', $uri, $matches)) {
            $gameId = (int)$matches[1];
            return [
                'type' => 'json',
                'data' => $this->gameController->getGameById($gameId),
                'status' => 200
            ];
        }

        if ($method === 'POST' && $uri === '/games') {
            $data = $this->getJsonInput();
            return [
                'type' => 'json',
                'data' => $this->gameController->createGame($data),
                'status' => 201
            ];
        }

        if ($method === 'POST' && preg_match('#^/step/(\d+)$#', $uri, $matches)) {
            $gameId = (int)$matches[1];
            $data = $this->getJsonInput();
            return [
                'type' => 'json',
                'data' => $this->moveController->addMove($gameId, $data),
                'status' => 200
            ];
        }

        throw new NotFoundException('Маршрут не найден: ' . $method . ' ' . $uri);
    }

    private function getJsonInput(): array {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            return [];
        }
        
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationException('Невалидный JSON: ' . json_last_error_msg());
        }
        
        return $data ?? [];
    }
}

class NotFoundException extends Exception {
    public function __construct($message = "Ресурс не найден", $code = 404, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

class ValidationException extends Exception {
    public function __construct($message = "Ошибка валидации", $code = 400, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
