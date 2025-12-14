<?php

declare(strict_types=1);

namespace NeoCore\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeLangCommand extends Command
{
    protected static $defaultName = 'make:lang';
    protected static $defaultDescription = 'Create a new language file';

    protected function configure(): void
    {
        $this->addArgument('locale', InputArgument::REQUIRED, 'The locale code (e.g., en, th, fr)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $locale = $input->getArgument('locale');

        $langPath = __DIR__ . '/../../resources/lang';
        
        if (!is_dir($langPath)) {
            mkdir($langPath, 0755, true);
        }

        $jsonFile = "{$langPath}/{$locale}.json";

        if (file_exists($jsonFile)) {
            $io->error("Language file '{$locale}.json' already exists.");
            return Command::FAILURE;
        }

        $template = [
            'welcome' => 'Welcome',
            'hello' => 'Hello, :name!',
            'goodbye' => 'Goodbye, :name!',
            'auth' => [
                'login' => 'Login',
                'logout' => 'Logout',
                'register' => 'Register',
                'email' => 'Email Address',
                'password' => 'Password',
            ],
            'messages' => [
                'success' => 'Operation completed successfully!',
                'error' => 'An error occurred. Please try again.',
            ],
        ];

        file_put_contents($jsonFile, json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $io->success("Language file '{$locale}.json' created successfully!");

        return Command::SUCCESS;
    }
}
