<?php

class MoveController {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function addMove(int $gameId, array $data): array {
        $this->validateGameExists($gameId);
        $this->validateMoveData($data);
        
        $moveNumber = (int)$data['move_number'];
        $rowCoord = (int)$data['row_coord'];
        $colCoord = (int)$data['col_coord'];
        $moveType = $data['move_type'];
        $result = $data['result'];
        
        $stmt = $this->db->prepare("
            INSERT INTO moves (game_id, move_number, row_coord, col_coord, move_type, result)
            VALUES (:game_id, :move_number, :row_coord, :col_coord, :move_type, :result)
        ");
        
        $stmt->execute([
            'game_id' => $gameId,
            'move_number' => $moveNumber,
            'row_coord' => $rowCoord,
            'col_coord' => $colCoord,
            'move_type' => $moveType,
            'result' => $result
        ]);
        
        $this->updateTotalMoves($gameId);
        
        if ($result === 'win' || $result === 'mine') {
            $gameResult = ($result === 'win') ? 'win' : 'lose';
            $this->updateGameResult($gameId, $gameResult);
        }
        
        return [
            'success' => true,
            'message' => 'Ход сохранён'
        ];
    }

    private function validateGameExists(int $gameId): void {
        $stmt = $this->db->prepare("SELECT id FROM games WHERE id = :id");
        $stmt->execute(['id' => $gameId]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$game) {
            throw new NotFoundException('Игра не найдена');
        }
    }

    private function validateMoveData(array $data): void {
        $requiredFields = ['move_number', 'row_coord', 'col_coord', 'move_type', 'result'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new ValidationException('Отсутствует обязательное поле: ' . $field);
            }
        }
        
        if (!is_numeric($data['move_number']) || $data['move_number'] < 1) {
            throw new ValidationException('Номер хода должен быть положительным числом');
        }
        
        if (!is_numeric($data['row_coord']) || $data['row_coord'] < 0) {
            throw new ValidationException('Координата строки должна быть неотрицательным числом');
        }
        
        if (!is_numeric($data['col_coord']) || $data['col_coord'] < 0) {
            throw new ValidationException('Координата столбца должна быть неотрицательным числом');
        }
        
        $validMoveTypes = ['open', 'flag'];
        if (!in_array($data['move_type'], $validMoveTypes, true)) {
            throw new ValidationException('Тип хода должен быть "open" или "flag"');
        }
        
        $validResults = ['safe', 'mine', 'win', 'flag_set', 'flag_removed'];
        if (!in_array($data['result'], $validResults, true)) {
            throw new ValidationException('Результат хода должен быть одним из: ' . implode(', ', $validResults));
        }
    }

    private function updateTotalMoves(int $gameId): void {
        $stmt = $this->db->prepare("
            UPDATE games 
            SET total_moves = (SELECT COUNT(*) FROM moves WHERE game_id = :game_id)
            WHERE id = :game_id
        ");
        $stmt->execute(['game_id' => $gameId]);
    }

    private function updateGameResult(int $gameId, string $result): void {
        $stmt = $this->db->prepare("
            UPDATE games 
            SET game_result = :result
            WHERE id = :game_id
        ");
        $stmt->execute([
            'game_id' => $gameId,
            'result' => $result
        ]);
    }
}
