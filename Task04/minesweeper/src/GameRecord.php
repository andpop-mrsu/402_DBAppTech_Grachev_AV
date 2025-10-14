<?php

namespace Artyo\Task03;

class GameRecord
{
    private string $datePlayed;
    private string $playerName;
    private int $fieldSize;
    private int $minesCount;
    private string $minePositions; // JSON строка с позициями мин
    private string $gameResult;
    private int $totalMoves;
    private array $moves = [];

    public function __construct(
        string $playerName,
        int $fieldSize,
        int $minesCount,
        array $minePositions,
        string $gameResult,
        int $totalMoves
    ) {
        $this->datePlayed = date('Y-m-d H:i:s');
        $this->playerName = $playerName;
        $this->fieldSize = $fieldSize;
        $this->minesCount = $minesCount;
        $this->minePositions = json_encode($minePositions);
        $this->gameResult = $gameResult;
        $this->totalMoves = $totalMoves;
    }

    public function addMove(Move $move): void
    {
        $this->moves[] = $move;
    }

    public function getDatePlayed(): string
    {
        return $this->datePlayed;
    }

    public function getPlayerName(): string
    {
        return $this->playerName;
    }

    public function getFieldSize(): int
    {
        return $this->fieldSize;
    }

    public function getMinesCount(): int
    {
        return $this->minesCount;
    }

    public function getMinePositions(): string
    {
        return $this->minePositions;
    }

    public function getGameResult(): string
    {
        return $this->gameResult;
    }

    public function getTotalMoves(): int
    {
        return $this->totalMoves;
    }

    public function getMoves(): array
    {
        return $this->moves;
    }

    public function getMinePositionsArray(): array
    {
        return json_decode($this->minePositions, true);
    }
}
