<?php

namespace Artyo\Task03;

use Artyo\Task03\GameController;

class CLI
{
    private GameController $controller;

    public function __construct()
    {
        $this->controller = new GameController();
    }

    public function run(array $args): void
    {
        array_shift($args); 
        $command = $args[0] ?? '--new';

        switch ($command) {
            case '--new':
            case '-n':
                $this->controller->startNewGame();
                break;

            case '--list':
            case '-l':
                $this->controller->showGameList();
                break;

            case '--replay':
            case '-r':
                $id = $args[1] ?? null;
                $this->controller->replayGame($id);
                break;

            case '--help':
            case '-h':
                $this->controller->showHelp();
                break;

            default:
                echo "Неизвестная команда: $command\n";
                $this->controller->showHelp();
                break;
        }
    }
}
