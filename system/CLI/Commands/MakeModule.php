<?php

namespace NeoCore\System\CLI\Commands;

/**
 * MakeModule - Create new module
 */
class MakeModule extends Command
{
    public function execute(array $args): int
    {
        $moduleName = $this->argument($args, 0);

        if (!$moduleName) {
            $this->error("Module name is required");
            $this->info("Usage: php neocore make:module <name>");
            return 1;
        }

        $moduleName = ucfirst($moduleName);
        $modulePath = $this->basePath . '/modules/' . strtolower($moduleName);

        if (is_dir($modulePath)) {
            $this->error("Module already exists: $moduleName");
            return 1;
        }

        $this->info("Creating module: $moduleName");

        // Create module directories
        $directories = [
            $modulePath . '/Http/Controllers',
            $modulePath . '/Http/Middleware',
            $modulePath . '/Models',
            $modulePath . '/Services',
            $modulePath . '/Config',
            $modulePath . '/Migrations',
            $modulePath . '/Routes',
            $modulePath . '/Resources',
        ];

        foreach ($directories as $dir) {
            $this->ensureDirectory($dir);
        }

        // Create module config
        $configContent = $this->getModuleConfigTemplate($moduleName);
        $this->createFile($modulePath . '/Config/module.php', $configContent);

        // Create routes file
        $routesContent = $this->getRoutesTemplate($moduleName);
        $this->createFile($modulePath . '/Routes/api.php', $routesContent);

        // Create example controller
        $controllerContent = $this->getControllerTemplate($moduleName);
        $this->createFile($modulePath . '/Http/Controllers/' . $moduleName . 'Controller.php', $controllerContent);

        $this->success("Module created successfully: $moduleName");
        $this->info("Don't forget to add '$moduleName' to config/modules.php");

        return 0;
    }

    private function getModuleConfigTemplate(string $moduleName): string
    {
        return <<<PHP
<?php

return [
    'name' => '$moduleName',
    'version' => '1.0.0',
    'description' => '$moduleName module',
    
    'routes' => [
        'api' => 'Routes/api.php'
    ],
    
    'events' => [
        // 'event.name' => [
        //     Modules\\$moduleName\\Listeners\\EventListener::class
        // ]
    ]
];

PHP;
    }

    private function getRoutesTemplate(string $moduleName): string
    {
        $lowerName = strtolower($moduleName);
        return <<<PHP
<?php

// Module routes for $moduleName
// \$router is available from ModuleLoader

\$router->prefix('/$lowerName')->group(function(\$router) {
    \$router->get('', 'Modules\\$moduleName\\Http\\Controllers\\{$moduleName}Controller@index');
    \$router->post('', 'Modules\\$moduleName\\Http\\Controllers\\{$moduleName}Controller@store');
    \$router->get('/{id}', 'Modules\\$moduleName\\Http\\Controllers\\{$moduleName}Controller@show');
    \$router->put('/{id}', 'Modules\\$moduleName\\Http\\Controllers\\{$moduleName}Controller@update');
    \$router->delete('/{id}', 'Modules\\$moduleName\\Http\\Controllers\\{$moduleName}Controller@delete');
});

PHP;
    }

    private function getControllerTemplate(string $moduleName): string
    {
        return <<<PHP
<?php

namespace Modules\\$moduleName\\Http\\Controllers;

use NeoCore\\System\\Core\\Controller;
use NeoCore\\System\\Core\\Request;
use NeoCore\\System\\Core\\Response;

class {$moduleName}Controller extends Controller
{
    public function index(Request \$request, Response \$response): Response
    {
        return \$this->respondSuccess(\$response, [], '$moduleName list');
    }

    public function show(Request \$request, Response \$response): Response
    {
        \$id = \$request->param('id');
        return \$this->respondSuccess(\$response, ['id' => \$id], '$moduleName details');
    }

    public function store(Request \$request, Response \$response): Response
    {
        \$data = \$request->all();
        
        // Add your logic here
        
        return \$this->respondSuccess(\$response, \$data, '$moduleName created');
    }

    public function update(Request \$request, Response \$response): Response
    {
        \$id = \$request->param('id');
        \$data = \$request->all();
        
        // Add your logic here
        
        return \$this->respondSuccess(\$response, \$data, '$moduleName updated');
    }

    public function delete(Request \$request, Response \$response): Response
    {
        \$id = \$request->param('id');
        
        // Add your logic here
        
        return \$this->respondSuccess(\$response, null, '$moduleName deleted');
    }
}

PHP;
    }
}
