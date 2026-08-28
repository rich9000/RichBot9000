<?php

namespace App\Services;

use App\Models\TicketSummary;
use App\Services\OpenAIAssistant;
use App\Services\TroubleTicketService;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client;
use App\Models\User;
use App\Models\Task;
use App\Models\Project;
use App\Models\Appointment;
use App\Models\Display;
use Illuminate\Support\Facades\Log;


class CodingExecutor
{
    public int $auth_user_id = 1;
    private $disk = false;
    private $user;
    public function __construct($user = null)
    {
        $this->auth_user_id = 1;
        $this->disk = Storage::disk('richbot_sandbox');
        $this->user = $user;
    }




    // Read the contents of a specified file
    public function read_file($arguments)
    {
        $filePath = $arguments['filePath'] ?? null;

        if (!$filePath) {
            return ['success' => false, 'error' => 'Missing required parameter: filePath'];
        }

        if ($this->disk->exists($filePath)) {
            $content = $this->disk->get($filePath);
            return ['success' => true, 'data' => $content];
        }

        return ['success' => false, 'error' => 'File not found'];
    }

    // Suggest changes to a file (placeholder for logic)
    public function suggest_file_change($arguments)
    {
        $filePath = $arguments['filePath'] ?? null;
        $newContent = $arguments['newContent'] ?? null;

        if (!$filePath || !$newContent) {
            return ['success' => false, 'error' => 'Missing required parameters: filePath, newContent'];
        }

        if ($this->disk->exists($filePath)) {
            $originalContent = $this->disk->get($filePath);
            // Placeholder logic for file change suggestion
            return [
                'success' => true,
                'data' => [
                    'originalContent' => $originalContent,
                    'suggestedContent' => $newContent,
                ]
            ];
        }

        return ['success' => false, 'error' => 'File not found'];
    }

    // Suggest an artisan command (placeholder logic)
    public function suggest_artisan_command($arguments)
    {
        $description = $arguments['description'] ?? 'Run a Laravel command';

        // Placeholder for command suggestion logic
        return [
            'success' => true,
            'data' => "Suggested Artisan Command: php artisan ${description}"
        ];
    }

    // Suggest SQL (placeholder for logic)
    public function suggest_sql($arguments)
    {
        $queryDescription = $arguments['queryDescription'] ?? null;

        if (!$queryDescription) {
            return ['success' => false, 'error' => 'Missing required parameter: queryDescription'];
        }

        // Placeholder for SQL generation logic
        $suggestedQuery = "SELECT * FROM table WHERE description LIKE '%${queryDescription}%';";

        return ['success' => true, 'data' => $suggestedQuery];
    }

    // Ask a question (placeholder for logic)
    public function ask_question($arguments)
    {
        $question = $arguments['question'] ?? null;

        if (!$question) {
            return ['success' => false, 'error' => 'Missing required parameter: question'];
        }


        Log::info('Question asked: '.$question);

        // Placeholder for asking a question
        return ['success' => true, 'data' => "Question asked: $question"];
    }




    // List all files in a specified directory
    public function list_files($arguments)
    {
        $directory = $arguments['directory'] ?? '/';

        if ($this->disk->exists($directory)) {
            $files = $this->disk->files($directory);
            return ['success' => true, 'data' => $files];
        }

        return ['success' => false, 'error' => 'Directory not found'];
    }

    // List all folders in a specified directory
    public function list_folders($arguments)
    {
        $directory = $arguments['directory'] ?? null;

        if (!$directory) {
            return ['success' => false, 'error' => 'Missing required parameter: directory'];
        }

        if ($this->disk->exists($directory)) {
            $folders = $this->disk->directories($directory);
            return ['success' => true, 'data' => $folders];
        }

        return ['success' => false, 'error' => 'Directory not found'];
    }

    // Create a new directory
    public function create_directory($arguments)
    {
        $directory = $arguments['directory'] ?? null;

        if (!$directory) {
            return ['success' => false, 'error' => 'Missing required parameter: directory'];
        }

        if (!$this->disk->exists($directory)) {
            $this->disk->makeDirectory($directory);
            return ['success' => true, 'message' => 'Directory created successfully'];
        }

        return ['success' => false, 'error' => 'Directory already exists'];
    }

