<?php

namespace App\Services;

use App\Models\Assistant;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

class SingleUseAssistantService
{
    protected OpenAIAssistant $openAIAssistant;
    protected array $toolExecutors;
    protected string $assistantId;
    protected string $prompt;
    protected ?string $systemMessage;
    protected array $tools;

    public function __construct(
        string $assistantId,
        string $prompt,
        ?string $systemMessage = null,
        array $tools = []
    ) {

        $this->assistant = Assistant::find($assistantId);
        if (!$this->assistant) {
            throw new \Exception("Assistant not found: {$assistantId}");
        }

        $this->openAIAssistant = new OpenAIAssistant();
        $this->toolExecutors = [];
        $this->assistantId = $assistantId;
        $this->prompt = $prompt;
        $this->systemMessage = $systemMessage;
        $this->tools = $tools;
    }

    public function addToolExecutor($executor)
    {
        $this->toolExecutors[] = $executor;
        return $this;
    }

    public function execute()
    {
        try {
            // Create a thread with system message if provided
            $threadId = $this->openAIAssistant->create_thread(
                $this->systemMessage ?? '',
                'user'
            );

            // Add the user's prompt
            $this->openAIAssistant->add_message($threadId, $this->prompt, 'user');


            $assistant_id = $this->assistant->createOpenAiAssistant();

            // Create and run the assistant
            $runId = $this->openAIAssistant->create_run($threadId, $assistant_id);


            // Handle tool calls if needed
            $maxRetries = 5;
            $retryCount = 0;
            $retryDelay = 2;

            do {
                sleep($retryDelay);
                try {
                    $run = $this->openAIAssistant->get_run($threadId, $runId);
                } catch (\Exception $e) {
                    Log::error("Error retrieving run (attempt $retryCount): " . $e->getMessage());
                    if (++$retryCount > $maxRetries) {
                        throw $e;
                    }
                    continue;
                }

                if ($run['status'] === 'requires_action') {

                    Log::info('[SingleUseAssistantService] run: ' . json_encode($run));

                    $outputs = $this->executeTools($threadId, $runId);
                    if (!empty($outputs)) {
                        $this->openAIAssistant->submitToolOutputs($threadId, $runId, $outputs);
                    }
                }
            } while ($run['status'] !== 'completed' && $run['status'] !== 'failed');

            // Get the final messages
            $messages = $this->openAIAssistant->list_thread_messages($threadId);

            // Clean up the assistant
            $this->openAIAssistant->delete_assistant($assistant_id);

            return [
                'success' => true,
                'messages' => $messages,
                'thread_id' => $threadId,
                'run_id' => $runId
            ];

        } catch (\Exception $e) {
            Log::error("Error in SingleUseAssistantService: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function executeTools($threadId, $runId)
    {
        $run = $this->openAIAssistant->get_run($threadId, $runId);
        $calls = $run['required_action']['submit_tool_outputs']['tool_calls'];
        $outputs = [];

        foreach ($calls as $call) {


            Log::info('[SingleUseAssistantService] executeTools call: ' . json_encode($call));

            $method_name = $call['function']['name'];
            $method_args = json_decode($call['function']['arguments'], true);
            $data = null;

            // Loop through all tool executors
            foreach ($this->toolExecutors as $executor) {

                $class_name = get_class($executor);
                Log::info('[SingleUseAssistantService] executor: ' . $class_name);


                
                if (method_exists($executor, $method_name)) {
                    Log::info("Executing method on {$class_name}:", [
                        'method' => $method_name,
                        'args' => $method_args
                    ]);

                    $data = call_user_func([$executor, $method_name], $method_args);
                    
                    Log::info("Function call results", [
                        'results' => $data
                    ]);
                    break;
                }
            }

            if ($data !== null) {
                $outputs[] = [
                    'tool_call_id' => $call['id'],
                    'output' => json_encode($data)
                ];
            }
        }

        return $outputs;
    }
} 