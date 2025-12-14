<?php

declare(strict_types=1);

namespace NeoCore\Commands;

use NeoCore\Schedule\Scheduler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Schedule List Command
 * 
 * Lists all scheduled tasks
 */
class ScheduleListCommand extends Command
{
    protected static $defaultName = 'schedule:list';
    protected static $defaultDescription = 'List all scheduled tasks';

    protected Scheduler $scheduler;

    public function __construct(Scheduler $scheduler)
    {
        parent::__construct();
        $this->scheduler = $scheduler;
    }

    protected function configure(): void
    {
        $this->setHelp('This command lists all scheduled tasks and their configurations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Scheduled Tasks');

        // Load schedule definitions
        $this->loadSchedule();

        $tasks = $this->scheduler->getTasks();

        if (empty($tasks)) {
            $io->warning('No scheduled tasks defined.');
            return Command::SUCCESS;
        }

        $io->text(sprintf('Total: %d task(s)', count($tasks)));
        $io->newLine();

        $tableRows = [];

        foreach ($tasks as $index => $task) {
            $command = $task->getCommand();
            $commandStr = is_string($command) ? $command : 'Closure';
            
            $description = $task->getDescription() ?? $commandStr;
            $expression = $task->getExpression();
            $isDue = $task->isDue() ? 'Yes' : 'No';
            $shouldRun = $task->shouldRun() ? 'Yes' : 'No';

            try {
                $nextRun = (new \NeoCore\Schedule\CronExpression($expression))
                    ->getNextRunDate()
                    ->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $nextRun = 'N/A';
            }

            $tableRows[] = [
                $index + 1,
                $description,
                $expression,
                $isDue,
                $shouldRun,
                $nextRun,
            ];
        }

        $io->table(
            ['#', 'Description', 'Expression', 'Due Now', 'Will Run', 'Next Run'],
            $tableRows
        );

        // Show legend
        $io->newLine();
        $io->section('Cron Expression Format');
        $io->text([
            '┌───────────── minute (0 - 59)',
            '│ ┌───────────── hour (0 - 23)',
            '│ │ ┌───────────── day of month (1 - 31)',
            '│ │ │ ┌───────────── month (1 - 12)',
            '│ │ │ │ ┌───────────── day of week (0 - 6) (Sunday to Saturday)',
            '│ │ │ │ │',
            '│ │ │ │ │',
            '* * * * *',
        ]);

        $io->newLine();
        $io->section('Special Characters');
        $io->listing([
            '* - Any value',
            ', - Value list separator',
            '- - Range of values',
            '/ - Step values',
            'L - Last (day of month or weekday)',
            'W - Nearest weekday',
            '# - Nth occurrence (e.g., 1#2 = second Monday)',
        ]);

        return Command::SUCCESS;
    }

    /**
     * Load schedule definitions
     */
    protected function loadSchedule(): void
    {
        $scheduleFile = __DIR__ . '/../../config/schedule.php';

        if (!file_exists($scheduleFile)) {
            return;
        }

        $config = require $scheduleFile;

        if (isset($config['timezone'])) {
            $this->scheduler->setTimezone($config['timezone']);
        }

        if (isset($config['tasks']) && is_array($config['tasks'])) {
            foreach ($config['tasks'] as $taskConfig) {
                if (isset($taskConfig['command']) && isset($taskConfig['frequency'])) {
                    $task = $this->scheduler->command($taskConfig['command'])
                        ->cron($taskConfig['frequency']);

                    if (isset($taskConfig['description'])) {
                        $task->description($taskConfig['description']);
                    }

                    if (isset($taskConfig['without_overlapping']) && $taskConfig['without_overlapping']) {
                        $task->withoutOverlapping();
                    }

                    if (isset($taskConfig['run_in_background']) && $taskConfig['run_in_background']) {
                        $task->runInBackground();
                    }
                } elseif (isset($taskConfig['callable']) && isset($taskConfig['frequency'])) {
                    $task = $this->scheduler->call($taskConfig['callable'])
                        ->cron($taskConfig['frequency']);

                    if (isset($taskConfig['description'])) {
                        $task->description($taskConfig['description']);
                    }
                }
            }
        }
    }
}
