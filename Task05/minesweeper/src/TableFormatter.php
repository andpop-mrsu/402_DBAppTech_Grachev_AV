<?php

namespace Artyo\Task03;

class TableFormatter
{
    public static function formatGamesTable(array $games): string
    {
        if (empty($games)) {
            return "Сохранённых игр пока нет.\n";
        }

        $columnWidths = self::calculateColumnWidths($games);

        $output = "\n=== СПИСОК СОХРАНЁННЫХ ИГР ===\n\n";

        $output .= self::drawHeader($columnWidths);

        $output .= self::drawSeparator($columnWidths);

        foreach ($games as $game) {
            $output .= self::drawGameRow($game, $columnWidths);
        }

        $output .= "\nИспользуйте команду --replay ID для повторного просмотра игры.\n";

        return $output;
    }

    private static function calculateColumnWidths(array $games): array
    {
        $widths = [
            'id' => 3,
            'player' => 12,
            'date' => 12,
            'size' => 8,
            'mines' => 6,
            'result' => 10,
            'moves' => 8
        ];

        $headers = [
            'id' => 'ID',
            'player' => 'Игрок',
            'date' => 'Дата',
            'size' => 'Размер',
            'mines' => 'Мины',
            'result' => 'Результат',
            'moves' => 'Ходы'
        ];

        foreach ($headers as $key => $header) {
            $widths[$key] = max($widths[$key], mb_strlen($header, 'UTF-8'));
        }

        foreach ($games as $game) {
            $widths['id'] = max($widths['id'], strlen((string)$game['id']));
            $widths['player'] = max($widths['player'], mb_strlen($game['player_name'], 'UTF-8'));
            $widths['date'] = max($widths['date'], mb_strlen(self::formatDate($game['date_played']), 'UTF-8'));
            $widths['size'] = max($widths['size'], mb_strlen(self::formatFieldSize($game['field_size']), 'UTF-8'));
            $widths['mines'] = max($widths['mines'], strlen((string)$game['mines_count']));
            $widths['result'] = max(
                $widths['result'],
                mb_strlen(self::formatGameResult($game['game_result']), 'UTF-8')
            );
            $widths['moves'] = max($widths['moves'], strlen((string)$game['total_moves']));
        }

        return $widths;
    }

    private static function drawHeader(array $widths): string
    {
        $headers = [
            'id' => 'ID',
            'player' => 'Игрок',
            'date' => 'Дата',
            'size' => 'Размер',
            'mines' => 'Мины',
            'result' => 'Результат',
            'moves' => 'Ходы'
        ];

        $line = '';
        foreach ($headers as $key => $header) {
            $line .= '│ ' . self::padString($header, $widths[$key], ' ', STR_PAD_RIGHT) . ' ';
        }
        $line .= "│\n";

        return $line;
    }

    private static function drawSeparator(array $widths): string
    {
        $line = '';
        foreach ($widths as $width) {
            $line .= '├' . str_repeat('─', $width + 2);
        }
        $line .= "┤\n";

        return $line;
    }

    private static function drawGameRow(array $game, array $widths): string
    {
        $line = '';
        $line .= '│ ' . self::padString((string)$game['id'], $widths['id'], ' ', STR_PAD_LEFT) . ' ';
        $line .= '│ ' . self::padString(
            $game['player_name'],
            $widths['player'],
            ' ',
            STR_PAD_RIGHT
        ) . ' ';
        $line .= '│ ' . self::padString(
            self::formatDate($game['date_played']),
            $widths['date'],
            ' ',
            STR_PAD_RIGHT
        ) . ' ';
        $line .= '│ ' . self::padString(
            self::formatFieldSize($game['field_size']),
            $widths['size'],
            ' ',
            STR_PAD_RIGHT
        ) . ' ';
        $line .= '│ ' . self::padString(
            (string)$game['mines_count'],
            $widths['mines'],
            ' ',
            STR_PAD_LEFT
        ) . ' ';
        $line .= '│ ' . self::padString(
            self::formatGameResult($game['game_result']),
            $widths['result'],
            ' ',
            STR_PAD_RIGHT
        ) . ' ';
        $line .= '│ ' . self::padString(
            (string)$game['total_moves'],
            $widths['moves'],
            ' ',
            STR_PAD_LEFT
        ) . ' ';
        $line .= "│\n";

        return $line;
    }

    private static function padString(
        string $string,
        int $width,
        string $padChar = ' ',
        int $padType = STR_PAD_RIGHT
    ): string {
        $actualLength = mb_strlen($string, 'UTF-8');

        if ($actualLength > $width) {
            return mb_substr($string, 0, $width - 1, 'UTF-8') . '…';
        }

        return str_pad($string, $width - $actualLength + strlen($string), $padChar, $padType);
    }

    private static function formatDate(string $dateString): string
    {
        try {
            $date = new \DateTime($dateString);
            return $date->format('d.m.Y H:i');
        } catch (\Exception $e) {
            return $dateString;
        }
    }

    private static function formatFieldSize(int $size): string
    {
        return $size . '×' . $size;
    }

    private static function formatGameResult(string $result): string
    {
        return $result === 'win' ? 'Победа' : 'Поражение';
    }
}
