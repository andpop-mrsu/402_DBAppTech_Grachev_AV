<?php

namespace Artyo\Task03;

class Move
{
    private int $moveNumber;
    private int $rowCoord;
    private int $colCoord;
    private string $moveType; // 'open' или 'flag'
    private string $result; // 'safe', 'mine', 'win', 'flag_set', 'flag_removed'

    public function __construct(
        int $moveNumber,
        int $rowCoord,
        int $colCoord,
        string $moveType,
        string $result
    ) {
        $this->moveNumber = $moveNumber;
        $this->rowCoord = $rowCoord;
        $this->colCoord = $colCoord;
        $this->moveType = $moveType;
        $this->result = $result;
    }

    public function getMoveNumber(): int
    {
        return $this->moveNumber;
    }

    public function getRowCoord(): int
    {
        return $this->rowCoord;
    }

    public function getColCoord(): int
    {
        return $this->colCoord;
    }

    public function getMoveType(): string
    {
        return $this->moveType;
    }

    public function getResult(): string
    {
        return $this->result;
    }

    public static function createOpenMove(int $moveNumber, int $row, int $col, array $gameResult): self
    {
        $result = 'safe';
        if ($gameResult['game_over'] && !$gameResult['win']) {
            $result = 'mine';
        } elseif ($gameResult['game_over'] && $gameResult['win']) {
            $result = 'win';
        }

        return new self($moveNumber, $row, $col, 'open', $result);
    }

    public static function createFlagMove(int $moveNumber, int $row, int $col, bool $flagSet): self
    {
        $result = $flagSet ? 'flag_set' : 'flag_removed';
        return new self($moveNumber, $row, $col, 'flag', $result);
    }
}
