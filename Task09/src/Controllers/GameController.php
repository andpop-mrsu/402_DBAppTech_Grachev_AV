<?php

class GameController {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function createGame(array $data): array {
        $this->validateGameData($data);

        $playerName = $data['player_name'];
        $fieldSize = (int)$data['field_size'];
        $minesCount = (int)$data['mines_count'];
        $minePositions = json_encode($data['mine_positions']);
        $datePlayed = date('c');
        $createdAt = date('c');

        $stmt = $this->db->prepare("
            INSERT INTO games (player_name, date_played, field_size, mines_count, mine_positions, game_result, total_moves, created_at)
            VALUES (:player_name, :date_played, :field_size, :mines_count, :mine_positions, NULL, 0, :created_at)
        ");

        $stmt->execute([
            'player_name' => $playerName,
            'date_played' => $datePlayed,
            'field_size' => $fieldSize,
            'mines_count' => $minesCount,
            'mine_positions' => $minePositions,
            'created_at' => $createdAt
        ]);

        $gameId = (int)$this->db->lastInsertId();

        return [
            'id' => $gameId,
            'message' => 'Игра успешно создана'
        ];
    }

    public function getAllGames(): array {
        $stmt = $this->db->query("
            SELECT id, player_name, date_played, field_size, mines_count, game_result, total_moves
            FROM games
            ORDER BY date_played DESC
        ");

        $games = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $games[] = [
                'id' => (int)$row['id'],
                'player_name' => $row['player_name'],
                'date_played' => $row['date_played'],
                'field_size' => (int)$row['field_size'],
                'mines_count' => (int)$row['mines_count'],
                'game_result' => $row['game_result'],
                'total_moves' => (int)$row['total_moves']
            ];
        }

        return $games;
    }

    public function getGameById(int $id): array {
        $stmt = $this->db->prepare("
            SELECT id, player_name, date_played, field_size, mines_count, mine_positions, game_result, total_moves
            FROM games
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$game) {
            throw new NotFoundException('Игра не найдена');
        }

        $stmt = $this->db->prepare("
            SELECT move_number, row_coord, col_coord, move_type, result
            FROM moves
            WHERE game_id = :game_id
            ORDER BY move_number ASC
        ");
        $stmt->execute(['game_id' => $id]);
        $moves = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $moves[] = [
                'move_number' => (int)$row['move_number'],
                'row_coord' => (int)$row['row_coord'],
                'col_coord' => (int)$row['col_coord'],
                'move_type' => $row['move_type'],
                'result' => $row['result']
            ];
        }

        return [
            'id' => (int)$game['id'],
            'player_name' => $game['player_name'],
            'date_played' => $game['date_played'],
            'field_size' => (int)$game['field_size'],
            'mines_count' => (int)$game['mines_count'],
            'mine_positions' => json_decode($game['mine_positions'], true),
            'game_result' => $game['game_result'] ?? null,
            'total_moves' => (int)$game['total_moves'],
            'moves' => $moves
        ];
    }

    private function validateGameData(array $data): void {
        if (!isset($data['player_name'])) {
            throw new ValidationException('Отсутствует обязательное поле: player_name');
        }

        if (!isset($data['field_size'])) {
            throw new ValidationException('Отсутствует обязательное поле: field_size');
        }

        if (!isset($data['mines_count'])) {
            throw new ValidationException('Отсутствует обязательное поле: mines_count');
        }

        if (!isset($data['mine_positions'])) {
            throw new ValidationException('Отсутствует обязательное поле: mine_positions');
        }

        if (!is_string($data['player_name']) || trim($data['player_name']) === '') {
            throw new ValidationException('Имя игрока должно быть непустой строкой');
        }

        $fieldSize = $data['field_size'];
        if (!is_numeric($fieldSize) || $fieldSize < 2 || $fieldSize > 30) {
            throw new ValidationException('Размер поля должен быть от 2 до 30');
        }

        $minesCount = $data['mines_count'];
        if (!is_numeric($minesCount) || $minesCount < 1) {
            throw new ValidationException('Количество мин должно быть положительным числом');
        }

        $maxMines = $fieldSize * $fieldSize - 1;
        if ($minesCount > $maxMines) {
            throw new ValidationException('Количество мин не может превышать ' . $maxMines . ' для поля размером ' . $fieldSize);
        }

        if (!is_array($data['mine_positions'])) {
            throw new ValidationException('mine_positions должно быть массивом');
        }

        foreach ($data['mine_positions'] as $position) {
            if (!is_array($position) || !isset($position['row']) || !isset($position['col'])) {
                throw new ValidationException('Каждая позиция мины должна содержать поля row и col');
            }

            if (!is_numeric($position['row']) || !is_numeric($position['col'])) {
                throw new ValidationException('Координаты мины должны быть числами');
            }

            $row = (int)$position['row'];
            $col = (int)$position['col'];

            if ($row < 0 || $row >= $fieldSize || $col < 0 || $col >= $fieldSize) {
                throw new ValidationException('Координаты мины должны быть в пределах поля');
            }
        }
    }
}
