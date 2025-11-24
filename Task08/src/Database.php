<?php

class Database {
    private PDO $connection;
    private string $dbPath;

    public function __construct(string $dbPath) {
        $this->dbPath = $dbPath;
        
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        $this->connection = new PDO('sqlite:' . $dbPath);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->exec('PRAGMA foreign_keys = ON');
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    public function initSchema(): void {
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS games (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                player_name TEXT NOT NULL,
                date_played TEXT NOT NULL,
                field_size INTEGER NOT NULL,
                mines_count INTEGER NOT NULL,
                mine_positions TEXT NOT NULL,
                game_result TEXT,
                total_moves INTEGER DEFAULT 0,
                created_at TEXT NOT NULL
            )
        ");

        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS moves (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                game_id INTEGER NOT NULL,
                move_number INTEGER NOT NULL,
                row_coord INTEGER NOT NULL,
                col_coord INTEGER NOT NULL,
                move_type TEXT NOT NULL,
                result TEXT NOT NULL,
                FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
            )
        ");

        $this->connection->exec("
            CREATE INDEX IF NOT EXISTS idx_moves_game_id ON moves(game_id)
        ");

        $this->connection->exec("
            CREATE INDEX IF NOT EXISTS idx_games_date ON games(date_played)
        ");
    }
}