    // Delete a directory
    public function delete_directory($arguments)
    {
        $directory = $arguments['directory'] ?? null;

        if (!$directory) {
            return ['success' => false, 'error' => 'Missing required parameter: directory'];
        }

        if ($this->disk->exists($directory)) {
            $this->disk->deleteDirectory($directory);
            return ['success' => true, 'message' => 'Directory deleted successfully'];
        }

        return ['success' => false, 'error' => 'Directory not found'];
    }

    // Save text content to a specified file, replacing existing content
    public function put_text($arguments)
    {
        $filePath = $arguments['filePath'] ?? null;
        $content = $arguments['content'] ?? null;

        if (!$filePath || !$content) {
            return ['success' => false, 'error' => 'Missing required parameters: filePath, content'];
        }

        try {
            $this->disk->put($filePath, $content);
            return ['success' => true, 'message' => 'Content saved successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to save content: ' . $e->getMessage()];
        }
    }

    // 5. Search for files in a directory
    public function search_files($arguments)
    {
        $query = $arguments['query'] ?? null;
        $directory = $arguments['directory'] ?? '/';

        if (!$query) {
            return ['success' => false, 'error' => 'Missing required parameter: query'];
        }

        if ($this->disk->exists($directory)) {
            $allFiles = $this->disk->allFiles($directory);
            $matchedFiles = array_filter($allFiles, function ($file) use ($query) {
                return stripos($file, $query) !== false;
            });

            return ['success' => true, 'data' => array_values($matchedFiles)];
        }

        return ['success' => false, 'error' => 'Directory not found'];
    }

