<?php

namespace NeoCore\System\CLI\Commands;

/**
 * MakeController - Create new controller
 */
class MakeController extends Command
{
    public function execute(array $args): int
    {
        $controllerName = $this->argument($args, 0);

        if (!$controllerName) {
            $this->error("Controller name is required");
            $this->info("Usage: php neocore make:controller <name>");
            return 1;
        }

        // Remove 'Controller' suffix if provided
        $controllerName = str_replace('Controller', '', $controllerName);
        $controllerName = ucfirst($controllerName);

        $controllerPath = $this->basePath . '/app/Http/Controllers/' . $controllerName . 'Controller.php';

        if (file_exists($controllerPath)) {
            $this->error("Controller already exists: {$controllerName}Controller");
            return 1;
        }

        $content = $this->getControllerTemplate($controllerName);

        if ($this->createFile($controllerPath, $content)) {
            $this->success("Controller created: {$controllerName}Controller");
            $this->info("Location: app/Http/Controllers/{$controllerName}Controller.php");
            return 0;
        }

        return 1;
    }

    private function getControllerTemplate(string $name): string
    {
        return <<<PHP
<?php

namespace App\\Http\\Controllers;

use NeoCore\\System\\Core\\Controller;
use NeoCore\\System\\Core\\Request;
use NeoCore\\System\\Core\\Response;

class {$name}Controller extends Controller
{
    public function index(Request \$request, Response \$response): Response
    {
        return \$this->respondSuccess(\$response, [], '$name list');
    }

    public function show(Request \$request, Response \$response): Response
    {
        \$id = \$request->param('id');
        return \$this->respondSuccess(\$response, ['id' => \$id], '$name details');
    }

    public function store(Request \$request, Response \$response): Response
    {
        \$data = \$request->all();
        
        // Validate input
        \$errors = \$this->validate(\$data, [
            // 'field' => 'required|min:3|max:255'
        ]);

        if (!empty(\$errors)) {
            return \$this->respondValidationError(\$response, \$errors);
        }

        // Add your logic here
        
        return \$this->respondSuccess(\$response, \$data, '$name created');
    }

    public function update(Request \$request, Response \$response): Response
    {
        \$id = \$request->param('id');
        \$data = \$request->all();
        
        // Add your logic here
        
        return \$this->respondSuccess(\$response, \$data, '$name updated');
    }

    public function delete(Request \$request, Response \$response): Response
    {
        \$id = \$request->param('id');
        
        // Add your logic here
        
        return \$this->respondSuccess(\$response, null, '$name deleted');
    }
}

PHP;
    }
}
