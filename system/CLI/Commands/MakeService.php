<?php

namespace NeoCore\System\CLI\Commands;

/**
 * MakeService - Create new service
 */
class MakeService extends Command
{
    public function execute(array $args): int
    {
        $serviceName = $this->argument($args, 0);

        if (!$serviceName) {
            $this->error("Service name is required");
            $this->info("Usage: php neocore make:service <name>");
            return 1;
        }

        // Remove 'Service' suffix if provided
        $serviceName = str_replace('Service', '', $serviceName);
        $serviceName = ucfirst($serviceName);

        $servicePath = $this->basePath . '/app/Services/' . $serviceName . 'Service.php';

        if (file_exists($servicePath)) {
            $this->error("Service already exists: {$serviceName}Service");
            return 1;
        }

        $content = $this->getServiceTemplate($serviceName);

        if ($this->createFile($servicePath, $content)) {
            $this->success("Service created: {$serviceName}Service");
            $this->info("Location: app/Services/{$serviceName}Service.php");
            return 0;
        }

        return 1;
    }

    private function getServiceTemplate(string $name): string
    {
        return <<<PHP
<?php

namespace App\\Services;

use NeoCore\\System\\Core\\EventBus;

class {$name}Service
{
    private EventBus \$eventBus;

    public function __construct(EventBus \$eventBus)
    {
        \$this->eventBus = \$eventBus;
    }

    /**
     * Example service method
     */
    public function create(array \$data): array
    {
        // Manual validation
        \$errors = \$this->validate(\$data);
        
        if (!empty(\$errors)) {
            throw new \\Exception('Validation failed');
        }

        // Business logic here
        // Model operations
        // \$model = new {$name}Model(\$db);
        // \$id = \$model->insert(\$data);

        // Dispatch event
        // \$this->eventBus->dispatch('{$name}.created', \$data);

        return \$data;
    }

    /**
     * Validate data
     */
    private function validate(array \$data): array
    {
        \$errors = [];

        // Add validation rules
        // if (empty(\$data['field'])) {
        //     \$errors['field'][] = 'Field is required';
        // }

        return \$errors;
    }
}

PHP;
    }
}
