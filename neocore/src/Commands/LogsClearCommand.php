<?php

declare(strict_types=1);

namespace NeoCore\Commands;

use NeoCore\Repositories\LogRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LogsClearCommand extends Command
{
    protected static $defaultName = 'logs:clear';
    protected static $defaultDescription = 'Clear log files';

    protected LogRepository $logRepository;

    public function __construct(LogRepository $logRepository)
    {
        parent::__construct();
        $this->logRepository = $logRepository;
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Delete logs older than days', 30)
            ->addOption('database', null, InputOption::VALUE_NONE, 'Clear database logs')
            ->addOption('files', null, InputOption::VALUE_NONE, 'Clear file logs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');
        $database = $input->getOption('database');
        $files = $input->getOption('files');

        if (!$database && !$files) {
            $database = true;
            $files = true;
        }

        if ($database) {
            $count = $this->logRepository->cleanOldLogs($days);
            $io->success("Cleared {$count} database logs older than {$days} days.");
        }

        if ($files) {
            $count = $this->clearFileLog($days);
            $io->success("Cleared {$count} log files older than {$days} days.");
        }

        return Command::SUCCESS;
    }

    protected function clearFileLog(int $days): int
    {
        $logPath = __DIR__ . '/../../storage/logs';
        $cutoff = strtotime("-{$days} days");
        $count = 0;

        if (!is_dir($logPath)) {
            return 0;
        }

        $files = glob("{$logPath}/*.log");

        if (!$files) {
            return 0;
        }

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $count++;
            }
        }

        return $count;
    }
}
