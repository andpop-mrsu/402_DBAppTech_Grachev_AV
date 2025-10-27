<?php

namespace Artyo\Task03;

use RedBeanPHP\R;

class DatabaseService
{
    private string $dbPath;

    public function __construct(string $dbPath = 'data/minesweeper.db')
    {
        $this->dbPath = $dbPath;
        $this->initializeDatabase();
    }

    private function initializeDatabase(): void
    {
        $dir = dirname($this->dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        try {
            R::setup('sqlite:' . $this->dbPath);
            
            $this->createTables();
        } catch (\Exception $e) {
            throw new \RuntimeException("Не удалось подключиться к базе данных: " . $e->getMessage());
        }
    }

    private function createTables(): void
    {
        $tables = ['game', 'move'];
        
        foreach ($tables as $table) {
            if (!R::inspect($table)) {
                R::exec("
                    CREATE TABLE IF NOT EXISTS game (
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
                ");
                
                R::exec("
                    CREATE TABLE IF NOT EXISTS move (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        game_id INTEGER NOT NULL,
                        move_number INTEGER NOT NULL,
                        row_coord INTEGER NOT NULL,
                        col_coord INTEGER NOT NULL,
                        move_type TEXT NOT NULL,
                        result TEXT NOT NULL,
                        FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE
                    )
                ");
                break;
            }
        }
    }

    public function saveGame(GameRecord $gameRecord): int
    {
        try {
            $game = R::dispense('game');
            $game->date_played = $gameRecord->getDatePlayed();
            $game->player_name = $gameRecord->getPlayerName();
            $game->field_size = $gameRecord->getFieldSize();
            $game->mines_count = $gameRecord->getMinesCount();
            $game->mine_positions = $gameRecord->getMinePositions();
            $game->game_result = $gameRecord->getGameResult();
            $game->total_moves = $gameRecord->getTotalMoves();
            $game->created_at = date('Y-m-d H:i:s');

            $gameId = R::store($game);

            foreach ($gameRecord->getMoves() as $move) {
                $moveBean = R::dispense('move');
                $moveBean->game_id = $gameId;
                $moveBean->move_number = $move->getMoveNumber();
                $moveBean->row_coord = $move->getRowCoord();
                $moveBean->col_coord = $move->getColCoord();
                $moveBean->move_type = $move->getMoveType();
                $moveBean->result = $move->getResult();
                
                R::store($moveBean);
            }

            return (int)$gameId;
        } catch (\Exception $e) {
            throw new \RuntimeException("Ошибка при сохранении игры: " . $e->getMessage());
        }
    }

    public function getAllGames(): array
    {
        $games = R::getAll("
            SELECT id, date_played, player_name, field_size, mines_count, game_result, total_moves
            FROM game
            ORDER BY created_at DESC
        ");

        return $games;
    }

    public function getGameById(int $gameId): ?array
    {
        $game = R::getRow("
            SELECT id, date_played, player_name, field_size, mines_count, mine_positions, game_result, total_moves
            FROM game
            WHERE id = ?
        ", [$gameId]);

        if (!$game) {
            return null;
        }

        $moves = R::getAll("
            SELECT move_number, row_coord, col_coord, move_type, result
            FROM move
            WHERE game_id = ?
            ORDER BY move_number
        ", [$gameId]);

        $game['moves'] = $moves;

        return $game;
    }

    public function deleteGame(int $gameId): bool
    {
        $game = R::load('game', $gameId);
        if (!$game->id) {
            return false;
        }
        R::trash($game);
        return true;
    }

    public function getGamesCount(): int
    {
        return (int)R::count('game');
    }
}
