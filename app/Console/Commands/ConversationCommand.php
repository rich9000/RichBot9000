<?php

namespace App\Console\Commands;

use App\Models\Assistant;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Services\ToolExecutor;
use App\Models\Tool;
use App\Services\OpenAIAssistant;
use App\Models\AssistantFunction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\Conversation;
use Illuminate\Support\Str;
class ConversationCommand extends Command
{

    // Command signature and descriptionfff
    protected $signature = 'zz:Conv {--check-tools} {--list-tools} {--list-pipelines} {--list-assistants} {--test-pipeline} {--test-pipeline-id=} {--test-prompt=} {--test-assistant-id=}';
    protected $description = 'Performs various conversation-related tasks';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Command logic will go here
        if ($this->option('check-tools')) {
            $this->verifyTools();
        }

        if ($this->option('test-assistant-id')) {
            $this->testOpenaiPipeline();
        }

        if ($this->option('test-pipeline')) {
            //$this->testOpenaiPipeline();
        }
        if ($this->option('test-pipeline-id')) {

            $prompt = $this->option('test-pipeline-id');
            $this->testPipeline($this->option('test-pipeline-id'),$this->option('test-prompt'));

        }

        if ($this->option('list-tools')) {
            $this->listAllTools();
        }

        if ($this->option('list-pipelines')) {
            $this->listPipelines();
        }