    public function analyze_requirements($arguments)
    {
        try {
            $requirements = $arguments['requirements'] ?? null;
            if (!$requirements) {
                return ['success' => false, 'error' => 'Missing required parameter: requirements'];
            }

            // Analyze requirements and break down into components
            $analysis = [
                'components' => [],
                'dependencies' => [],
                'estimated_time' => 0,
                'complexity' => 'medium'
            ];

            // Basic requirement analysis logic
            if (strpos($requirements, 'database') !== false) {
                $analysis['components'][] = 'Database Schema';
                $analysis['dependencies'][] = 'Database Management System';
            }
            if (strpos($requirements, 'api') !== false) {
                $analysis['components'][] = 'API Endpoints';
                $analysis['dependencies'][] = 'API Framework';
            }
            if (strpos($requirements, 'ui') !== false) {
                $analysis['components'][] = 'User Interface';
                $analysis['dependencies'][] = 'Frontend Framework';
            }

            return ['success' => true, 'data' => $analysis];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function design_schema($arguments)
    {
        try {
            $requirements = $arguments['requirements'] ?? null;
            if (!$requirements) {
                return ['success' => false, 'error' => 'Missing required parameter: requirements'];
            }

            // Generate database schema based on requirements
            $schema = [
                'tables' => [],
                'relationships' => [],
                'indexes' => []
            ];

            // Basic schema design logic
            if (strpos($requirements, 'user') !== false) {
                $schema['tables']['users'] = [
                    'id' => 'integer primary key',
                    'name' => 'string',
                    'email' => 'string unique',
                    'created_at' => 'timestamp',
                    'updated_at' => 'timestamp'
                ];
            }

            return ['success' => true, 'data' => $schema];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function create_migration($arguments)
    {
        try {
            $schema = $arguments['schema'] ?? null;
            if (!$schema) {
                return ['success' => false, 'error' => 'Missing required parameter: schema'];
            }

            $migrationName = 'create_' . time() . '_tables';
            $migrationPath = database_path('migrations/' . date('Y_m_d_His') . '_' . $migrationName . '.php');

            $migrationContent = "<?php\n\nuse Illuminate\Database\Migrations\Migration;\nuse Illuminate\Database\Schema\Blueprint;\nuse Illuminate\Support\Facades\Schema;\n\nclass " . ucfirst($migrationName) . " extends Migration\n{\n    public function up()\n    {\n";
            
            foreach ($schema['tables'] as $tableName => $columns) {
                $migrationContent .= "        Schema::create('$tableName', function (Blueprint \$table) {\n";
                foreach ($columns as $columnName => $type) {
                    $migrationContent .= "            \$table->$type('$columnName');\n";
                }
                $migrationContent .= "        });\n\n";
            }

            $migrationContent .= "    }\n\n    public function down()\n    {\n";
            foreach (array_keys($schema['tables']) as $tableName) {
                $migrationContent .= "        Schema::dropIfExists('$tableName');\n";
            }
            $migrationContent .= "    }\n}\n";

            file_put_contents($migrationPath, $migrationContent);
            
            return ['success' => true, 'message' => 'Migration created successfully', 'path' => $migrationPath];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function create_model($arguments)
    {
        try {
            $tableName = $arguments['table_name'] ?? null;
            if (!$tableName) {
                return ['success' => false, 'error' => 'Missing required parameter: table_name'];
            }

            $modelName = ucfirst(str_singular($tableName));
            $modelPath = app_path('Models/' . $modelName . '.php');

            $modelContent = "<?php\n\nnamespace App\\Models;\n\nuse Illuminate\\Database\\Eloquent\\Model;\n\nclass $modelName extends Model\n{\n";
            $modelContent .= "    protected \$table = '$tableName';\n\n";
            $modelContent .= "    protected \$fillable = [\n";
            $modelContent .= "        // Add fillable fields here\n";
            $modelContent .= "    ];\n";
            $modelContent .= "}\n";

            file_put_contents($modelPath, $modelContent);
            
            return ['success' => true, 'message' => 'Model created successfully', 'path' => $modelPath];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function create_controller($arguments)
    {
        try {
            $modelName = $arguments['model_name'] ?? null;
            if (!$modelName) {
                return ['success' => false, 'error' => 'Missing required parameter: model_name'];
            }

            $controllerName = $modelName . 'Controller';
            $controllerPath = app_path('Http/Controllers/' . $controllerName . '.php');

            $controllerContent = "<?php\n\nnamespace App\\Http\\Controllers;\n\nuse App\\Models\\$modelName;\nuse Illuminate\\Http\\Request;\n\nclass $controllerName extends Controller\n{\n";
            $controllerContent .= "    public function index()\n    {\n";
            $controllerContent .= "        return $modelName::all();\n";
            $controllerContent .= "    }\n\n";
            $controllerContent .= "    public function store(Request \$request)\n    {\n";
            $controllerContent .= "        return $modelName::create(\$request->all());\n";
            $controllerContent .= "    }\n\n";
            $controllerContent .= "    public function show($modelName \$$modelName)\n    {\n";
            $controllerContent .= "        return \$$modelName;\n";
            $controllerContent .= "    }\n\n";
            $controllerContent .= "    public function update(Request \$request, $modelName \$$modelName)\n    {\n";
            $controllerContent .= "        \$$modelName->update(\$request->all());\n";
            $controllerContent .= "        return \$$modelName;\n";
            $controllerContent .= "    }\n\n";
            $controllerContent .= "    public function destroy($modelName \$$modelName)\n    {\n";
            $controllerContent .= "        \$$modelName->delete();\n";
            $controllerContent .= "        return response()->json(null, 204);\n";
            $controllerContent .= "    }\n";
            $controllerContent .= "}\n";

            file_put_contents($controllerPath, $controllerContent);
            
            return ['success' => true, 'message' => 'Controller created successfully', 'path' => $controllerPath];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function create_api_routes($arguments)
    {
        try {
            $modelName = $arguments['model_name'] ?? null;
            if (!$modelName) {
                return ['success' => false, 'error' => 'Missing required parameter: model_name'];
            }

            $routeName = strtolower(str_plural($modelName));
            $routesPath = base_path('routes/api.php');

            $routeContent = "\nRoute::apiResource('$routeName', {$modelName}Controller::class);\n";
            
            file_put_contents($routesPath, $routeContent, FILE_APPEND);
            
            return ['success' => true, 'message' => 'API routes created successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function create_frontend_component($arguments)
    {
        try {
            $componentName = $arguments['component_name'] ?? null;
            if (!$componentName) {
                return ['success' => false, 'error' => 'Missing required parameter: component_name'];
            }

            $componentPath = resource_path('js/components/' . $componentName . '.js');

            $componentContent = "import React from 'react';\n\n";
            $componentContent .= "const $componentName = () => {\n";
            $componentContent .= "    return (\n";
            $componentContent .= "        <div>\n";
            $componentContent .= "            {/* Component content */}\n";
            $componentContent .= "        </div>\n";
            $componentContent .= "    );\n";
            $componentContent .= "};\n\n";
            $componentContent .= "export default $componentName;\n";

            file_put_contents($componentPath, $componentContent);
            
            return ['success' => true, 'message' => 'Frontend component created successfully', 'path' => $componentPath];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function create_test($arguments)
    {
        try {
            $testType = $arguments['test_type'] ?? null;
            $testName = $arguments['test_name'] ?? null;
            if (!$testType || !$testName) {
                return ['success' => false, 'error' => 'Missing required parameters: test_type, test_name'];
            }

            $testPath = base_path('tests/' . $testType . '/' . $testName . 'Test.php');

            $testContent = "<?php\n\nnamespace Tests\\$testType;\n\nuse Tests\\TestCase;\n\nclass {$testName}Test extends TestCase\n{\n";
            $testContent .= "    public function test_example()\n    {\n";
            $testContent .= "        \$this->assertTrue(true);\n";
            $testContent .= "    }\n";
            $testContent .= "}\n";

            file_put_contents($testPath, $testContent);
            
            return ['success' => true, 'message' => 'Test created successfully', 'path' => $testPath];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function create_project_skeleton($arguments)
    {
        try {
            $projectName = $arguments['project_name'] ?? null;
            $projectType = $arguments['project_type'] ?? 'laravel';
            $basePath = $arguments['base_path'] ?? null;

            if (!$projectName || !$basePath) {
                return ['success' => false, 'error' => 'Missing required parameters: project_name, base_path'];
            }

            $projectPath = $basePath . '/' . $projectName;

            // Create base directory structure
            $directories = [
                'src',
                'tests',
                'config',
                'docs',
                'resources',
                'public'
            ];

            foreach ($directories as $dir) {
                if (!$this->disk->exists($projectPath . '/' . $dir)) {
                    $this->disk->makeDirectory($projectPath . '/' . $dir, 0755, true);
                }
            }

            // Create basic configuration files
            $files = [
                '.gitignore' => "vendor/\nnode_modules/\n.env\n",
                'README.md' => "# {$projectName}\n\nProject description goes here.\n",
                'composer.json' => json_encode([
                    'name' => strtolower($projectName),
                    'description' => 'Project description',
                    'type' => 'project',
                    'require' => [],
                    'require-dev' => [],
                    'autoload' => [
                        'psr-4' => [
                            'App\\' => 'src/'
                        ]
                    ]
                ], JSON_PRETTY_PRINT)
            ];

            foreach ($files as $file => $content) {
                $this->disk->put($projectPath . '/' . $file, $content);
            }

            return [
                'success' => true,
                'message' => 'Project skeleton created successfully',
                'path' => $projectPath
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function suggest_cli_command($arguments)
    {
        try {
            $command = $arguments['command'] ?? null;
            $description = $arguments['description'] ?? null;

            if (!$command) {
                return ['success' => false, 'error' => 'Missing required parameter: command'];
            }

            // Validate command format and security
            if (preg_match('/[;&|]/', $command)) {
                return ['success' => false, 'error' => 'Invalid command format: Command chaining not allowed'];
            }

            return [
                'success' => true,
                'data' => [
                    'command' => $command,
                    'description' => $description,
                    'warning' => 'Please review this command before execution'
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function suggest_new_file($arguments)
    {
        try {
            $filePath = $arguments['file_path'] ?? null;
            $content = $arguments['content'] ?? null;
            $context = $arguments['context'] ?? null;

            if (!$filePath || !$content || !$context) {
                return ['success' => false, 'error' => 'Missing required parameters: file_path, content, context'];
            }

            // Check if file already exists
            if ($this->disk->exists($filePath)) {
                return ['success' => false, 'error' => 'File already exists'];
            }

            return [
                'success' => true,
                'data' => [
                    'file_path' => $filePath,
                    'content' => $content,
                    'context' => $context,
                    'warning' => 'Please review the suggested file content before creation'
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

}
