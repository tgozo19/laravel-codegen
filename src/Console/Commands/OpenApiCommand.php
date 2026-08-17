<?php

namespace Tgozo\LaravelCodegen\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class OpenApiCommand extends Command
{
    protected $signature = 'codegen:openapi {--output=openapi.json : Output filename for openapi spec}';

    protected $description = 'Generate OpenAPI 3.0.0 JSON specification for models and API endpoints';

    public function handle(): int
    {
        $output = $this->option('output') ?? 'openapi.json';
        $modelsDir = app_path('Models');

        $schemas = [];
        $paths = [];

        if (File::isDirectory($modelsDir)) {
            $files = File::files($modelsDir);
            foreach ($files as $file) {
                $modelName = $file->getFilenameWithoutExtension();
                $modelPlural = Str::plural(Str::kebab($modelName));
                $modelVariable = Str::camel($modelName);

                $schemas[$modelName] = [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'example' => 1],
                        'created_at' => ['type' => 'string', 'format' => 'date-time'],
                        'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                    ],
                ];

                $paths["/api/{$modelPlural}"] = [
                    'get' => [
                        'summary' => "List all {$modelPlural}",
                        'responses' => [
                            '200' => ['description' => 'Successful operation'],
                        ],
                    ],
                    'post' => [
                        'summary' => "Create a new {$modelName}",
                        'responses' => [
                            '201' => ['description' => 'Created successfully'],
                        ],
                    ],
                ];

                $paths["/api/{$modelPlural}/{id}"] = [
                    'get' => [
                        'summary' => "Get {$modelName} by ID",
                        'responses' => [
                            '200' => ['description' => 'Successful operation'],
                            '404' => ['description' => 'Not found'],
                        ],
                    ],
                    'put' => [
                        'summary' => "Update {$modelName}",
                        'responses' => [
                            '200' => ['description' => 'Updated successfully'],
                        ],
                    ],
                    'delete' => [
                        'summary' => "Delete {$modelName}",
                        'responses' => [
                            '204' => ['description' => 'Deleted successfully'],
                        ],
                    ],
                ];
            }
        }

        $openApiData = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => config('app.name', 'Laravel') . ' API',
                'version' => '1.0.0',
                'description' => 'Auto-generated OpenAPI specification by Laravel CodeGen',
            ],
            'paths' => $paths,
            'components' => [
                'schemas' => $schemas,
            ],
        ];

        $targetPath = base_path($output);
        File::put($targetPath, json_encode($openApiData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("Generated OpenAPI spec at [{$targetPath}].");

        return Command::SUCCESS;
    }
}
