<?php

namespace NeoCore\System\CLI\Commands;

/**
 * Serve - Start development server
 */
class Serve extends Command
{
    public function execute(array $args): int
    {
        $host = $this->argument($args, 0, 'localhost');
        $port = $this->argument($args, 1, '8000');

        $publicPath = $this->basePath . '/public';

        if (!is_dir($publicPath)) {
            $this->error("Public directory not found: $publicPath");
            return 1;
        }

        $this->info("NeoCore development server started");
        $this->info("Listening on http://$host:$port");
        $this->info("Press Ctrl+C to stop");

        $command = sprintf(
            'php -S %s:%s -t %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($publicPath)
        );

        passthru($command);

        return 0;
    }
}
