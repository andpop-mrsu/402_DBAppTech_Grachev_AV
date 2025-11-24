<?php

namespace Artyo\Task03;

use PDO;
use PDOException;

class DatabaseService
{
    private PDO $pdo;
    private string $dbPath;

    public function __construct(string $dbPath = 'data/minesweeper.db')
    {
        $this->dbPath = $dbPath;
        $this->initializeDatabase();
    }

    private function initializeDatabase(): void
    {
        // Создаем директорию для базы данных, если её нет
        $dir = dirname($this->dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        try {
            $this->pdo = new PDO("sqlite:" . $this->dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->createTables();
        } catch (PDOException $e) {
            throw new \RuntimeException("Не удалось подключиться к базе данных: " . $e->getMessage());
        }
    }

    private function createTables(): void
    {
        // Таблица игр
        $gamesTable = "
            CREATE TABLE IF NOT EXISTS games (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                date_played TEXT NOT NULL,
                player_name TEXT NOT NULL,
                field_size INTEGER NOT NULL,
                mines_count INTEGER NOT NULL,
                mine_positions TEXT NOT NULL,
                game_result TEXT NOT NULL,
                total_moves INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";

        // Таблица ходов
        $movesTable = "
            CREATE TABLE IF NOT EXISTS moves (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                game_id INTEGER NOT NULL,
                move_number INTEGER NOT NULL,
                row_coord INTEGER NOT NULL,
                col_coord INTEGER NOT NULL,
                move_type TEXT NOT NULL,
                result TEXT NOT NULL,
                FOREIGN KEY (game_id) REFERENCES games (id) ON DELETE CASCADE
            )
        ";

        $this->pdo->exec($gamesTable);
        $this->pdo->exec($movesTable);
    }

    public function saveGame(GameRecord $gameRecord): int
    {
        try {
            $this->pdo->beginTransaction();

            // Сохраняем игру
            $stmt = $this->pdo->prepare("
                INSERT INTO games (
                    date_played, 
                    player_name, 
                    field_size, 
                    mines_count, 
                    mine_positions, 
                    game_result, 
                    total_moves
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $gameRecord->getDatePlayed(),
                $gameRecord->getPlayerName(),
                $gameRecord->getFieldSize(),
                $gameRecord->getMinesCount(),
                $gameRecord->getMinePositions(),
                $gameRecord->getGameResult(),
                $gameRecord->getTotalMoves()
            ]);

            $gameId = $this->pdo->lastInsertId();

            // Сохраняем ходы
            foreach ($gameRecord->getMoves() as $move) {
                $moveStmt = $this->pdo->prepare("
                    INSERT INTO moves (game_id, move_number, row_coord, col_coord, move_type, result)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $moveStmt->execute([
                    $gameId,
                    $move->getMoveNumber(),
                    $move->getRowCoord(),
                    $move->getColCoord(),
                    $move->getMoveType(),
                    $move->getResult()
                ]);
            }

            $this->pdo->commit();
            return (int)$gameId;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new \RuntimeException("Ошибка при сохранении игры: " . $e->getMessage());
        }
    }

    public function getAllGames(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, date_played, player_name, field_size, mines_count, game_result, total_moves
            FROM games
            ORDER BY created_at DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGameById(int $gameId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, date_played, player_name, field_size, mines_count, mine_positions, game_result, total_moves
            FROM games
            WHERE id = ?
        ");

        $stmt->execute([$gameId]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$game) {
            return null;
        }

        // Получаем ходы для игры
        $movesStmt = $this->pdo->prepare("
            SELECT move_number, row_coord, col_coord, move_type, result
            FROM moves
            WHERE game_id = ?
            ORDER BY move_number
        ");

        $movesStmt->execute([$gameId]);
        $game['moves'] = $movesStmt->fetchAll(PDO::FETCH_ASSOC);

        return $game;
    }

    public function deleteGame(int $gameId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM games WHERE id = ?");
        return $stmt->execute([$gameId]);
    }

    public function getGamesCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM games");
        return (int)$stmt->fetchColumn();
    }
}
