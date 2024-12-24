<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledCronbot;
use App\Models\Conversation;
use App\Models\Tool;
use App\Services\OpenAIAssistant;
use App\Services\ToolExecutor;
use App\Services\CodingExecutor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RunCronbots extends Command
{
    protected $signature = 'cronbots:run 
                          {--list : List all active cronbots}
                          {--run-id= : Run a specific cronbot by ID}';
                          
    protected $description = 'Run scheduled cronbots. Use --list to see available cronbots or --run-id to run a specific one.';

    public function handle()
    {
        // Handle --list option
        if ($this->option('list')) {
            return $this->listCronbots();
        }

        $openai = new OpenAIAssistant();
        $this->info('Starting cronbot execution...');

        // Get cronbots based on options
        $cronbots = $this->getCronbots();

        if ($cronbots->isEmpty()) {
            $this->info('No cronbots to run at this time.');
            return;
        }

        foreach ($cronbots as $cronbot) {
            $this->processCronbot($cronbot, $openai);
        }

        $this->info('Cronbot execution completed');
    }

    private function listCronbots()
    {
        $cronbots = ScheduledCronbot::with('assistant')
            ->where('is_active', true)
            ->get();

           

        if ($cronbots->isEmpty()) {
            $this->info('No active cronbots found.');
            return;
        }

        $this->table(
            ['ID', 'Assistant', 'Prompt', 'Next Run', 'Repeat Interval', 'Status'],
            $cronbots->map(function ($cronbot) {



                return [
                    'id' => $cronbot->id,
                    'assistant' => $cronbot->assistant->name ?? 'N/A',
                    'prompt' => \Str::limit($cronbot->prompt, 30),
                    'next_run' => $cronbot->next_run_at,
                    'repeat' => $cronbot->repeat_interval ?? 'one-time',
                    'status' => $cronbot->is_active ? 'Active' : 'Inactive'
                ];
            })
        );
    }

    private function getCronbots()
    {
        $query = ScheduledCronbot::with('assistant');

        // If run-id is specified, get only that cronbot
        if ($runId = $this->option('run-id')) {
            return $query->where('id', $runId)->get();
        }

        // Otherwise get all due cronbots
        return $query->where('is_active', true)
            ->where(function($query) {
                $query->where('next_run_at', '<=', Carbon::now())
                      ->where(function($q) {
                          $q->whereNull('end_at')
                            ->orWhere('end_at', '>', Carbon::now());
                      });
            })
            ->get();
    }

    private function processCronbot($cronbot, $openai)
    {
        $this->info("Processing cronbot ID: {$cronbot->id}");
        
        try {
            // Execute the assistant with the cronbot's prompt
            $run_info = $cronbot->assistant->startOpenAiRun($cronbot->prompt);
            $conversation = Conversation::find($run_info['conversation_id']);

            if (!$conversation) {
                throw new \Exception("Failed to create conversation");
            }

            Log::info('Run Info: ' . json_encode($run_info, true));
            $retryCount = 0;
            $maxRetries = 3;

            while (true) {
                try {
                    $run = $openai->get_run($run_info['thread_id'], $run_info['run_id']);
                } catch (\Exception $e) {
                    Log::error("Error retrieving run (attempt $retryCount): " . $e->getMessage());
                    if (++$retryCount > $maxRetries) throw $e;
                    sleep(2);
                    continue;
                }

                if ($run['status'] === 'completed') {
                    $messages = $openai->list_thread_messages($run_info['thread_id']);
                    foreach ($messages as $message) {
                        if ($message['role'] === 'assistant' && isset($message['content'][0]['text'])) {
                            $conversation->addMessage('assistant', $message['content'][0]['text']['value']);
                        }
                    }
                    break;
                }

                if ($run['status'] === 'requires_action') {
                    Log::info('Run requires action: ' . json_encode($run, JSON_PRETTY_PRINT));
                    
                    $toolExecutor = new ToolExecutor();

                    $outputs = [];

                    $calls = $run['required_action']['submit_tool_outputs']['tool_calls'];

                    foreach ($calls as $call) {
                        $method_name = $call['function']['name'];
                        $method_args = json_decode($call['function']['arguments'], true);

                        Log::info("Executing tool: $method_name with args: " . json_encode($method_args));
                        
                        try {
                            // Handle special tool cases first
                          

                            // Handle regular tool execution
                            if (method_exists($toolExecutor, $method_name)) {

                                $data = $toolExecutor->$method_name($method_args);
                                
                                // Log successful tool execution
                                $conversation->addMessage('tool', 
                                    "Tool: $method_name\nArgs: " . json_encode($method_args) . 
                                    "\nResponse: " . json_encode($data)
                                );

                                $outputs[] = [
                                    'tool_call_id' => $call['id'],
                                    'output' => json_encode($data)
                                ];

                                // Handle success/fail tools
                                if ($cronbot->success_tool_id && $method_name === $cronbot->successTool->name) {
                                    Log::info("Success tool executed: $method_name");
                                }
                                if ($cronbot->fail_tool_id && $method_name === $cronbot->failTool->name) {
                                    Log::info("Fail tool executed: $method_name");
                                }
                            } else {
                                
                                throw new \Exception("Method $method_name not found in ToolExecutor");
                            }
                        } catch (\Exception $e) {
                            Log::error("Tool execution failed: " . $e->getMessage());
                            $conversation->addMessage('tool', 
                                "Tool Error: $method_name\nArgs: " . json_encode($method_args) . 
                                "\nError: " . $e->getMessage()
                            );
                            $outputs[] = [
                                'tool_call_id' => $call['id'],
                                'output' => json_encode(['error' => $e->getMessage()])
                            ];
                        }
                    }

                    $openai->submit_tool_outputs($run_info['thread_id'], $run_info['run_id'], $outputs);
                    continue; 
                }

                if ($run['status'] === 'failed') {
                    throw new \Exception("Run failed: " . ($run['last_error']['message'] ?? 'Unknown error'));
                }

                sleep(2);
            }

            Log::info('Cronbot completed: ' . $cronbot->id);
            Log::info('Cronbot timing: ' . json_encode($cronbot, JSON_PRETTY_PRINT));
            Log::info('Cronbot conversation: '.__LINE__ . json_encode($conversation->getConversationMessages(), JSON_PRETTY_PRINT));

            // Update cronbot timing only if not running a specific ID
            if (!$this->option('run-id')) {
                $cronbot->last_run_at = now();
                if ($cronbot->is_repeating) {
                    $cronbot->next_run_at = $cronbot->schedule ? 
                        (new \Cron\CronExpression($cronbot->schedule))->getNextRunDate() : 
                        $this->calculateNextRun($cronbot);
                } else {
                    $cronbot->is_active = false;
                }
                $cronbot->save();
            }

          

        } catch (\Exception $e) {
            $this->error("Error processing cronbot {$cronbot->id}: " . $e->getMessage());
            Log::error("Cronbot {$cronbot->id} failed: " . $e->getMessage());
            
            if ($conversation) {
                $conversation->addMessage('system', "Error: " . $e->getMessage());
            }
            
           
        }
    }

    private function calculateNextRun(ScheduledCronbot $cronbot)
    {
        $current = Carbon::parse($cronbot->next_run_at);
        
        switch ($cronbot->repeat_interval) {
            case 'hourly':
                return $current->addHour();
            case 'twice_daily':
                return $current->addHours(12);
            case 'daily':
                return $current->addDay();
            case 'weekly':
                return $current->addWeek();
            case 'monthly':
                return $current->addMonth();
            default: 
                return null;
        }
    }
} 


