<?php

namespace NeoCore\System\CLI;

/**
 * Console - CLI command runner
 * 
 * No artisan-style magic. Simple command execution.
 */
class Console
{
    private array $commands = [];
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->registerDefaultCommands();
    }

    /**
     * Register default commands
     */
    private function registerDefaultCommands(): void
    {
        $this->register('make:module', Commands\MakeModule::class);
        $this->register('make:controller', Commands\MakeController::class);
        $this->register('make:model', Commands\MakeModel::class);
        $this->register('make:migration', Commands\MakeMigration::class);
        $this->register('make:service', Commands\MakeService::class);
        $this->register('make:entity', Commands\MakeEntity::class);
        $this->register('make:repository', Commands\MakeRepository::class);
        $this->register('migrate', Commands\Migrate::class);
        $this->register('migrate:rollback', Commands\MigrateRollback::class);
        $this->register('orm:sync', Commands\ORMSync::class);
        $this->register('view:clear', Commands\ClearViewCache::class);
        $this->register('worker:run', Commands\WorkerRun::class);
        $this->register('serve', Commands\Serve::class);
        $this->register('list', Commands\ListCommands::class);
    }

    /**
     * Register command
     */
    public function register(string $name, string $class): void
    {
        $this->commands[$name] = $class;
    }

    /**
     * Run command
     */
    public function run(array $argv): int
    {
        if (count($argv) < 2) {
            $this->showHelp();
            return 0;
        }

        $commandName = $argv[1];

        if (!isset($this->commands[$commandName])) {
            echo "Command not found: $commandName\n";
            echo "Run 'php neocore list' to see available commands\n";
            return 1;
        }

        $commandClass = $this->commands[$commandName];
        $command = new $commandClass($this->basePath);

        $args = array_slice($argv, 2);

        try {
            return $command->execute($args);
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    /**
     * Show help
     */
    private function showHelp(): void
    {
        echo "NeoCore PHP Framework - CLI Tool\n\n";
        echo "Usage: php neocore <command> [arguments]\n\n";
        echo "Available commands:\n";
        
        foreach ($this->commands as $name => $class) {
            echo "  $name\n";
        }
        
        echo "\nRun 'php neocore list' for detailed command descriptions\n";
    }

    /**
     * Get registered commands
     */
    public function getCommands(): array
    {
        return $this->commands;
    }
}
