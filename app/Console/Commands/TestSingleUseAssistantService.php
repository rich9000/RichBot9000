<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SingleUseAssistantService;
use App\Models\Assistant;
use App\Services\ToolExecutor;
use App\Services\CodingExecutor;
use App\Services\Executors\SurveyExecutor;
use App\Services\Executors\RainbowExecutor;
use Illuminate\Support\Facades\Log;

class TestSingleUseAssistantService extends Command
{
    protected $signature = 'test:assistant 
        {assistant_id? : The ID of the assistant to use}
        {prompt? : The prompt to send to the assistant}
        {--list-assistants : List all available assistants}
        {--debug : Enable debug mode}';

    protected $description = 'Test the SingleUseAssistantService with a specific assistant';

    public function handle()
    {
        try {
            if ($this->option('list-assistants')) {
                $this->listAssistants();
                return 0;
            }

            $assistantId = $this->argument('assistant_id');
            $prompt = $this->argument('prompt');

            if (!$assistantId || !$prompt) {
                $this->error('Both assistant_id and prompt are required unless --list-assistants is used');
                $this->info('Usage: php artisan test:assistant <assistant_id> "Your prompt here"');
                return 1;
            }

            $assistant = Assistant::find($assistantId);
            if (!$assistant) {
                $this->error("Assistant not found: {$assistantId}");
                return 1;
            }

            if (!$assistant->model) {
                $this->error("Assistant {$assistant->name} has no model configured");
                return 1;
            }

            $this->info("Using assistant: {$assistant->name}");
            $this->info("Model: {$assistant->model}");
            $this->info("Prompt: {$prompt}");
            $this->info("System message: " . ($assistant->system_message ?: 'None'));

            // Create the service
            $service = new SingleUseAssistantService(
                $assistantId,
                $prompt,
                $assistant->system_message
            );

            // Add tool executors
            $service->addToolExecutor(new ToolExecutor())
                    ->addToolExecutor(new CodingExecutor())
                    ->addToolExecutor(new SurveyExecutor())
                    ->addToolExecutor(new RainbowExecutor());

            // Execute the service
            $this->info("\nExecuting assistant...");
            $result = $service->execute();

            if (!$result['success']) {
                $this->error("Error: {$result['error']}");
                Log::error("Assistant execution failed", [
                    'assistant_id' => $assistantId,
                    'error' => $result['error']
                ]);
                return 1;
            }

            // Display the results
            $this->info("\nResponse:");
            $hasResponse = false;
            foreach ($result['messages'] as $message) {
                if ($message['role'] === 'assistant') {
                    foreach ($message['content'] as $content) {
                        if ($content['type'] === 'text') {
                            $this->line($content['text']['value']);
                            $hasResponse = true;
                        }
                    }
                }
            }

            if (!$hasResponse) {
                $this->warn("No text response received from assistant");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Unexpected error: " . $e->getMessage());
            Log::error("Unexpected error in TestSingleUseAssistantService", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    protected function listAssistants()
    {
        try {
            $assistants = Assistant::all();
            
            if ($assistants->isEmpty()) {
                $this->info('No assistants found.');
                return;
            }

            $this->info('Available Assistants:');
            $this->info('===================');

            foreach ($assistants as $assistant) {
                $this->info("\nID: {$assistant->id}");
                $this->info("Name: {$assistant->name}");
                $this->info("Model: " . ($assistant->model ?: 'Not configured'));
                $this->info("Type: {$assistant->type}");
                $this->info("Created: {$assistant->created_at}");
                $this->info("Last Used: " . ($assistant->last_used ?: 'Never'));
                $this->info("Times Used: {$assistant->times_used}");
                $this->info("-------------------");
            }
        } catch (\Exception $e) {
            $this->error("Error listing assistants: " . $e->getMessage());
            Log::error("Error listing assistants", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 