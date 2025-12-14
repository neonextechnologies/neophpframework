<?php

declare(strict_types=1);

namespace NeoCore\Commands;

use NeoCore\Schedule\Scheduler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Schedule Run Command
 * 
 * Runs all scheduled tasks that are due
 */
class ScheduleRunCommand extends Command
{
    protected static $defaultName = 'schedule:run';
    protected static $defaultDescription = 'Run all scheduled tasks that are due';

    protected Scheduler $scheduler;

    public function __construct(Scheduler $scheduler)
    {
        parent::__construct();
        $this->scheduler = $scheduler;
    }

    protected function configure(): void
    {
        $this->setHelp('This command runs all scheduled tasks that are currently due.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Running Scheduled Tasks');

        // Load schedule definitions
        $this->loadSchedule();

        $dueTasks = $this->scheduler->getDueTasks();

        if (empty($dueTasks)) {
            $io->info('No scheduled tasks are due.');
            return Command::SUCCESS;
        }

        $io->text(sprintf('Found %d task(s) to run.', count($dueTasks)));
        $io->newLine();

        $results = $this->scheduler->run();

        // Display results
        $tableRows = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($results as $result) {
            $status = $result['success'] ? '✓' : '✗';
            $tableRows[] = [
                $status,
                $result['task'],
                $result['started_at'],
                $result['duration'] . 's',
                $result['success'] ? 'Success' : 'Failed: ' . ($result['error'] ?? 'Unknown error'),
            ];

            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        $io->table(
            ['Status', 'Task', 'Started At', 'Duration', 'Result'],
            $tableRows
        );

        $io->newLine();
        $io->success(sprintf(
            'Completed: %d successful, %d failed',
            $successCount,
            $failureCount
        ));

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
