<?php

namespace Artyo\Task03;

class GameController
{
    private Game $game;
    private Renderer $renderer;
    private DatabaseService $database;

    public function __construct()
    {
        $this->game = new Game();
        $this->renderer = new Renderer();
        $this->database = new DatabaseService();
    }

    public function startNewGame(): void
    {
        echo "=== САПЁР ===\n";
        echo "Начинаем новую игру...\n";

        $sizeInput = readline("Введите размер поля (по умолчанию 10): ");
        if ($sizeInput === '' || $sizeInput === null) {
            $size = 10;
        } else {
            $size = (int) $sizeInput;
        }
        if ($size < 2) {
            $size = 2;
        }

        $maxMines = ($size * $size) - 1;
        $defaultMines = (int) round($size * $size * 0.15);
        if ($defaultMines < 1) {
            $defaultMines = 1;
        }
        if ($defaultMines > $maxMines) {
            $defaultMines = $maxMines;
        }

        $minesPrompt = "Введите количество мин (1..$maxMines, по умолчанию $defaultMines): ";
        $minesInput = readline($minesPrompt);

        if ($minesInput === '' || $minesInput === null) {
            $mines = $defaultMines;
        } else {
            $mines = (int) $minesInput;
        }
        if ($mines < 1) {
            $mines = 1;
        }
        if ($mines > $maxMines) {
            $mines = $maxMines;
        }

        $playerName = readline("Введите ваше имя (по умолчанию Игрок): ");
        if (empty($playerName)) {
            $playerName = "Игрок";
        }

        $this->game->initializeGame($size, $mines, $playerName);

        $gameOver = false;
        $moves = 0;

        while (!$gameOver) {
            $this->renderer->displayField($this->game->getVisibleField(), $this->game->getRemainingMines());

            $input = readline("Введите координаты ('ряд столбец') или 'M ряд столбец' для флага: ");
            if (!$input) {
                continue;
            }

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

                    // Сохраняем игру в базу данных
                    $gameResult = $result['win'] ? 'win' : 'lose';
                    $gameRecord = $this->game->createGameRecord($gameResult);
                    $gameId = $this->database->saveGame($gameRecord);

                    if ($result['win']) {
                        $this->renderer->showWinMessage($moves);
                    } else {
                        $this->renderer->showGameOverMessage();
                    }

                    echo "Игра сохранена с ID: $gameId\n";
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
        $games = $this->database->getAllGames();
        echo TableFormatter::formatGamesTable($games);
    }

    public function replayGame(?string $id): void
    {
        if ($id === null) {
            echo "Для режима повторного просмотра требуется ID игры\n";
            echo "Используйте: minesweeper --replay <ID>\n";
            return;
        }

        $gameId = (int)$id;
        $gameData = $this->database->getGameById($gameId);

        if (!$gameData) {
            echo "Игра с ID $gameId не найдена.\n";
            echo "Используйте --list для просмотра доступных игр.\n";
            return;
        }

        echo "=== ПОВТОР ИГРЫ #$gameId ===\n";
        echo "Игрок: {$gameData['player_name']}\n";
        echo "Дата: " . date('d.m.Y H:i', strtotime($gameData['date_played'])) . "\n";
        echo "Размер поля: {$gameData['field_size']}x{$gameData['field_size']}\n";
        echo "Количество мин: {$gameData['mines_count']}\n";
        echo "Результат: " . ($gameData['game_result'] === 'win' ? 'Победа' : 'Поражение') . "\n";
        echo "Всего ходов: {$gameData['total_moves']}\n\n";

        // Восстанавливаем игру
        $this->replayGameMoves($gameData);
    }

    private function replayGameMoves(array $gameData): void
    {
        $size = $gameData['field_size'];
        $mines = $gameData['mine_positions'];
        $moves = $gameData['moves'];

        // Инициализируем пустое поле
        $field = array_fill(0, $size, array_fill(0, $size, ' '));
        $remainingMines = $gameData['mines_count'];

        echo "Начинаем повтор игры...\n\n";
        echo "Нажмите Enter для следующего хода или 'q' для выхода...\n";

        foreach ($moves as $move) {
            $input = readline();
            if ($input === 'q' || $input === 'quit') {
                echo "Повтор игры прерван.\n";
                return;
            }

            $row = $move['row_coord'];
            $col = $move['col_coord'];
            $type = $move['move_type'];
            $result = $move['result'];

            if ($type === 'open') {
                // Открываем клетку
                $minePositions = json_decode($mines, true);
                $isMine = false;

                foreach ($minePositions as $minePos) {
                    if ($minePos['row'] === $row && $minePos['col'] === $col) {
                        $isMine = true;
                        break;
                    }
                }

                if ($isMine) {
                    $field[$row][$col] = '*';
                    // Показываем все мины
                    foreach ($minePositions as $minePos) {
                        $field[$minePos['row']][$minePos['col']] = 'X';
                    }
                } else {
                    // Подсчитываем соседние мины
                    $count = 0;
                    for ($dr = -1; $dr <= 1; $dr++) {
                        for ($dc = -1; $dc <= 1; $dc++) {
                            if ($dr === 0 && $dc === 0) {
                                continue;
                            }
                            $nr = $row + $dr;
                            $nc = $col + $dc;
                            if ($nr >= 0 && $nr < $size && $nc >= 0 && $nc < $size) {
                                foreach ($minePositions as $minePos) {
                                    if ($minePos['row'] === $nr && $minePos['col'] === $nc) {
                                        $count++;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    $field[$row][$col] = $count === 0 ? '0' : (string)$count;
                }
            } elseif ($type === 'flag') {
                if ($result === 'flag_set') {
                    $field[$row][$col] = 'F';
                    $remainingMines--;
                } else {
                    $field[$row][$col] = ' ';
                    $remainingMines++;
                }
            }

            $this->renderer->displayField($field, $remainingMines);

            $moveDesc = $type === 'open' ? "открытие" : "флаг";
            $resultDesc = match ($result) {
                'safe' => "безопасно",
                'mine' => "МИНА!",
                'win' => "ПОБЕДА!",
                'flag_set' => "установлен",
                'flag_removed' => "снят",
                default => $result
            };

            echo "Ход #{$move['move_number']}: {$moveDesc} ({$row}, {$col}) - {$resultDesc}\n";

            if ($result === 'mine' || $result === 'win') {
                echo "\nИгра завершена!\n";
                break;
            }
        }

        echo "\nПовтор игры завершён.\n";
    }

    public function showMainMenu(): void
    {
        while (true) {
            echo "\n=== ГЛАВНОЕ МЕНЮ САПЁРА ===\n";
            echo "Выберите действие:\n";
            echo "1. Новая игра\n";
            echo "2. Список сохранённых партий\n";
            echo "3. Повтор игры\n";
            echo "4. Справка\n";
            echo "0. Выход\n";
            echo "Введите номер (0-4): ";

            $choice = trim(readline());

            switch ($choice) {
                case '1':
                    $this->startNewGame();
                    break;

                case '2':
                    $this->showGameList();
                    break;

                case '3':
                    $this->handleReplayFromMenu();
                    break;

                case '4':
                    $this->showHelp();
                    break;

                case '0':
                    echo "До свидания!\n";
                    return;

                default:
                    echo "Неверный выбор. Пожалуйста, введите число от 0 до 4.\n";
                    break;
            }

            if ($choice !== '0') {
                echo "\nНажмите Enter для возврата в главное меню...";
                readline();
            }
        }
    }

    private function handleReplayFromMenu(): void
    {
        echo "\n=== ПОВТОР ИГРЫ ===\n";
        echo "Введите ID игры для повторного просмотра (или 'q' для отмены): ";

        $input = trim(readline());

        if ($input === 'q' || $input === 'quit' || $input === '') {
            echo "Отменено.\n";
            return;
        }

        $id = (int)$input;
        if ($id <= 0) {
            echo "Неверный ID игры.\n";
            return;
        }

        $this->replayGame((string)$id);
    }

    public function showHelp(): void
    {
        echo "\n=== СПРАВКА ===\n";
        echo "Игра Сапёр - консольная версия\n\n";

        echo "ПРАВИЛА ИГРЫ:\n";
        echo "- Открывайте клетки, избегая мин\n";
        echo "- Числа показывают количество соседних мин\n";
        echo "- Используйте флаги для пометки подозрительных клеток\n";
        echo "- Цель: открыть все безопасные клетки\n\n";

        echo "КОМАНДЫ В ИГРЕ:\n";
        echo "- 'ряд столбец' - открыть клетку\n";
        echo "- 'M ряд столбец' - установить/снять флаг\n";
        echo "- 'q' или 'quit' - выйти из повторного просмотра\n\n";

        echo "КОМАНДЫ ЗАПУСКА:\n";
        echo "- minesweeper - показать главное меню\n";
        echo "- minesweeper --new - новая игра\n";
        echo "- minesweeper --list - список игр\n";
        echo "- minesweeper --replay ID - повтор игры\n";
        echo "- minesweeper --help - эта справка\n\n";

        echo "БАЗА ДАННЫХ:\n";
        echo "- Все игры автоматически сохраняются\n";
        echo "- Можно просматривать историю и повторять игры\n";
        echo "- Файл базы: data/minesweeper.db\n";
    }
}
