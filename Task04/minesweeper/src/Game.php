<?php

namespace Artyo\Task03;

class Game
{
    private int $size;
    private int $mines;
    private array $mineField = [];
    private array $visibleField = [];
    private int $remainingMines;
    private bool $gameStarted = false;
    private string $playerName = '';
    private int $moveCount = 0;
    private array $moves = [];
    private array $minePositions = [];

    public function initializeGame(int $size, int $mines, string $playerName): void
    {
        // Clamp size and mines to valid ranges
        $clampedSize = max(2, $size);
        $maxMines = ($clampedSize * $clampedSize) - 1;
        $clampedMines = max(1, min($mines, $maxMines));

        $this->size = $clampedSize;
        $this->mines = $clampedMines;
        $this->remainingMines = $clampedMines;
        $this->gameStarted = false;
        $this->playerName = $playerName;
        $this->moveCount = 0;
        $this->moves = [];
        $this->minePositions = [];

        $this->mineField = array_fill(0, $this->size, array_fill(0, $this->size, 0));
        $this->visibleField = array_fill(0, $this->size, array_fill(0, $this->size, ' '));
    }

    public function openCell(int $row, int $col): array
    {
        if (!$this->gameStarted) {
            $this->placeMines($row, $col);
        }

        $this->moveCount++;

        if ($this->mineField[$row][$col] === -1) {
            $this->visibleField[$row][$col] = '*';
            $this->revealAllMines();
            $result = ['game_over' => true, 'win' => false, 'adjacent_mines' => 0];
            $this->moves[] = Move::createOpenMove($this->moveCount, $row, $col, $result);
            return $result;
        }

        $this->revealCell($row, $col);

        if ($this->checkWin()) {
            $result = ['game_over' => true, 'win' => true, 'adjacent_mines' => $this->mineField[$row][$col]];
            $this->moves[] = Move::createOpenMove($this->moveCount, $row, $col, $result);
            return $result;
        }

        $result = ['game_over' => false, 'win' => false, 'adjacent_mines' => $this->mineField[$row][$col]];
        $this->moves[] = Move::createOpenMove($this->moveCount, $row, $col, $result);
        return $result;
    }

    public function toggleFlag(int $row, int $col): bool
    {
        $cell = $this->visibleField[$row][$col];
        if ($cell === ' ') {
            $this->visibleField[$row][$col] = 'F';
            $this->remainingMines--;
            $this->moveCount++;
            $this->moves[] = Move::createFlagMove($this->moveCount, $row, $col, true);
            return true;
        } elseif ($cell === 'F') {
            $this->visibleField[$row][$col] = ' ';
            $this->remainingMines++;
            $this->moveCount++;
            $this->moves[] = Move::createFlagMove($this->moveCount, $row, $col, false);
            return true;
        }
        return false;
    }

    public function getVisibleField(): array
    {
        return $this->visibleField;
    }

    public function getFullField(): array
    {
        $field = [];
        for ($r = 0; $r < $this->size; $r++) {
            for ($c = 0; $c < $this->size; $c++) {
                $field[$r][$c] = $this->mineField[$r][$c] === -1 ? 'X' : (string)$this->mineField[$r][$c];
            }
        }
        return $field;
    }

    public function getRemainingMines(): int
    {
        return $this->remainingMines;
    }

    private function placeMines(int $firstRow, int $firstCol): void
    {
        $placed = 0;
        $this->minePositions = [];

        while ($placed < $this->mines) {
            $r = random_int(0, $this->size - 1);
            $c = random_int(0, $this->size - 1);
            if (($r === $firstRow && $c === $firstCol) || $this->mineField[$r][$c] === -1) {
                continue;
            }

            $this->mineField[$r][$c] = -1;
            $this->minePositions[] = ['row' => $r, 'col' => $c];
            $placed++;

            for ($dr = -1; $dr <= 1; $dr++) {
                for ($dc = -1; $dc <= 1; $dc++) {
                    $nr = $r + $dr;
                    $nc = $c + $dc;
                    if (
                        $nr >= 0 && $nr < $this->size
                        && $nc >= 0 && $nc < $this->size
                        && $this->mineField[$nr][$nc] !== -1
                    ) {
                        $this->mineField[$nr][$nc]++;
                    }
                }
            }
        }
        $this->gameStarted = true;
    }

    private function revealCell(int $row, int $col): void
    {
        if ($this->visibleField[$row][$col] !== ' ') {
            return;
        }
        $count = $this->mineField[$row][$col];
        $this->visibleField[$row][$col] = $count === 0 ? '0' : (string)$count;

        if ($count === 0) {
            for ($dr = -1; $dr <= 1; $dr++) {
                for ($dc = -1; $dc <= 1; $dc++) {
                    if ($dr !== 0 || $dc !== 0) {
                        $nr = $row + $dr;
                        $nc = $col + $dc;
                        if ($nr >= 0 && $nr < $this->size && $nc >= 0 && $nc < $this->size) {
                            $this->revealCell($nr, $nc);
                        }
                    }
                }
            }
        }
    }

    private function revealAllMines(): void
    {
        for ($r = 0; $r < $this->size; $r++) {
            for ($c = 0; $c < $this->size; $c++) {
                if ($this->mineField[$r][$c] === -1 && $this->visibleField[$r][$c] !== '*') {
                    $this->visibleField[$r][$c] = 'X';
                }
            }
        }
    }

    private function checkWin(): bool
    {
        for ($r = 0; $r < $this->size; $r++) {
            for ($c = 0; $c < $this->size; $c++) {
                if ($this->visibleField[$r][$c] === ' ' && $this->mineField[$r][$c] !== -1) {
                    return false;
                }
            }
        }
        return true;
    }

    // Методы для работы с базой данных
    public function getPlayerName(): string
    {
        return $this->playerName;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getMines(): int
    {
        return $this->mines;
    }

    public function getMinePositions(): array
    {
        return $this->minePositions;
    }

    public function getMoves(): array
    {
        return $this->moves;
    }

    public function getMoveCount(): int
    {
        return $this->moveCount;
    }

    public function createGameRecord(string $gameResult): GameRecord
    {
        $gameRecord = new GameRecord(
            $this->playerName,
            $this->size,
            $this->mines,
            $this->minePositions,
            $gameResult,
            $this->moveCount
        );

        foreach ($this->moves as $move) {
            $gameRecord->addMove($move);
        }

        return $gameRecord;
    }
}
