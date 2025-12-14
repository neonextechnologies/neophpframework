<?php

declare(strict_types=1);

namespace NeoCore\Commands;

use NeoCore\Repositories\LogRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LogsStatsCommand extends Command
{
    protected static $defaultName = 'logs:stats';
    protected static $defaultDescription = 'Show log statistics';

    protected LogRepository $logRepository;

    public function __construct(LogRepository $logRepository)
    {
        parent::__construct();
        $this->logRepository = $logRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Log Statistics');

        // Count by level
        $counts = $this->logRepository->countByLevel();

        if (empty($counts)) {
            $io->info('No logs found.');
            return Command::SUCCESS;
        }

        $io->section('Logs by Level');
        $rows = [];
        foreach ($counts as $level => $count) {
            $rows[] = [strtoupper($level), $count];
        }
        $io->table(['Level', 'Count'], $rows);

        // Total logs
        $total = array_sum($counts);
        $io->success("Total logs: {$total}");

        // Recent errors
        $errors = $this->logRepository->getErrors()->limit(5)->fetchAll();

        if (!empty($errors)) {
            $io->section('Recent Errors');
            $rows = [];
            foreach ($errors as $log) {
                $rows[] = [
                    $log->created_at->format('Y-m-d H:i:s'),
                    strtoupper($log->level),
                    $this->truncate($log->message, 80),
                ];
            }
            $io->table(['Time', 'Level', 'Message'], $rows);
        }

        return Command::SUCCESS;
    }

    protected function truncate(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length - 3) . '...';
    }
}
