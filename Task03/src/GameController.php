<?php

namespace Artyo\Task03;

class GameController
{
    private Game $game;
    private Renderer $renderer;

    public function __construct()
    {
        $this->game = new Game();
        $this->renderer = new Renderer();
    }

    public function startNewGame(): void
    {
        echo "=== САПЁР ===\n";
        echo "Начинаем новую игру...\n";

        $sizeInput = readline("Введите размер поля (по умолчанию 10): ");
        // Размер поля: по умолчанию 10, минимум 2
        if ($sizeInput === '' || $sizeInput === null) {
            $size = 10;
        } else {
            $size = (int) $sizeInput;
        }
        if ($size < 2) {
            $size = 2;
        }

        // Рассчитать диапазон и дефолт для мин с учетом выбранного размера
        $maxMines = ($size * $size) - 1;
        $defaultMines = (int) round($size * $size * 0.15);
        if ($defaultMines < 1) $defaultMines = 1;
        if ($defaultMines > $maxMines) $defaultMines = $maxMines;

        // Запросить количество мин, показывая допустимый диапазон и дефолт
        $minesPrompt = "Введите количество мин (1..$maxMines, по умолчанию $defaultMines): ";
        $minesInput = readline($minesPrompt);

        // Количество мин: принять введенное или применить дефолт, затем зажать в диапазон
        if ($minesInput === '' || $minesInput === null) {
            $mines = $defaultMines;
        } else {
            $mines = (int) $minesInput;
        }
        if ($mines < 1) $mines = 1;
        if ($mines > $maxMines) $mines = $maxMines;

        $playerName = readline("Введите ваше имя (по умолчанию Игрок): ");
        if (empty($playerName)) $playerName = "Игрок";

        $this->game->initializeGame($size, $mines, $playerName);

        $gameOver = false;
        $moves = 0;

        while (!$gameOver) {
            $this->renderer->displayField($this->game->getVisibleField(), $this->game->getRemainingMines());

            $input = readline("Введите координаты ('ряд столбец') или 'M ряд столбец' для флага: ");
            if (!$input) continue;

            $parts = explode(' ', $input);

            if (strtoupper($parts[0]) === 'M' && count($parts) === 3) {
                $row = (int)$parts[1];
                $col = (int)$parts[2];
                $this->game->toggleFlag($row, $col);
            } elseif (count($parts) === 2) {
                $row = (int)$parts[0];
                $col = (int)$parts[1];
                $moves++;

                $result = $this->game->openCell($row, $col);

                if ($result['game_over']) {
                    $gameOver = true;
                    $this->renderer->displayField($this->game->getFullField(), 0);
                    if ($result['win']) {
                        $this->renderer->showWinMessage($moves);
                    } else {
                        $this->renderer->showGameOverMessage();
                    }
                } else {
                    echo "Клетка открыта. Рядом мин: " . $result['adjacent_mines'] . "\n";
                }
            } else {
                echo "Неверный формат ввода. Используйте 'ряд столбец' или 'M ряд столбец'\n";
            }
        }
    }

    public function showGameList(): void
    {
        echo "Список игр будет доступен при поддержке базы данных (пока не реализовано)\n";
    }

    public function replayGame(?string $id): void
    {
        if ($id === null) {
            echo "Для режима повторного просмотра требуется ID игры\n";
        } else {
            echo "Повтор игры будет доступен при поддержке базы данных (пока не реализовано)\n";
        }
    }

    public function showHelp(): void
    {
        echo "Игра Сапёр - консольная версия\n";
        echo "Использование: minesweeper [ПАРАМЕТР]\n";
        echo "Параметры:\n";
        echo "  -n, --new       Начать новую игру (по умолчанию)\n";
        echo "  -l, --list      Показать список сохранённых игр\n";
        echo "  -r, --replay ID Повторить сохранённую игру\n";
        echo "  -h, --help      Показать это сообщение\n";
        echo "Ввод:\n";
        echo "  'ряд столбец' для открытия клетки, 'M ряд столбец' для установки флага\n";
    }
}
