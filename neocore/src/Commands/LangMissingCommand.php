<?php

declare(strict_types=1);

namespace NeoCore\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LangMissingCommand extends Command
{
    protected static $defaultName = 'lang:missing';
    protected static $defaultDescription = 'Find missing translations';

    protected function configure(): void
    {
        $this
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'Check specific locale')
            ->addOption('base', 'b', InputOption::VALUE_OPTIONAL, 'Base locale to compare against', 'en');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $locale = $input->getOption('locale');
        $baseLocale = $input->getOption('base');

        $langPath = __DIR__ . '/../../resources/lang';

        if (!is_dir($langPath)) {
            $io->error('Language directory not found.');
            return Command::FAILURE;
        }

        $baseFile = "{$langPath}/{$baseLocale}.json";

        if (!file_exists($baseFile)) {
            $io->error("Base language file '{$baseLocale}.json' not found.");
            return Command::FAILURE;
        }

        $baseTranslations = json_decode(file_get_contents($baseFile), true);
        $baseKeys = $this->flattenArray($baseTranslations);

        if ($locale) {
            $this->checkLocale($io, $langPath, $locale, $baseKeys);
        } else {
            // Check all locales
            $files = glob("{$langPath}/*.json");
            foreach ($files as $file) {
                $currentLocale = basename($file, '.json');
                if ($currentLocale === $baseLocale) {
                    continue;
                }
                $this->checkLocale($io, $langPath, $currentLocale, $baseKeys);
            }
        }

        return Command::SUCCESS;
    }

    protected function checkLocale(SymfonyStyle $io, string $langPath, string $locale, array $baseKeys): void
    {
        $file = "{$langPath}/{$locale}.json";

        if (!file_exists($file)) {
            $io->warning("Language file '{$locale}.json' not found.");
            return;
        }

        $translations = json_decode(file_get_contents($file), true);
        $keys = $this->flattenArray($translations);

        $missing = array_diff_key($baseKeys, $keys);

        if (empty($missing)) {
            $io->success("Locale '{$locale}' has all translations!");
        } else {
            $io->section("Missing translations for '{$locale}':");
            $io->listing(array_keys($missing));
        }
    }

    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
