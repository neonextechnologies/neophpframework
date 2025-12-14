<?php

declare(strict_types=1);

namespace NeoCore\Commands;

use NeoCore\Repositories\AuditLogRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AuditLogsClearCommand extends Command
{
    protected static $defaultName = 'audit:clear';
    protected static $defaultDescription = 'Clear old audit logs';

    protected AuditLogRepository $auditLogRepository;

    public function __construct(AuditLogRepository $auditLogRepository)
    {
        parent::__construct();
        $this->auditLogRepository = $auditLogRepository;
    }

    protected function configure(): void
    {
        $this->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Delete logs older than days', 90);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');

        $count = $this->auditLogRepository->cleanOldLogs($days);

        $io->success("Cleared {$count} audit logs older than {$days} days.");

        return Command::SUCCESS;
    }
}
