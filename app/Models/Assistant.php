<?php

namespace App\Models;

use App\Services\OpenAIAssistant;
use App\Services\ToolExecutor;
use ArdaGnsrn\Ollama\Ollama;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Assistant extends Model
{
  use HasFactory;

  public $prompt = '';
    protected $casts = [
        'interactive' => 'boolean',
    ];

    protected $fillable = ['user_id','name', 'system_message', 'model_id', 'success_tool_id', 'type', 'interactive', 'created_at', 'updated_at','times_used',
        'last_used',];



    public function stages()
    {
        return $this->belongsToMany(Stage::class, 'stage_assistants')
            ->withPivot('order', 'success_stage_id', 'success_tool_id')
            ->with('successTool', 'successStage') // Eager load related models
            ->withTimestamps();
    }

    // Relationship with User (the creator of the assistant)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with Tools (many-to-many relationship)
    public function tools()
    {
        return $this->belongsToMany(Tool::class, 'assistant_tool');
    }
    public function successTool()
    {
        return $this->belongsTo(Tool::class, 'success_tool_id');
    }
    public function aiModel()
    {
        return $this->belongsTo(AiModel::class, 'model_id');
    }

    // Relationship with Model (AIModel)
    public function model()
    {
        return $this->belongsTo(AiModel::class, 'model_id');
    }




    public function toolJson() {




        return json_encode($this->generateTools());
    }

    public function getRealtimeAssistantTools(){
        return $this->tools->map(function($tool) {
            // Create a simpler, flattened parameter structure
            $parameters = [
                'type' => 'object',
                'properties' => [],
                'required' => []
            ];

            if ($tool->relationLoaded('toolParameters') || $tool->toolParameters) {
                foreach ($tool->toolParameters as $param) {
                    // Ensure description is never null
                    $description = $param->description;
                    if (empty($description)) {
                        // Generate a default description based on the parameter name
                        $description = "The " . str_replace('_', ' ', $param->name) . " parameter";
                    }

                    $parameters['properties'][$param->name] = [
                        'type' => $param->type ?: 'string', // Default to string if type is null
                        'description' => $description
                    ];
                    
                    if ($param->required) {
                        $parameters['required'][] = $param->name;
                    }
                }
            }

            // Ensure tool description is never null
            $toolDescription = $tool->description;
            if (empty($toolDescription)) {
                $toolDescription = "Tool to " . str_replace('_', ' ', $tool->name);
            }

            // Create the tool structure - note the removal of the nested 'function' object
            return [
                'type' => 'function',
                'name' => $tool->name,
                'description' => $toolDescription,
                'parameters' => $parameters
            ];
        })->toArray();
    }

    public function getRealtimeAssistantSession()
    {
        $modelName = is_string($this->model) ? $this->model : 
            ($this->model ? $this->model->name : 'gpt-4');

        return [
            
            'instructions' => $this->system_message,
            //'model' => $modelName,          
            'tools' => $this->getRealtimeAssistantTools(),

        ];
    }














    public function generateTools()
    {

        // Transform the tools and parameters to the desired JSON structure
        $tools = $this->tools->map(function ($tool) {

            $return = [
                'type' => 'function',
                'function' => [
                    'name' => $tool->name,
                    'description' => $tool->description,
                    'strict'=>(bool) $tool->strict,

                ],

            ];
            if($tool->parameters()->count()){

                $return['function']['parameters'] = [
                    'type' => 'object',
                    'properties' => $tool->parameters()->get()->mapWithKeys(function ($param) {
                        return [
                            $param->name => [
                                'type' => $param->type,
                                'description' => $param->description ?? '',
                                //'enum' => $param->enum ?? [], // Assuming the enum is stored in the database, if applicable
                            ],
                        ];
                    })->toArray(),
                    "additionalProperties" => false,
                    'required' => $tool->parameters()->where('required', true)->pluck('name')->toArray(),
                ];

            }

            return $return;

        });

        return $tools;
    }









    public function askOllamaPrompt($prompt){

        $this->prompt = $prompt;

        $conversation = Conversation::create([
            'title' => "Assistant: $this->name",
            'assistant_type' => $this->name,
            'active_tools' => $this->tools()->pluck('name')->toArray(),
            'system_message' => $this->system_message,
            'model'=> $this->aiModel->id,
        ]);

        $conversation->addMessage('system', $this->system_message);

        $conversation->addMessage('user', $prompt);

        //dump($conversation->getConversationMessages());

        $client =Ollama::client('http://192.168.0.104:11434');

        $response = $client->chat()->create([
            //'model' => 'mistral-nemo',
            //'model' => 'nemotron-mini',
            'model' => $conversation->model,
            'messages' => $conversation->getConversationMessages(),
            'tools' => $conversation->assistant->generateTools()->toArray(),
        ]);

        if($response->message->content){
            //var_dump($response->message->content);
            $conversation->addMessage($response->message->role, $response->message->content);
        }

        while(count($response->message->toolCalls)){

            $toolExecutor = new ToolExecutor();

            //$conversation->addMessage('assistant', json_encode($response->message->toolCalls));
            echo "********************* Tool Calls!!!\n ***************************";

            $tool_response = array();

            foreach ($response->message->toolCalls as $toolCall){

              //  dump($toolCall);

                if(property_exists($toolCall, 'function')){

                    $name = $toolCall->function->name;
                    $arguments = $toolCall->function->arguments;

                    if(method_exists($toolExecutor,$name)){

                        $response = $toolExecutor->$name($arguments);
                        $tool_response[$name] = $response;
                        $conversation->addMessage('assistant', json_encode($tool_response));

                    }

                }

            }

          //  echo "Sending Tool Responses\n";
         //   dump($conversation->getConversationMessages());

            $response = $client->chat()->create([
                'model' => $conversation->model,
                'messages' => $conversation->getConversationMessages(),
                'tools' => $conversation->assistant->generateTools()->toArray(),
            ]);

            if($response->message->content){
              //  var_dump($response->message->content);
                $conversation->addMessage($response->message->role, $response->message->content);

            }

        }

        return $conversation->getConversationMessages();

    }




    function startOpenAiRun($prompt, $files = [],$pipelineId = null, $stageId = null){


        $storage = Storage::disk('richbot_sandbox');

       // dump('got here');

        $this->prompt = $prompt;

        $conversation = Conversation::create([
            'title' => "$this->name",
            'assistant_type' => $this->type ?? '',
            'assistant_id' => $this->id ?? '',
            'active_tools' => $this->tools()->pluck('name')->toArray(),
            'system_message' => $this->systemMessage,
            'pipeline_id'=>$pipelineId,
            'stage_id' => $stageId,
        ]);

    //    dump('got here');


        foreach ($files as $file){

     //       dump('got here');

            if(!isset($file['content'])) $file['content'] = false;

            if (!$file['content'] && !$storage->exists($file['path'])) {

             //   dump('file not found: '.$file['path']);

                return ['error' => 'File not found'];
            }

            $fileContents = $file['content'] ?? $storage->get($file['path']);
          //  $mimeType = $storage->mimeType($file['path']);

            if(isset($file['type']) && $file['type'] == 'context'){

                $content = "Here is a file for additional context or information, use it to answer any questions.\n{$file['path']}:\n$fileContents";

            } else {


                $content = "File:{$file['path']}:\n$fileContents";

            }

            Log::info($content ?? $fileContents);

           // $openai->add_message($thread_id, $content, 'user');
            $conversation->addMessage('system', $content ?? $fileContents);
        }
     //   dump('got here');

        $conversation->addMessage('user', $prompt);

        $messages = $conversation->messages()->orderBy('created_at')->get();

        $openai = new OpenAIAssistant();

        $system_messages_array = $conversation->messages()->where('role' ,'system')->orderBy('created_at')->pluck('content')->toArray();

        $system_message = implode("\n\n\n",$system_messages_array);

        $thread_id = $openai->create_thread($system_message,'user');
     //   dump('got here');

        $messages_array = $conversation->messages()->where([['role','!=' ,'system'],['role','!=' ,'tool']])->orderBy('created_at')->get();

        foreach ($messages_array as $message){

            $openai->add_message($thread_id, $message->content, $message->role);

        }

        $assistant_id  = $this->createOpenAiAssistant();

        $run_id = $openai->create_run($thread_id, $assistant_id);

        //$openai->delete_assistant($assistant_id);


      //  dump('got here');
        return ['thread_id'=>$thread_id,'assistant_id'=>$assistant_id,'run_id'=>$run_id,'conversation_id'=>$conversation->id,'conversation'=>$conversation];

    }



    function createOpenAiAssistant($system_message = ''){

        $openai = new OpenAIAssistant();

        $assistantName = $this->name;
        $instructions = $system_message . "\n". $this->system_message;

        $tools = $this->generateTools();

        Log::info($instructions);
        Log::info($tools);

        try {
            // Use OpenAIAssistant service to create the assistant
            $assistantId = $openai->create_assistant(
                $assistantName,
                $instructions,
                [],
                [],
                $tools,

            );

            //dd($assistantId);

            return $assistantId;

        } catch (\Exception $e) {
            Log::error("Error creating assistant: " . $e->getMessage());
            return ['error' => 'Failed to create assistant.' . $e->getMessage()];
        }

    }



}