        if ($this->option('list-assistants')) {
            $this->listAssistants();
        }




    }

    protected function testPipeline($pipeline_id, $prompt)
    {

        $pipeline = Pipeline::find($pipeline_id);

        echo "Pipeline: {$pipeline->id} {$pipeline->name}\nDescription: {$pipeline->description}\n";

        $title = 'Pipeline: ' . $pipeline->name;

        try {
            $conversation = Conversation::create([
                'title' => $title,
                'pipeline_id' => $pipeline->id,
                'pipeline_status' => 'active',
                'prompt' => $prompt,
            ]);

            $conversation->addMessage('prompt', $prompt);

        } catch (\Exception $e) {
            dd($e->getMessage());
        }


        //   $first_stage = $pipeline->stages()->orderBy('order')->get()->first();
        //    echo "## First Stage:{$first_stage->id} {$first_stage->type} {$first_stage->successTool->name} \n";


        $openai = new OpenAIAssistant();
        $messages_array = $conversation->messages()->orderBy('created_at')->get();

        foreach ($pipeline->stages()->orderBy('order')->get() as $stage) {
            echo "******************* NEW STAGE: {$stage->id} {$stage->name} {$stage->type} {$stage->successTool->name} \n";

            foreach ($stage->assistants()->orderBy('order')->get() as $assistant) {

                $interactive = ($assistant->interactive == 1) ? 'Interactive' : 'Non-Interactive';

                dump($assistant);
                echo "------------- Assistant ID:{$assistant->id} {$interactive}:  {$assistant->name} {$assistant->type} \n";

                switch ($assistant->type) {
                    case 'context':

                        $prompt = $conversation->getPrompt();

                        // Set up prompt with the intention to summarize
                        $prompt = "You are a type of assistant that does not answer the prompt, but gathers information for answering the prompt. Here is the prompt: <prompt>{$prompt->content}</prompt>\n";
                        if ($conversation && $conversation->getConversationMessages()) {

                            $prompt .= "Here is the conversation so far if that helps:\n";
                            $prompt .= print_r($conversation->getConversationMessages(), true);

                        }

                        echo "Its a context Assistant. Here is the prompt we are giving it: \n&&&&&&&&&&&\n $prompt";
                        echo "\n&&&&&&&&&&&\n";

                        $assistant_id = $assistant->createOpenAiAssistant();
                        dump("Assistant ID: {$assistant_id}");

                        $thread_id = $openai->create_thread();
                        dump("Thread ID: {$thread_id}");

                        $openai->add_message($thread_id, $prompt, 'user');

                        // Create a run with the assistant
                        $run_id = $openai->create_run($thread_id, $assistant_id);
                        dump("Run ID: {$run_id}");

                        // Poll for completion
                        $retryDelay = 3;
                        $maxRetries = 3;
                        $retryCount = 0;

                        do {

                            sleep($retryDelay);
                            try {
                                $run = $openai->get_run($thread_id, $run_id);
                            } catch (\Exception $e) {
                                \Log::error("Error retrieving run (attempt $retryCount): " . $e->getMessage());
                                if (++$retryCount > $maxRetries) {
                                    throw $e;
                                }
                                continue;
                            }

                            if ($run['status'] == 'requires_action') {
                                // Handle tool execution if required
                                $toolExecutor = new ToolExecutor(); // Implement this class

                                $calls = $run['required_action']['submit_tool_outputs']['tool_calls'];
                                $outputs = [];
                                $log_entry = '';

                                $success_called = false;
                                $stage_success_called = false;
                                $assistant_success_called = false;

                               // dump($calls);

                                foreach ($calls as $call) {

                                    echo "Call\n";

                                    //var_dump($call);

                                    $method_name = $call['function']['name'];
                                    $method_args = json_decode($call['function']['arguments'], true);

                                    if ($method_name == 'stage_complete') {


                                        if ($method_name == $stage->successTool->name) {

                                            echo "%%%%%%% Stage Success Tool Called, Last Loop  %%%%%%\n\n";

                                            $stage_success_called = true;
                                            $outputs[] = [
                                                'tool_call_id' => $call['id'],
                                                'output' => json_encode(['message' => 'stage complete'])
                                            ];


                                        }

                                        continue;

                                    }
                                    if ($method_name == 'submit_context') {

                                        $content = $method_args['content'] ?? '';

                                        $outputs[] = [
                                            'tool_call_id' => $call['id'],
                                            'output' => json_encode(['content' => $content])
                                        ];

                                        //var_dump($method_args);

                                        $conversation->addMessage('context', $content);

                                        if ($method_name == $assistant->successTool->name) {

                                            echo "%%%%%%% Success Tool Called, Last Loop  %%%%%%\n\n";
                                            $success_called = true;
                                        }
                                        continue;

                                    }

                                    if ($method_name == 'add_context_message') {


                                        $outputs[] = [
                                            'tool_call_id' => $call['id'],
                                            'output' => json_encode(['context', $method_args['context']])
                                        ];


                                        //   var_dump($method_args);

                                        $conversation->addMessage('context', $method_args['context']);

                                        if ($method_name == $assistant->successTool->name) {

                                            echo "%%%%%%% Success Tool Called, Last Loop  %%%%%%\n\n";

                                            $success_called = true;


                                        }

                                        continue;

                                    }


                                    $callable = $toolExecutor ?
                                        [$toolExecutor, $method_name] : $method_name;

                                    \Log::info("OpenAIAssistant $method_name " . class_basename($toolExecutor) . json_encode($call) . print_r($method_args, true));
                                    echo "OpenAIAssistant $method_name " . class_basename($toolExecutor) . json_encode($call) . print_r($method_args, true);

                                    if (is_callable($callable)) {
                                        $data = call_user_func($callable, $method_args);

                                        $conversation->addMessage('tool', print_r([$method_name => [
                                            'tool_call_id' => $call['id'],
                                            'output' => json_encode($data)
                                        ]], true));


                                        $outputs[] = [
                                            'tool_call_id' => $call['id'],
                                            'output' => json_encode($data)
                                        ];

                                        $log_entry .= "$method_name -> " . print_r($method_args, true);

                                    } else {
                                        throw new \Exception("Failed to execute tool: The $method_name you provided is not callable");
                                    }

                                    if ($method_name == $assistant->successTool->name) {
                                        Log::info("%%%%%%% Success Tool Called, Last Loop  %%%%%%\n\n");

                                        $assistant_success_called = true;


                                    }
                                    if ($method_name == $stage->successTool->name) {

                                        Log::info("%%%%%%% Stage Success Tool Called, Last Loop  %%%%%%\n\n");

                                        $stage_success_called = true;


                                    }

                                }

                                if ($success_called) {
                                    echo "\n\n%%%%%%%%%%%%% BREAKING OUT OF RUN - {$assistant->successTool->name} %%%%%%%%%%%%\n";
                                    echo "%%%%%%%%%%%%% KILLING THE ASSISTANT - {$assistant->name} %%%%%%%%%%%%\n\n";

                                    $openai->delete_assistant($assistant_id);
                                    break 2;
                                }

                                if ($stage_success_called) {
                                    echo "\n\n%%%%%%%%%%%%% BREAKING OUT OF STAGE - {$stage->successTool->name} %%%%%%%%%%%%\n";
                                    echo "%%%%%%%%%%%%% KILLING THE ASSISTANT - {$assistant->name} %%%%%%%%%%%%\n\n";

                                    $openai->delete_assistant($assistant_id);
                                    break 3;
                                }

                                //$outputs = $openai->execute_tools($thread_id, $run_id, $toolExecutor);
                                dump($outputs);
                                $response = $openai->submitToolOutputs($thread_id, $run_id, $outputs);


                            }

                        } while ($run['status'] != 'completed' && $run['status'] != 'failed');


                        $openai->delete_assistant($assistant_id);

                        $messages = $openai->list_thread_messages($thread_id);

                        foreach ($messages as $msg) {
                            if (in_array($msg['role'], ['assistant', 'user'])) {
                                if ($msg['content'][0]['type'] == 'text') {
                                    $message_content = $msg['content'][0]['text']['value'];
                                    $role = $msg['role'];
                                    echo " $role, $message_content\n";
                                }
                            }
                        }

                        break;

                    case 'transform':
                        // Indicate the prompt should lead to a tool execution
                        // $assistant_prompt = "{$processed_prompt} - Execute tool action.";
                        break;

                    case 'assistant':

                        echo "We got an assistant. Lets just work the whole conversation.\n";

                        $assistant_id = $assistant->createOpenAiAssistant();

                        dump("Assistant ID: {$assistant_id}");
                        dump("Assistant Name: {$assistant->name}");

                        $thread_id = $openai->create_thread();
                        dump("Thread ID: {$thread_id}");


                        $messages_array = $conversation->getConversationMessages();

                        foreach ($messages_array as $message) {
                            if ($message['role'] == 'user' || $message['role'] == 'assistant') {
                                $openai->add_message($thread_id, $message->content, $message->role);
                            }
                        }
                        $openai->add_message($thread_id, $prompt, 'user');

                        $prompt = $conversation->getPrompt();
                        $openai->add_message($thread_id, $prompt, 'user');
                        $run_id = $openai->create_run($thread_id, $assistant_id);
                        dump("Run ID: {$run_id}");

                        // Poll for completion
                        $retryDelay = 3;
                        $maxRetries = 3;
                        $retryCount = 0;

                        do {

                            sleep($retryDelay);
                            try {
                                $run = $openai->get_run($thread_id, $run_id);
                            } catch (\Exception $e) {
                                \Log::error("Error retrieving run (attempt $retryCount): " . $e->getMessage());
                                if (++$retryCount > $maxRetries) {
                                    throw $e;
                                }
                                continue;
                            }

                            if ($run['status'] == 'requires_action') {
                                // Handle tool execution if required
                                $toolExecutor = new ToolExecutor(); // Implement this class

                                $calls = $run['required_action']['submit_tool_outputs']['tool_calls'];
                                $outputs = [];
                                $log_entry = '';

                                $success_called = false;

                                dump($calls);

                                foreach ($calls as $call) {

                                    echo "Call\n";

                                    var_dump($call);

                                    $method_name = $call['function']['name'];
                                    $method_args = json_decode($call['function']['arguments'], true);

                                    if ($method_name == 'submit_context') {

                                        $content = $method_args['content'] ?? '';

                                        $outputs[] = [
                                            'tool_call_id' => $call['id'],
                                            'output' => json_encode(['content' => $content])
                                        ];

                                        //var_dump($method_args);

                                        $conversation->addMessage('context', $content);

                                        if ($method_name == $assistant->successTool->name) {

                                            echo "%%%%%%% Success Tool Called, Last Loop  %%%%%%\n\n";

                                            $success_called = true;


                                        }


                                        continue;

                                    }

                                    if ($method_name == 'add_context_message') {


                                        $outputs[] = [
                                            'tool_call_id' => $call['id'],
                                            'output' => json_encode(['context', $method_args['context']])
                                        ];


                                        var_dump($method_args);

                                        exit;

                                        $conversation->addMessage('context', $method_args['context']);

                                        if ($method_name == $assistant->successTool->name) {

                                            echo "%%%%%%% Success Tool Called, Last Loop  %%%%%%\n\n";

                                            $success_called = true;


                                        }

                                        continue;

                                    }


                                    $callable = $toolExecutor ?
                                        [$toolExecutor, $method_name] : $method_name;

                                    \Log::info("OpenAIAssistant $method_name " . class_basename($toolExecutor) . json_encode($call) . print_r($method_args, true));
                                    echo "OpenAIAssistant $method_name " . class_basename($toolExecutor) . json_encode($call) . print_r($method_args, true);

                                    if (is_callable($callable)) {
                                        $data = call_user_func($callable, $method_args);
                                        $outputs[] = [
                                            'tool_call_id' => $call['id'],
                                            'output' => json_encode($data)
                                        ];
                                        $log_entry .= "$method_name -> " . print_r($method_args, true);
                                    } else {
                                        throw new \Exception("Failed to execute tool: The $method_name you provided is not callable");
                                    }

                                    if ($method_name == $assistant->successTool->name) {

                                        echo "%%%%%%% Success Tool Called, Last Loop  %%%%%%\n\n";

                                        $success_called = true;


                                    }

                                }

                                if ($success_called) {
                                    echo "\n\n%%%%%%%%%%%%% BREAKING OUT OF RUN - {$assistant->successTool->name} %%%%%%%%%%%%\n";
                                    echo "%%%%%%%%%%%%% KILLING THE ASSISTANT - {$assistant->name} %%%%%%%%%%%%\n\n";

                                    $openai->delete_assistant($assistant_id);
                                    break 2;
                                }

                                //$outputs = $openai->execute_tools($thread_id, $run_id, $toolExecutor);
                                dump($outputs);
                                $response = $openai->submitToolOutputs($thread_id, $run_id, $outputs);


                            }

                        } while ($run['status'] != 'completed' && $run['status'] != 'failed');


                        $openai->delete_assistant($assistant_id);

                        $messages = $openai->list_thread_messages($thread_id);

                        foreach ($messages as $msg) {
                            if (in_array($msg['role'], ['assistant', 'user'])) {
                                if ($msg['content'][0]['type'] == 'text') {
                                    $message_content = $msg['content'][0]['text']['value'];
                                    $role = $msg['role'];
                                    echo " $role, $message_content\n";
                                }
                            }
                        }

                        // Basic message handling with no extra processing
                        $assistant_prompt = $prompt;
                        break;

                    default:
                        $assistant_prompt = $prompt;
                        $conversation->addMessage('system', "Unknown assistant type '{$assistant->type}'.");
                }


            }


            echo "## Stage Assistants Done ##\n";


        }


        echo "+++++++++++++ Final Message Array: ++++++++++++++++\n";
        // Output the final conversation messages

        $messages_array = $conversation->messages()->orderBy('created_at')->get();

        foreach ($conversation->getConversationMessages() as $message){
            $content = Str::limit($message['content']);
            echo "{$message['role']} : $content\n";

        }
    }
    protected function testOpenaiPipeline($pipelineId, $prompt)
    {

        $pipeline = Pipeline::find($pipeline_id);

        echo "Pipeline: {$pipeline->id} {$pipeline->name}\nDescription: {$pipeline->description}\n";

        $title = 'Pipeline: ' . $pipeline->name;

        try {
            $conversation = Conversation::create([
                'title'           => $title,
                'pipeline_id'     => $pipeline->id,
                'pipeline_status' => 'active',
                'prompt'            => $prompt,
            ]);

            $conversation->addMessage('prompt', $prompt);

        } catch (\Exception $e) {
            dd($e->getMessage());
        }


     //   $first_stage = $pipeline->stages()->orderBy('order')->get()->first();
    //    echo "## First Stage:{$first_stage->id} {$first_stage->type} {$first_stage->successTool->name} \n";



        $openai = new OpenAIAssistant();
        $messages_array = $conversation->messages()->orderBy('created_at')->get();

        foreach ($pipeline->stages()->orderBy('order')->get() as $stage) {
            echo "******************* NEW STAGE: {$stage->id} {$stage->name} {$stage->type} {$stage->successTool->name} \n";

            foreach ($stage->assistants()->orderBy('order')->get() as $assistant) {

                $interactive = ($assistant->interactive == 1) ? 'Interactive' : 'Non-Interactive';

                echo "------------- Assistant ID:{$assistant->id} {$interactive}: success_tool: {$assistant->successTool->name} {$assistant->name} {$assistant->type} {$assistant->successTool->name} \n";

                switch ($assistant->type) {
                    case 'context':

                        $prompt = $conversation->getPrompt();

                        // Set up prompt with the intention to summarize
                        $prompt = "You are a type of assistant that does not answer the prompt, but gathers information for answering the prompt. Here is the prompt: <prompt>{$prompt->content}</prompt>\n";
                        if($conversation && $conversation->getConversationMessages()){

                            $prompt .= "Here is the conversation so far if that helps:\n";
                            $prompt .= print_r($conversation->getConversationMessages(), true);

                        }

                        echo "Its a context Assistant. Here is the prompt we are giving it: \n&&&&&&&&&&&\n $prompt";
                        echo "\n&&&&&&&&&&&\n";

                        $assistant_id = $assistant->createOpenAiAssistant();
                        dump("Assistant ID: {$assistant_id}");

                        $thread_id = $openai->create_thread();
                        dump("Thread ID: {$thread_id}");

                        $openai->add_message($thread_id, $prompt, 'user');

                        // Create a run with the assistant
                        $run_id = $openai->create_run($thread_id, $assistant_id);
                        dump("Run ID: {$run_id}");

                        // Poll for completion
                        $retryDelay = 3;
                        $maxRetries = 3;
                        $retryCount = 0;

                        do {

                            sleep($retryDelay);
                            try {
                                $run = $openai->get_run($thread_id, $run_id);
                            } catch (\Exception $e) {
                                \Log::error("Error retrieving run (attempt $retryCount): " . $e->getMessage());
                                if (++$retryCount > $maxRetries) {
                                    throw $e;
                                }
                                continue;
                            }

                            if ($run['status'] == 'requires_action') {
                                // Handle tool execution if required
                                $toolExecutor = new ToolExecutor(); // Implement this class

                                $calls = $run['required_action']['submit_tool_outputs']['tool_calls'];
                                $outputs = [];
                                $log_entry = '';

                                $success_called = false;
                                $stage_success_called = false;

                                dump($calls);

                                foreach ($calls as $call) {

                                    echo "Call\n";

                                    var_dump($call);

                                    $method_name = $call['function']['name'];
                                    $method_args = json_decode($call['function']['arguments'], true);

                                    if($method_name == 'stage_complete'){




                                        if($method_name == $stage->successTool->name){

                                            echo "%%%%%%% Stage Success Tool Called, Last Loop  %%%%%%\n\n";

                                            $stage_success_called = true;
                                            $outputs[] = [
                                                'tool_call_id' => $call['id'],
                                                'output' => json_encode(['message'=>'stage complete'])
                                            ];


                                        }

                                        continue;

                                    }
                                    if($method_name == 'submit_context'){

                                      $content = $method_args['content'] ?? '';

                                        $outputs[] = [
                                            'tool_call_id' => $call['id'],
                                            'output' => json_encode(['content'=>$content])
                                        ];

                                        //var_dump($method_args);

                                        $conversation->addMessage('context',$content);

                                        if($method_name == $assistant->successTool->name){

                                            echo "%%%%%%% Success Tool Called, Last Loop  %%%%%%\n\n";
                                            $success_called = true;
                                        }
                                        continue;

                                    }

                                    if($method_name == 'add_context_message'){


                                        $outputs[] = [
                                            'tool_call_id' => $call['id'],
                                            'output' => json_encode(['context',$method_args['context']])
                                        ];


                                     //   var_dump($method_args);

                                        $conversation->addMessage('context',$method_args['context']);

                                        if($method_name == $assistant->successTool->name){

                                            echo "%%%%%%% Success Tool Called, Last Loop  %%%%%%\n\n";

                                            $success_called = true;



                                        }

                                        continue;

                                    }



                                    $callable = $toolExecutor ?
                                        [$toolExecutor, $method_name] : $method_name;

                                    \Log::info("OpenAIAssistant $method_name " . class_basename($toolExecutor) . json_encode($call). print_r($method_args,true));
                                    echo "OpenAIAssistant $method_name " . class_basename($toolExecutor) . json_encode($call). print_r($method_args,true);

                                    if (is_callable($callable)) {
                                        $data = call_user_func($callable, $method_args);

                                        $conversation->addMessage('tool',print_r([ $method_name => [
                                            'tool_call_id' => $call['id'],
                                            'output' => json_encode($data)
                                        ]],true));


                                        $outputs[] = [
                                            'tool_call_id' => $call['id'],
                                            'output' => json_encode($data)
                                        ];

                                        $log_entry .= "$method_name -> " . print_r($method_args, true);

                                    } else {
                                        throw new \Exception("Failed to execute tool: The $method_name you provided is not callable");
                                    }

                                    if($method_name == $assistant->successTool->name){

                                         echo "%%%%%%% Success Tool Called, Last Loop  %%%%%%\n\n";

                                        $success_called = true;



                                    }
                                    if($method_name == $stage->successTool->name){

                                        echo "%%%%%%% Stage Success Tool Called, Last Loop  %%%%%%\n\n";

                                        $stage_success_called = true;



                                    }

                                }

                                if($success_called){
                                    echo "\n\n%%%%%%%%%%%%% BREAKING OUT OF RUN - {$assistant->successTool->name} %%%%%%%%%%%%\n";
                                    echo "%%%%%%%%%%%%% KILLING THE ASSISTANT - {$assistant->name} %%%%%%%%%%%%\n\n";

                                    $openai->delete_assistant($assistant_id);
                                    break 2;
                                }

                                if($stage_success_called){
                                    echo "\n\n%%%%%%%%%%%%% BREAKING OUT OF STAGE - {$stage->successTool->name} %%%%%%%%%%%%\n";
                                    echo "%%%%%%%%%%%%% KILLING THE ASSISTANT - {$assistant->name} %%%%%%%%%%%%\n\n";

                                    $openai->delete_assistant($assistant_id);
                                    break 3;
                                }

                                //$outputs = $openai->execute_tools($thread_id, $run_id, $toolExecutor);
                                dump($outputs);
                                $response = $openai->submitToolOutputs($thread_id, $run_id, $outputs);



                            }

                        } while ($run['status'] != 'completed' && $run['status'] != 'failed');


                        $openai->delete_assistant($assistant_id);

                        $messages = $openai->list_thread_messages($thread_id);

                        foreach ($messages as $msg) {
                            if (in_array($msg['role'], ['assistant', 'user'])) {
                                if ($msg['content'][0]['type'] == 'text') {
                                    $message_content = $msg['content'][0]['text']['value'];
                                    $role = $msg['role'];
                                   echo " $role, $message_content\n";
                                }
                            }
                        }

                        break;

                    case 'transform':
                        // Indicate the prompt should lead to a tool execution
                       // $assistant_prompt = "{$processed_prompt} - Execute tool action.";
                        break;

                    case 'assistant':

                        echo "We got an assistant. Lets just work the whole conversation.\n";

                        $assistant_id = $assistant->createOpenAiAssistant();


                        dump("Assistant ID: {$assistant_id}");

                        $thread_id = $openai->create_thread();
                        dump("Thread ID: {$thread_id}");


                        $messages_array = $conversation->getConversationMessages();

                        foreach ($messages_array as $message) {
                            if ($message['role'] == 'user' || $message['role'] == 'assistant') {
                                $openai->add_message($thread_id, $message->content, $message->role);
                            }
                        }
                        $openai->add_message($thread_id, $prompt, 'user');

                        $prompt = $conversation->getPrompt();
                        $openai->add_message($thread_id, $prompt, 'user');
                        $run_id = $openai->create_run($thread_id, $assistant_id);
                        dump("Run ID: {$run_id}");

                        // Poll for completion
                        $retryDelay = 3;
                        $maxRetries = 3;
                        $retryCount = 0;

                        do {

                            sleep($retryDelay);
                            try {
                                $run = $openai->get_run($thread_id, $run_id);
                            } catch (\Exception $e) {
                                \Log::error("Error retrieving run (attempt $retryCount): " . $e->getMessage());
                                if (++$retryCount > $maxRetries) {
                                    throw $e;
                                }
                                continue;
                            }

                            if ($run['status'] == 'requires_action') {
                                // Handle tool execution if required
                                $toolExecutor = new ToolExecutor(); // Implement this class

                                $calls = $run['required_action']['submit_tool_outputs']['tool_calls'];
                                $outputs = [];
                                $log_entry = '';

                                $success_called = false;

                                dump($calls);

                                foreach ($calls as $call) {

                                    echo "Call\n";

                                    var_dump($call);

                                    $method_name = $call['function']['name'];
                                    $method_args = json_decode($call['function']['arguments'], true);

                                    if($method_name == 'submit_context'){

                                        $content = $method_args['content'] ?? '';

                                        $outputs[] = [
                                            'tool_call_id' => $call['id'],
                                            'output' => json_encode(['content'=>$content])
                                        ];

                                        //var_dump($method_args);

                                        $conversation->addMessage('context',$content);

                                        if($method_name == $assistant->successTool->name){

                                            echo "%%%%%%% Success Tool Called, Last Loop  %%%%%%\n\n";

                                            $success_called = true;



                                        }



                                        continue;

                                    }

                                    if($method_name == 'add_context_message'){


                                        $outputs[] = [
                                            'tool_call_id' => $call['id'],
                                            'output' => json_encode(['context',$method_args['context']])
                                        ];


                                           var_dump($method_args);

                                           exit;

                                        $conversation->addMessage('context',$method_args['context']);

                                        if($method_name == $assistant->successTool->name){

                                            echo "%%%%%%% Success Tool Called, Last Loop  %%%%%%\n\n";

                                            $success_called = true;



                                        }

                                        continue;

                                    }



                                    $callable = $toolExecutor ?
                                        [$toolExecutor, $method_name] : $method_name;

                                    \Log::info("OpenAIAssistant $method_name " . class_basename($toolExecutor) . json_encode($call). print_r($method_args,true));
                                    echo "OpenAIAssistant $method_name " . class_basename($toolExecutor) . json_encode($call). print_r($method_args,true);

                                    if (is_callable($callable)) {
                                        $data = call_user_func($callable, $method_args);
                                        $outputs[] = [
                                            'tool_call_id' => $call['id'],
                                            'output' => json_encode($data)
                                        ];
                                        $log_entry .= "$method_name -> " . print_r($method_args, true);
                                    } else {
                                        throw new \Exception("Failed to execute tool: The $method_name you provided is not callable");
                                    }

                                    if($method_name == $assistant->successTool->name){

                                        echo "%%%%%%% Success Tool Called, Last Loop  %%%%%%\n\n";

                                        $success_called = true;



                                    }

                                }

                                if($success_called){
                                    echo "\n\n%%%%%%%%%%%%% BREAKING OUT OF RUN - {$assistant->successTool->name} %%%%%%%%%%%%\n";
                                    echo "%%%%%%%%%%%%% KILLING THE ASSISTANT - {$assistant->name} %%%%%%%%%%%%\n\n";

                                    $openai->delete_assistant($assistant_id);
                                    break 2;
                                }

                                //$outputs = $openai->execute_tools($thread_id, $run_id, $toolExecutor);
                                dump($outputs);
                                $response = $openai->submitToolOutputs($thread_id, $run_id, $outputs);



                            }

                        } while ($run['status'] != 'completed' && $run['status'] != 'failed');


                        $openai->delete_assistant($assistant_id);

                        $messages = $openai->list_thread_messages($thread_id);

                        foreach ($messages as $msg) {
                            if (in_array($msg['role'], ['assistant', 'user'])) {
                                if ($msg['content'][0]['type'] == 'text') {
                                    $message_content = $msg['content'][0]['text']['value'];
                                    $role = $msg['role'];
                                    echo " $role, $message_content\n";
                                }
                            }
                        }

                        // Basic message handling with no extra processing
                        $assistant_prompt = $prompt;
                        break;

                    default:
                        $assistant_prompt = $prompt;
                        $conversation->addMessage('system', "Unknown assistant type '{$assistant->type}'.");
                }

















            }


            dump($conversation->getConversationMessages());


            echo "## Stage Assistants Done ##\n";




        }


        echo "+++++++++++++ Final Message Array: ++++++++++++++++\n";
        // Output the final conversation messages
        $messages_array = $conversation->messages()->orderBy('created_at')->get();
        dd($messages_array);
    }

    protected function verifyTools()
    {

        // Check if the corresponding method exists in ToolExecutor
        $executor = new ToolExecutor();

        $te_method_names = array();

        foreach (Tool::all() as $tool){

            $te_method_names[] = $tool->name;

            $methodExists = method_exists($executor, $tool->name);

                if (!$methodExists) {
                    $this->error("The '{$tool->name}' method does not exist in the ToolExecutor class.");
                }

        }


        // Step 2: Get all public methods from ToolExecutor
        $reflection = new \ReflectionClass(ToolExecutor::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        // Step 3: Filter methods declared in ToolExecutor only
        $methodNames = [];
        foreach ($methods as $method) {
            if ($method->class === ToolExecutor::class) {
                $methodNames[] = $method->getName();
            }
        }

        // Step 4: Compare tool names with method names
        $missingMethods = array_diff($te_method_names, $methodNames);
        $extraMethods = array_diff($methodNames, $te_method_names);

        // Step 5: Output the results
        if (!empty($missingMethods)) {
            echo "The following tools do not have corresponding methods in ToolExecutor:\n";
            echo implode(", ", $missingMethods) . "\n";
        } else {
            echo "All tools have corresponding methods in ToolExecutor.\n";
        }

        if (!empty($extraMethods)) {
            echo "The following methods in ToolExecutor do not have corresponding tools in the database:\n";
            echo implode(", ", $extraMethods) . "\n";
        } else {
            echo "All methods in ToolExecutor have corresponding tools in the database.\n";
        }



    }

    protected function listAllTools()
    {
        $tools = Tool::all();
        foreach ($tools as $tool) {
            echo "Tool ID: {$tool->id}, Name: {$tool->name}, Description: {$tool->description}\n";
        }
    }

    protected function listPipelines()
    {
        $pipelines = Pipeline::all();
        foreach ($pipelines as $pipeline) {
            echo "Pipeline ID: {$pipeline->id}, Name: {$pipeline->name}, Description: {$pipeline->description}\n";
        }
    }
    protected function listAssistants()
    {
        $assistants = Assistant::all();
        foreach ($assistants as $assistant) {
            echo "Assistant ID: {$assistant->id}, Name: {$assistant->name}, Type: {$assistant->type}, Interactive: " . ($assistant->interactive ? 'Yes' : 'No') . "\n";
        }
    }

}
