<?php

declare(strict_types=1);

namespace NeoCore\Commands;

use NeoCore\Repositories\AuditLogRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AuditLogsShowCommand extends Command
{
    protected static $defaultName = 'audit:show';
    protected static $defaultDescription = 'Show audit logs';

    protected AuditLogRepository $auditLogRepository;

    public function __construct(AuditLogRepository $auditLogRepository)
    {
        parent::__construct();
        $this->auditLogRepository = $auditLogRepository;
    }

    protected function configure(): void
    {
        $this
            ->addOption('user', 'u', InputOption::VALUE_OPTIONAL, 'Filter by user ID')
            ->addOption('event', 'e', InputOption::VALUE_OPTIONAL, 'Filter by event')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of logs to show', 20);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getOption('user');
        $event = $input->getOption('event');
        $limit = (int) $input->getOption('limit');

        $query = $this->auditLogRepository->findRecent($limit);

        if ($userId) {
            $query->where('user_id', (int) $userId);
        }

        if ($event) {
            $query->where('event', $event);
        }

        $logs = $query->fetchAll();

        if (empty($logs)) {
            $io->info('No audit logs found.');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($logs as $log) {
            $rows[] = [
                $log->created_at->format('Y-m-d H:i:s'),
                $log->user_id ?? 'N/A',
                $log->event,
                $log->auditable_type ?? 'N/A',
                $log->auditable_id ?? 'N/A',
            ];
        }

        $io->table(
            ['Time', 'User ID', 'Event', 'Type', 'ID'],
            $rows
        );

        return Command::SUCCESS;
    }
}
