<?php

namespace App\Console\Commands;

use App\Services\Pipeline as PipelineService;
use App\Models\Message;
use App\Services\ConversationManager;
use App\Services\ToolExecutor;
use Illuminate\Console\Command;
use App\Services\OllamaApiClient;
use Exception;
use App\Models\Tool;
use App\Models\Assistant;
use Illuminate\Process\Pipe;
use Illuminate\Support\Facades\File;
use App\Models\Conversation;
use App\Models\Pipeline;



use ArdaGnsrn\Ollama\Ollama;

class TestOllamaApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xx:o-test';

    /**
     * The console command description.
     *
     * @var string
     */

    protected $description = 'Test the Ollama API client by performing various API calls';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {


        $payload = 'My name is Rich Carroll, and my secret is 1234.';




        $cm = new ConversationManager();

        $toolExecutor = new ToolExecutor();

        $pipeline_name = 'TestPipeline';
        $pl = Pipeline::where('name', $pipeline_name)->first();

        foreach ($pl->stages as $stage) {

            $assistant_name = $stage->assistant->name ?? '-';
            echo "{$stage->id} : {$stage->type} {$assistant_name}\n";

            if($stage->type == 'assistant'){


                $assistant = Assistant::where('name',$stage->assistant->name)->first();


                if($assistant->name == 'gate_keeper'){
                    $prompt = "This customer is asking the following customer prompt, but first you need to verify who the customer is.
<customer_prompt>$payload</customer_prompt> Unless the question is one that does not require account access such as what are our hours.";


                    $prompt = $payload;


                } else {

                    $prompt = $payload;
                }

                $reply = $assistant->askPrompt($prompt);

                dd($reply);



                dd($assistant);

            }

        }

        exit;

//        dump($pl->stages);


        dd($pl);









        $payload = 'What is the weather in paris?';







        $pipeline = new PipelineService();

        $pipeline->addStage(function($payload) {

            echo "in the pipeline for the re-prompter\n";

            $assistantType = 'reprompter';
            $assistant = Assistant::where('name',$assistantType)->first();

            $reply = $assistant->askPrompt($payload);

            return $reply;

        });


        dd($pipeline->run('Expand the following Prompt: What is the weather in paris?'));





        $assistantType = 'reprompter';
        $assistant = Assistant::where('name',$assistantType)->first();

        $conversation = Conversation::create([
            'title' => "Assistant: $assistantType",
            'assistant_type' => $assistantType,
            'active_tools' => $assistant->tools()->pluck('name')->toArray(),
            'system_messages' => $assistant->system_message,
            'model'=>'llama3.2'
        ]);

        $conversation->addMessage('system', $assistant->system_message);
        $conversation->addMessage('user', 'Hey my computer dont work well. ');

        dump($conversation->getConversationMessages());

        $client =Ollama::client('http://192.168.0.104:11434');

        $response = $client->chat()->create([
            'model' => $conversation->model,
            'messages' => $conversation->getConversationMessages(),
            'tools' => $conversation->assistant->generateTools()->toArray(),
        ]);



        if($response->message->content){
            var_dump($response->message->content);
            $conversation->addMessage($response->message->role, $response->message->content);

        }


        while(count($response->message->toolCalls)){

            //$conversation->addMessage('assistant', json_encode($response->message->toolCalls));
            echo "********************* Tool Calls!!!\n ***************************";

            $tool_response = array();

            foreach ($response->message->toolCalls as $toolCall){

                if(property_exists($toolCall, 'function')){

                    $name = $toolCall->function->name;
                    $arguments = $toolCall->function->arguments;

                    if(method_exists($toolExecutor,$name)){

                        $response = $toolExecutor->$name($arguments);
                        $message = array('tool_call_id'=>$toolCall->id,'role'=>'tool','name'=>$name,'content'=>$response);
                        $conversation->addMessage('tool', json_encode($message));

                    }

                }

            }

            echo "Sending Tool Responses\n";
            dump($conversation->getConversationMessages());

            $response = $client->chat()->create([
                'model' => $conversation->model,
                'messages' => $conversation->getConversationMessages(),
                'tools' => $conversation->assistant->generateTools()->toArray(),
            ]);

            if($response->message->content){
                var_dump($response->message->content);
                $conversation->addMessage($response->message->role, $response->message->content);

            }

            dd($response->message->content);

        }

        dd($response);
        dd($response);

        $cm = new ConversationManager();


        $client = new OllamaApiClient();

        $assistant = Assistant::where('name','test')->with('tools')->first();
        $tools = $assistant->generateTools();

        $path = '/var/www/html/audio_transcripts';

        if (File::exists($path) && File::isDirectory($path)) {

            // Get all files and directories
            $files = File::allFiles($path);

        }

        foreach ($files as $file){

            if($file->getExtension() != 'txt'){

                continue;
            }

            $text = file_get_contents($file->getPathname());

            $conversation = Conversation::create([
                'title' => 'Test',
                'assistant_type' => 'test',
                'active_tools' => $assistant->tools()->pluck('name')->toArray(),
                'system_messages' => $assistant->system_message,
            ]);

            // Optionally, add a system message for the assistant's introduction
            $cm->addMessage($conversation->id, 'system', $assistant->system_message);

            $text = 'Hi How are you.';

            $assistantResponse = $cm->sendMessage($conversation->id, $text);

            dump($assistantResponse);

            continue;




            $chatParams = [
                [
                    'role'    => 'system',
                    'content' => $assistant->system_message,
                ],
                [
                    'role'    => 'user',
                    'content' => $text,
                ],
            ];


            $client->generateChatCompletion('llama3.2', $chatParams, $tools, null, false, 1, function ($chunk) use ($tools) {

                $decoded = json_decode($chunk, true);
                dump($decoded);
                exit;
                if (json_last_error() === JSON_ERROR_NONE) {
                    if (isset($decoded['message'])) {
                        $role = $decoded['message']['role'] ?? 'unknown';
                        $content = $decoded['message']['content'] ?? '';
                        if (!$content) {
                            $this->line("[$role] -no content-");

                        } else {

                            $this->line("[$role] $content");
                        }

                        if(isset($decoded['message']['tool_calls']) && $decoded['message']['tool_calls']){

                            $this->line('TOOL CALLS!!********************');

                        }

                        // Check if the AI invoked any tool
                        //$this->checkAndExecuteTool($content, $tools);
                    } elseif (isset($decoded['status'])) {
                        $this->line("[Status] " . $decoded['status']);
                    }
                } else {
                    // Handle partial JSON or other data
                    $this->line("[Raw] " . $chunk);
                }
            });
            echo $text."\n";
            echo "{$file->getPathname()}\n";

        }
exit;


try{





            $this->line("Streaming Chat Completion:");


            //dump($tools);
            //dump($chatParams);

            //$tools = [];














        exit;
        $localModels = $client->listLocalModels();

        dump($localModels);

        $this->info('Starting Ollama API Client Tests...');


        // Generate a Completion and check for triggers
        $this->info("\nGenerating a Completion for Test:");
        $completionResponse = $client->generateCompletion(

            'test-model',
            'Describe the best way to relax after a stressful day.',
            null, // suffix
            null, // images
            null, // options
            null, // system
            null, // template
            null, // context
            false  // stream

        );

        dd($completionResponse);

        // Display the completion response
        $this->line(json_encode($completionResponse, JSON_PRETTY_PRINT));



            // 1. List Local Models
            $this->info("\n1. Listing Local Models:");
            $localModels = $client->listLocalModels();
            $this->line(json_encode($localModels, JSON_PRETTY_PRINT));

            // 2. Create a New Model
            $this->info("\n2. Creating a New Model 'test-model':");
            $createResponse = $client->createModel(
                'test-model',
                "FROM llama3\nSYSTEM You are a helpful assistant."
            );
            $this->line(json_encode($createResponse, JSON_PRETTY_PRINT));

            // 3. Show Model Information
            $this->info("\n3. Showing Information for 'test-model':");
            $modelInfo = $client->showModelInformation('test-model');
            $this->line(json_encode($modelInfo, JSON_PRETTY_PRINT));

            // 4. Generate a Completion (Non-Streaming)
            $this->info("\n4. Generating a Completion for Prompt 'Hello, how are you?':");
            $completionResponse = $client->generateCompletion(
                'test-model',
                'Hello, how are you?',
                null, // suffix
                null, // images
                null, // options
                null, // system
                null, // template
                null, // context
                false  // stream
            );
            $this->line(json_encode($completionResponse, JSON_PRETTY_PRINT));

            // 5. Generate a Chat Completion (Streaming)
            $this->info("\n5. Generating a Chat Completion for Message 'Tell me a joke.':");
            $chatParams = [
                [
                    'role'    => 'user',
                    'content' => 'Tell me a joke.',
                ],
            ];

            $this->line("Streaming Chat Completion:");
            $client->generateChatCompletion('test-model', $chatParams, null, null, null, null, function ($chunk) {
                // Handle each chunk of the streaming response
                $lines = explode("\n", $chunk);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '') {
                        continue;
                    }
                    $decoded = json_decode($line, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        if (isset($decoded['message'])) {
                            $role    = $decoded['message']['role'] ?? 'unknown';
                            $content = $decoded['message']['content'] ?? '';
                            $this->line("[$role] $content");
                        } elseif (isset($decoded['status'])) {
                            $this->line("[Status] " . $decoded['status']);
                        }
                    } else {
                        // Handle partial JSON or other data
                        $this->line("[Raw] " . $line);
                    }
                }
            });

            // 6. Generate Embeddings
            $this->info("\n6. Generating Embeddings for 'Why is the sky blue?':");
            $embeddings = $client->generateEmbeddings('all-minilm', 'Why is the sky blue?');
            $this->line(json_encode($embeddings, JSON_PRETTY_PRINT));

            // 7. Delete the Test Model
            $this->info("\n7. Deleting the Model 'test-model':");
            $deleteResponse = $client->deleteModel('test-model');
            $this->line(json_encode($deleteResponse, JSON_PRETTY_PRINT));

            $this->info("\nAll tests completed successfully.");

        } catch (Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
        }

        return 0;

    }



    /**
     * Check if the AI's response includes a tool invocation and execute the tool.
     *
     * @param string $content The content from the AI's response.
     * @param array  $tools   The list of available tools.
     *
     * @return void
     */
    protected function checkAndExecuteTool($content, $tools)
    {
        foreach ($tools as $tool) {
            // Simple pattern matching to detect tool invocation
            // This can be enhanced based on how tool invocations are structured
            if (stripos($content, $tool['name']) !== false) {
                $this->info("Detected tool invocation: {$tool['name']}");
                $this->executeToolAction($tool['action'], $content);
            }
        }
    }

    /**
     * Execute the action associated with a tool.
     *
     * @param string $action  The action identifier.
     * @param string $context The context or query for the tool.
     *
     * @return void
     */
    protected function executeToolAction($action, $context)
    {
        switch ($action) {
            case 'calculate':
                // Example: Extract expression and calculate
                preg_match('/calculate (.+)/i', $context, $matches);
                if (isset($matches[1])) {
                    $expression = $matches[1];
                    $result = eval("return $expression;"); // WARNING: eval can be dangerous!
                    $this->info("Calculator Result: $result");
                }
                break;

            case 'wiki_search':
                // Example: Extract query and perform Wikipedia search
                preg_match('/search (.+)/i', $context, $matches);
                if (isset($matches[1])) {
                    $query = $matches[1];
                    // Implement Wikipedia search logic here
                    $this->info("Wikipedia Search for: $query");
                }
                break;

            case 'weather_info':
                // Example: Extract location and fetch weather info
                preg_match('/weather in (.+)/i', $context, $matches);
                if (isset($matches[1])) {
                    $location = $matches[1];
                    // Implement weather info retrieval here
                    $this->info("Fetching weather info for: $location");
                }
                break;

            default:
                $this->info("No action defined for tool: $action");
                break;
        }
    }
}
