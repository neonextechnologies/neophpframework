<?php

declare(strict_types=1);

namespace NeoCore\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Model Prune Command
 * 
 * Prunes (permanently deletes) old soft-deleted records
 */
class ModelPruneCommand extends Command
{
    protected static $defaultName = 'model:prune';
    protected static $defaultDescription = 'Prune soft-deleted records older than specified days';

    protected function configure(): void
    {
        $this
            ->addOption(
                'model',
                'm',
                InputOption::VALUE_REQUIRED,
                'The model class to prune'
            )
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Number of days to keep soft-deleted records',
                30
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force the operation without confirmation'
            )
            ->setHelp(
                'This command permanently deletes soft-deleted records older than the specified number of days.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $modelClass = $input->getOption('model');
        $days = (int) $input->getOption('days');
        $force = $input->getOption('force');

        if (!$modelClass) {
            $io->error('Please specify a model class using --model option.');
            return Command::FAILURE;
        }

        if (!class_exists($modelClass)) {
            $io->error("Model class '{$modelClass}' does not exist.");
            return Command::FAILURE;
        }

        $io->title('Prune Soft-Deleted Records');
        $io->text([
            "Model: {$modelClass}",
            "Days to keep: {$days}",
        ]);
        $io->newLine();

        // Get repository
        try {
            $repository = app('orm')->getRepository($modelClass);

            if (!method_exists($repository, 'pruneDeleted')) {
                $io->error('Repository does not support soft delete pruning.');
                $io->note('Make sure the repository uses the HasSoftDeletes trait.');
                return Command::FAILURE;
            }

            // Count records to be pruned
            $query = $repository->query()->onlyTrashed();
            $cutoffDate = (new \DateTimeImmutable())->modify("-{$days} days");
            $toPrune = 0;

            foreach ($query->fetchAll() as $entity) {
                if (property_exists($entity, 'deleted_at')) {
                    $deletedAt = $entity->deleted_at;
                    if ($deletedAt instanceof \DateTimeInterface && $deletedAt < $cutoffDate) {
                        $toPrune++;
                    }
                }
            }

            if ($toPrune === 0) {
                $io->success('No records to prune.');
                return Command::SUCCESS;
            }

            $io->warning("Found {$toPrune} record(s) to permanently delete.");

            // Confirm if not forced
            if (!$force) {
                if (!$io->confirm('Do you want to continue?', false)) {
                    $io->note('Operation cancelled.');
                    return Command::SUCCESS;
                }
            }

            // Prune records
            $io->text('Pruning records...');
            $pruned = $repository->pruneDeleted($days);

            $io->newLine();
            $io->success("Pruned {$pruned} record(s) successfully.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to prune records: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
