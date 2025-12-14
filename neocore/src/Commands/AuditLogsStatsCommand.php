<?php

declare(strict_types=1);

namespace NeoCore\Commands;

use NeoCore\Repositories\AuditLogRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AuditLogsStatsCommand extends Command
{
    protected static $defaultName = 'audit:stats';
    protected static $defaultDescription = 'Show audit log statistics';

    protected AuditLogRepository $auditLogRepository;

    public function __construct(AuditLogRepository $auditLogRepository)
    {
        parent::__construct();
        $this->auditLogRepository = $auditLogRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Audit Log Statistics');

        // Count by event
        $counts = $this->auditLogRepository->countByEvent();

        if (empty($counts)) {
            $io->info('No audit logs found.');
            return Command::SUCCESS;
        }

        $io->section('Logs by Event');
        $rows = [];
        foreach ($counts as $event => $count) {
            $rows[] = [$event, $count];
        }
        $io->table(['Event', 'Count'], $rows);

        // Total logs
        $total = array_sum($counts);
        $io->success("Total audit logs: {$total}");

        return Command::SUCCESS;
    }
}
