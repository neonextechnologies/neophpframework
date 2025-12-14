<?php

declare(strict_types=1);

namespace NeoCore\Commands;

use NeoCore\Repositories\LogRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LogsTailCommand extends Command
{
    protected static $defaultName = 'logs:tail';
    protected static $defaultDescription = 'Tail log files';

    protected LogRepository $logRepository;

    public function __construct(LogRepository $logRepository)
    {
        parent::__construct();
        $this->logRepository = $logRepository;
    }

    protected function configure(): void
    {
        $this
            ->addOption('lines', 'l', InputOption::VALUE_OPTIONAL, 'Number of lines to display', 20)
            ->addOption('level', null, InputOption::VALUE_OPTIONAL, 'Filter by log level')
            ->addOption('channel', 'c', InputOption::VALUE_OPTIONAL, 'Filter by channel');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $lines = (int) $input->getOption('lines');
        $level = $input->getOption('level');
        $channel = $input->getOption('channel');

        $query = $this->logRepository->findRecent($lines);

        if ($level) {
            $query->where('level', $level);
        }

        if ($channel) {
            $query->where('channel', $channel);
        }

        $logs = $query->fetchAll();

        if (empty($logs)) {
            $io->info('No logs found.');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($logs as $log) {
            $rows[] = [
                $log->created_at->format('Y-m-d H:i:s'),
                strtoupper($log->level),
                $log->channel,
                $this->truncate($log->message, 100),
            ];
        }

        $io->table(
            ['Time', 'Level', 'Channel', 'Message'],
            $rows
        );

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
