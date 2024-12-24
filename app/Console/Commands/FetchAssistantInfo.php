<?php

namespace App\Console\Commands;

use App\Services\OpenAIAssistant;
use App\Models\AssistantFunction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;

class FetchAssistantInfo extends Command
{
    // The name and signature of the console command.
    protected $signature = 'test:AssistantInfo';

    // The console command description.
    protected $description = 'Fetch all assistant methods and insert them into the database with version control based on content changes';

    // Create a new command instance.
    public function __construct()
    {
        parent::__construct();
    }

    // Execute the console command.
    public function handle()
    {
        $openAI = new OpenAIAssistant();

        try {
            $assistants = $openAI->list_assistants();
            $functions = [];

            foreach ($assistants as $assistant) {
                $tools = $assistant['tools'] ?? [];

                foreach ($tools as $tool) {
                    if (isset($tool['function'])) {
                        $function = $tool['function'];
                        $functions[] = [
                            'name' => $function['name'],
                            'description' => $function['description'],
                            'parameters' => $this->canonicalizeJson($function['parameters']),
                            'code' => '', // Assuming code is not provided here
                            'status' => 'active',
                            'version' => '1.0', // Initial version; will be updated if needed
                            'execution_count' => 0,
                            'last_executed_at' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            // Display the functions for debugging
            $this->info('Fetched Functions:');
            dump($functions);

            // Start a database transaction for atomicity
            DB::beginTransaction();

            foreach ($functions as $functionData) {
                // Validate the function data
                $validator = \Validator::make($functionData, [
                    'name' => 'required|string',
                    'description' => 'required|string',
                    'parameters' => 'required|string',
                    'code' => 'nullable|string',
                    'status' => 'required|in:active,inactive',
                    'version' => 'required|string',
                    'execution_count' => 'required|integer',
                    'last_executed_at' => 'nullable|date',
                ]);

                if ($validator->fails()) {
                    $this->error("Validation failed for function '{$functionData['name']}': " . implode(', ', $validator->errors()->all()));
                    continue; // Skip this function
                }

                // Find the latest version of the function by name
                $latestFunction = AssistantFunction::where('name', $functionData['name'])
                    ->orderBy('version', 'desc')
                    ->first();

                if ($latestFunction) {
                    // Compare the existing parameters with the new ones
                    $existingParameters = $this->canonicalizeJson(json_decode($latestFunction->parameters, true));
                    $newParameters = $this->canonicalizeJson(json_decode($functionData['parameters'], true));

                    if ($existingParameters === $newParameters) {
                        // Parameters have not changed; skip inserting a new version
                        $this->info("Skipping function '{$functionData['name']}' as parameters have not changed.");
                        continue;
                    } else {
                        // Parameters have changed; increment the version
                        $currentVersion = floatval($latestFunction->version);
                        $newVersion = number_format($currentVersion + 1, 1);

                        $functionData['version'] = $newVersion;
                        $this->info("Updating function '{$functionData['name']}' to version {$newVersion}.");
                    }
                } else {
                    // If the function does not exist, start with version 1.0
                    $functionData['version'] = '1.0';
                    $this->info("Inserting new function '{$functionData['name']}' with version 1.0.");
                }

                // Create the new AssistantFunction record
                AssistantFunction::create($functionData);
            }

            // Commit the transaction
            DB::commit();

            $this->info('Successfully fetched and stored assistant methods with proper versioning.');
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();
            $this->error('Failed to fetch assistants: ' . $e->getMessage());
        }
    }

    /**
     * Canonicalize JSON data by sorting keys and encoding it.
     *
     * @param array $data The JSON data as an associative array.
     * @return string The canonicalized JSON string.
     */
    private function canonicalizeJson(array $data): string
    {
        $this->ksortRecursive($data);
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Recursively sort an associative array by its keys.
     *
     * @param array &$array The array to sort.
     * @return void
     */
    private function ksortRecursive(array &$array)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
        ksort($array);
    }
}
