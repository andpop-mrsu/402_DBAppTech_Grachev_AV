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
     
        if (!R::inspect('game')) {
    
            $game = R::dispense('game');
            $game->date_played = date('Y-m-d H:i:s');
            $game->player_name = 'temp';
            $game->field_size = 1;
            $game->mines_count = 1;
            $game->mine_positions = '[]';
            $game->game_result = 'temp';
            $game->total_moves = 0;
            $game->created_at = date('Y-m-d H:i:s');
            
            $gameId = R::store($game);
            R::trash($game); 
        }
        
        if (!R::inspect('move')) {

            $move = R::dispense('move');
            $move->game_id = 1;
            $move->move_number = 1;
            $move->row_coord = 0;
            $move->col_coord = 0;
            $move->move_type = 'temp';
            $move->result = 'temp';
            
            R::store($move);
            R::trash($move); 
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
        $gameBeans = R::findAll('game', ' ORDER BY created_at DESC ');
        
        $result = [];
        foreach ($gameBeans as $bean) {
            $result[] = [
                'id' => $bean->id,
                'date_played' => $bean->date_played,
                'player_name' => $bean->player_name,
                'field_size' => $bean->field_size,
                'mines_count' => $bean->mines_count,
                'game_result' => $bean->game_result,
                'total_moves' => $bean->total_moves
            ];
        }
        
        return $result;
    }

    public function getGameById(int $gameId): ?array
    {
        $gameBean = R::findOne('game', ' id = ? ', [$gameId]);

        if (!$gameBean) {
            return null;
        }

        $moveBeans = R::findAll('move', ' game_id = ? ORDER BY move_number', [$gameId]);
        
        $moves = [];
        foreach ($moveBeans as $moveBean) {
            $moves[] = [
                'move_number' => $moveBean->move_number,
                'row_coord' => $moveBean->row_coord,
                'col_coord' => $moveBean->col_coord,
                'move_type' => $moveBean->move_type,
                'result' => $moveBean->result
            ];
        }

        $result = [
            'id' => $gameBean->id,
            'date_played' => $gameBean->date_played,
            'player_name' => $gameBean->player_name,
            'field_size' => $gameBean->field_size,
            'mines_count' => $gameBean->mines_count,
            'mine_positions' => $gameBean->mine_positions,
            'game_result' => $gameBean->game_result,
            'total_moves' => $gameBean->total_moves
        ];
        
        $result['moves'] = $moves;

        return $result;
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
