<?php

namespace NeoCore\System\CLI\Commands;

/**
 * ListCommands - Show all available commands
 */
class ListCommands extends Command
{
    public function execute(array $args): int
    {
        $this->writeLine("NeoCore PHP Framework - Available Commands\n");

        $commands = [
            'make:module <name>' => 'Create a new module with scaffolding',
            'make:controller <name>' => 'Create a new controller',
            'make:model <name>' => 'Create a new model',
            'make:service <name>' => 'Create a new service',
            'make:migration <name>' => 'Create a new migration file',
            'migrate' => 'Run pending database migrations',
            'migrate:rollback' => 'Rollback the last migration batch',
            'worker:run [queue]' => 'Start queue worker (default queue: default)',
            'serve [host] [port]' => 'Start development server (default: localhost:8000)',
            'list' => 'Show this list of commands',
        ];

        foreach ($commands as $command => $description) {
            $this->writeLine(sprintf("  %-30s %s", $command, $description));
        }

        $this->writeLine("");
        return 0;
    }
}
