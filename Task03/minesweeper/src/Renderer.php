<?php

namespace Artyo\Task03;

class Renderer
{
    public function displayField(array $field, int $remainingMines): void
    {
        $size = count($field);
        echo "\nОставшиеся мины: $remainingMines\n\n";

        echo "   ";
        for ($c = 0; $c < $size; $c++) {
            echo sprintf("%2d ", $c);
        }
        echo "\n   " . str_repeat("---", $size) . "\n";

        for ($r = 0; $r < $size; $r++) {
            echo sprintf("%2d|", $r);
            for ($c = 0; $c < $size; $c++) {
                echo " " . $this->formatCell($field[$r][$c]) . " ";
            }
            echo "\n";
        }
        echo "\n";
    }

    private function formatCell(string $cell): string
    {
        return match($cell) {
            ' ' => '.',   
            'F' => '⚑',  
            'X' => '💣',  
            '*' => '💥', 
            default => $cell,
        };
    }

    public function showWinMessage(int $moves): void
    {
        echo "\n🎉 ПОЗДРАВЛЯЕМ! 🎉\n";
        echo "Вы выиграли игру за $moves ходов!\n";
        echo "Все мины успешно отмечены или открыты безопасно!\n";
    }

    public function showGameOverMessage(): void
    {
        echo "\n💥 ИГРА ОКОНЧЕНА! 💥\n";
        echo "Вы наступили на мину!\n";
        echo "Повезет в следующий раз!\n";
    }
}
