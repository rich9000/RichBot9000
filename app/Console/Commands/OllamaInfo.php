<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OllamaApiClient;
use Exception;

class OllamaInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ollama:info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display comprehensive information from the Ollama API';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $client = new OllamaApiClient();

        $this->info('Fetching information from Ollama API...');
        $this->line(str_repeat('=', 50));

        try {
            // 1. List All Local Models
            $this->info("1. Listing All Local Models:");
            $localModels = $client->listLocalModels();

            if (empty($localModels['models'])) {
                $this->warn("No local models found.");
            } else {
                $this->table(
                    ['Name', 'Modified At', 'Size (Bytes)', 'Digest', 'Format', 'Family', 'Parameters Size', 'Quantization Level'],
                    array_map(function ($model) {
                        return [
                            'Name'                => $model['name'] ?? 'N/A',
                            'Modified At'         => $model['modified_at'] ?? 'N/A',
                            'Size (Bytes)'        => number_format($model['size'] ?? 0),
                            'Digest'              => $model['digest'] ?? 'N/A',
                            'Format'              => $model['details']['format'] ?? 'N/A',
                            'Family'              => $model['details']['family'] ?? 'N/A',
                            'Parameters Size'     => $model['details']['parameter_size'] ?? 'N/A',
                            'Quantization Level'  => $model['details']['quantization_level'] ?? 'N/A',
                        ];
                    }, $localModels['models'])
                );

                // Summary Statistics
                $totalModels = count($localModels['models']);
                $totalSize   = array_sum(array_column($localModels['models'], 'size'));
                $this->info("Total Local Models: $totalModels");
                $this->info("Total Size of Local Models: " . number_format($totalSize) . " Bytes");
            }

            $this->line(str_repeat('-', 50));

            // 2. Show Detailed Information for Each Model
            if (!empty($localModels['models'])) {
                $this->info("2. Detailed Information for Each Local Model:");

                foreach ($localModels['models'] as $model) {
                    $modelName = $model['name'];
                    $this->info("Model: $modelName");
                    $this->line(str_repeat('-', 20));

                    $showParams = [
                        'name' => $modelName,
                        'verbose' => true, // Assuming 'verbose' is supported for more detailed info
                    ];

                    $modelInfo = $client->showModelInformation($showParams);

                    // Display Modelfile
                    $this->info("Modelfile:");
                    $this->line($modelInfo['modelfile'] ?? 'N/A');

                    // Display Parameters
                    $this->info("Parameters:");
                    $parameters = isset($modelInfo['parameters']) ? explode("\n", $modelInfo['parameters']) : [];
                    foreach ($parameters as $param) {
                        $this->line(" - $param");
                    }

                    // Display Template
                    $this->info("Template:");
                    $this->line($modelInfo['template'] ?? 'N/A');

                    // Display Details
                    $this->info("Details:");
                    if (isset($modelInfo['details'])) {
                        foreach ($modelInfo['details'] as $key => $value) {
                            $this->line("  - $key: " . (is_array($value) ? json_encode($value) : $value));
                        }
                    } else {
                        $this->line("  N/A");
                    }

                    // Display Model Info
                    $this->info("Model Info:");
                    if (isset($modelInfo['model_info'])) {
                        foreach ($modelInfo['model_info'] as $key => $value) {
                            if(str_starts_with($key,'tokenizer')){
                                $this->line("  - $key: --- tokenizer skip");

                            } else {
                                $this->line("  - $key: " . (is_array($value) ? json_encode($value) : $value));
                            }


                        }
                    } else {
                        $this->line("  N/A");
                    }

                    $this->line(str_repeat('-', 50));
                }
            }

            // 3. List Running Models
            $this->info("3. Listing Running Models:");
            $runningModels = $client->listRunningModels();

            if (empty($runningModels['models'])) {
                $this->warn("No running models found.");
            } else {
                $this->table(
                    ['Name', 'Size (Bytes)', 'Digest', 'Format', 'Family', 'Parameters Size', 'Quantization Level', 'Expires At', 'Size in VRAM (Bytes)'],
                    array_map(function ($model) {
                        return [
                            'Name'                => $model['name'] ?? 'N/A',
                            'Size (Bytes)'        => number_format($model['size'] ?? 0),
                            'Digest'              => $model['digest'] ?? 'N/A',
                            'Format'              => $model['details']['format'] ?? 'N/A',
                            'Family'              => $model['details']['family'] ?? 'N/A',
                            'Parameters Size'     => $model['details']['parameter_size'] ?? 'N/A',
                            'Quantization Level'  => $model['details']['quantization_level'] ?? 'N/A',
                            'Expires At'          => $model['expires_at'] ?? 'N/A',
                            'Size in VRAM (Bytes)' => number_format($model['size_vram'] ?? 0),
                        ];
                    }, $runningModels['models'])
                );
            }

            $this->line(str_repeat('-', 50));

            // 4. Additional Information (Optional)
            // You can add more sections here, such as blobs, but based on the provided API documentation,
            // there isn't a direct endpoint to list blobs. You might need to iterate over models or extend the API.

            $this->info("4. Summary:");
            $this->info("Total Local Models: " . (isset($totalModels) ? $totalModels : 'N/A'));
            $this->info("Total Size of Local Models: " . (isset($totalSize) ? number_format($totalSize) . ' Bytes' : 'N/A'));
            $this->info("Total Running Models: " . (isset($runningModels['models']) ? count($runningModels['models']) : 'N/A'));

            $this->line(str_repeat('=', 50));
            $this->info("Ollama API Information Retrieval Completed Successfully.");

        } catch (Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            return 1; // Non-zero exit code indicates failure
        }

        return 0; // Zero exit code indicates success
    }
}
