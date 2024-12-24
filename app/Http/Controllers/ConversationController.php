<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Models\Assistant;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Services\OpenAIAssistant;
use App\Services\ToolExecutor;
use Illuminate\Http\Request;
use App\Services\ConversationManager;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ConversationController extends Controller
{
    protected ConversationManager $conversationManager;

    /**
     * Inject the ConversationManager service.
     *
     * @param ConversationManager $conversationManager
     */
    public function __construct(ConversationManager $conversationManager)
    {
        $this->conversationManager = $conversationManager;
    }




    // Fetch a specific assistant by ID
    public function show($id)
    {
        $conversation = Conversation::with(['assistant', 'pipeline', 'model'])
            ->findOrFail($id);


        $model = AiModel::find($conversation->model_id);

        return response()->json([
            'id' => $conversation->id,
            'title' => $conversation->title,
            'type' => $conversation->type,
            'status' => $conversation->status,
            'model' => $model ? "{$model->type}: {$model->name}" : null,

            'assistant' => $conversation->assistant ? $conversation->assistant->name : null,
            'pipeline' => $conversation->pipeline ? $conversation->pipeline->name : null,
            'active_tools' => $conversation->active_tools ?? [],
            'system_message' => $conversation->system_messages,
            'created_at' => $conversation->created_at,
            'updated_at' => $conversation->updated_at,
        ]);
    }





    /**
     * Create a new conversation.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|max:32',
            'model_id' => 'required|integer|exists:models,id',
            'assistant_id' => 'nullable|integer|exists:assistants,id',
            'pipeline_id' => 'nullable|integer|exists:pipelines,id',
            'active_tools' => 'nullable|array',
            'active_tools.*' => 'string',
            'system_message' => 'nullable|string',
        ]);

        // Create conversation
        $conversation = new Conversation();
        $conversation->id = (string) \Illuminate\Support\Str::uuid();
        $conversation->user_id = $request->user()->id;
        $conversation->title = $validated['title'];
        $conversation->type = $validated['type'];
        $conversation->model_id = $validated['model_id'];
        $conversation->assistant_id = $validated['assistant_id'] ?? null;
        $conversation->pipeline_id = $validated['pipeline_id'] ?? null;
        $conversation->active_tools = $validated['active_tools'] ?? null;
        $conversation->system_message = $validated['system_message'] ?? null;
        $conversation->save();


        if($conversation->system_message){

            $conversation->addMessage('system',$conversation->system_message);

        }

        if($conversation->assistant){

            $conversation->addMessage('system',$conversation->assistant->system_message);

        }

        $messages = $conversation->messages()->orderBy('created_at')->get();

        $conversation->messages = $messages;

        return response()->json([
            'status' => 'success',
            'conversation' => $conversation,
            'conversation_id' => $conversation->id,
        ]);
    }



    public function createPipelineConversation(Request $request, Pipeline $pipeline)
    {


        try {

            $stage = Stage::where('pipeline_id',$pipeline->id)->orderBy('order')->first();


            $conversation = Conversation::create([
                'title' => $pipeline->name,
                'pipeline_id'=>$pipeline->id,
                'stage_id' => $stage->id,
                //'system_message' => $assistant->systemMessage,
            ]);

            return response()->json([
                'success' => true,
                'conversation_id' => $conversation->id,
                'conversation' => $conversation,
                'message' => 'Conversation created successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create conversation: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function createAssistantConversation(Request $request, Assistant $assistant)
    {


        try {

            $conversation = Conversation::create([
                'title' => $assistant->name,
                'assistant_id'=>$assistant->id,
                //'stage_id' => $stage->id,
                'system_message' => $assistant->system_message,
            ]);

            return response()->json([
                'success' => true,
                'conversation_id' => $conversation->id,
                'conversation' => $conversation,
                'message' => 'Conversation created successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create conversation: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function createConversation(Request $request)
    {
        $assistantType = $request->input('assistant_type');

        $title = $request->input('title', 'New Conversation');

        $assistant = Assistant::where('name',$assistantType)->first();

        // Define default tools and system messages based on assistant type
        $assistantId = $request->input('assistant_id');

        if(!$assistant && $assistantId){

            $assistant = Assistant::find($assistantId);

        }



        try {


            $conversation = Conversation::create([
                'title' => $title,
                'assistant_type' => $assistantType,
                'active_tools' => $assistant->tools()->pluck('name')->toArray(),
                'system_message' => $assistant->systemMessage,
            ]);

            // Optionally, add a system message for the assistant's introduction
            $this->conversationManager->addMessage($conversation->id, 'system', "Assistant of type '{$assistantType}' initialized.");

            return response()->json([
                'success' => true,
                'conversation_id' => $conversation->id,
                'message' => 'Conversation created successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create conversation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a message within a conversation.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request,$id = null)
    {

        $conversationId = ($id) ? $id : $request->input('conversation_id');



        //$conversationId = $request->input('conversation_id');
        $userMessage = $request->input('message');

        if (!$conversationId || !$userMessage) {
            return response()->json([
                'success' => false,
                'error' => 'Missing required parameters: conversation_id, message',
            ], 400);
        }

        try {

            $conversation = Conversation::find($conversationId);

            $conversation->addMessage('user', $userMessage);

            $messages = $conversation->messages()->orderBy('created_at')->get();

            if($conversation->pipeline){

                if(!$conversation->stage){

                    $stage = Stage::where('pipeline_id',$conversation->pipeline->id)->orderBy('order')->first();
                    $conversation->stage_id = $stage->id;
                    $conversation->save();

                } else {

                    $stage = $conversation->stage;

                }




                if($stage->assistants()->count() == 0){

                    return response()->json([
                        'success' => true,
                        'status' => 'fail',
                        'message' => 'Failed to find assistant.',
                    ]);

                }

                $assistant =  $stage->assistants()->first();
                $conversation->assistant_id = $assistant->id;
                $conversation->save();

                Log::info("########### Stage: $stage->name");

            } else if($conversation->assistant) {

                $assistant = $conversation->assistant;


            }

                $openai = new OpenAIAssistant();

                $system_messages_array = $conversation->messages()->where('role' ,'system')->orderBy('created_at')->pluck('content')->toArray();

                $system_message = implode("\n\n",$system_messages_array);

                $thread_id = $openai->create_thread($system_message,'user');

                $messages_array = $conversation->messages()->where('role','!=' ,'system')->orderBy('created_at')->get();

                if($conversation->stage){

                    if($conversation->stage->files){



                        foreach ($conversation->stage->files as $file){


                            if (!Storage::exists($file['file_path'])) {
                                return response()->json(['error' => 'File not found'], 404);
                            }

                            $fileContents = Storage::get($file['file_path']);
                            $mimeType = Storage::mimeType($file['file_path']);

                            $content = "Here is a file for additional context or information, use it to answer any questions.\n{$file['file_path']}:\n$fileContents";

                            Log::info($content);


                            $openai->add_message($thread_id, $content, 'user');

                        }
                       // dd($conversation->stage->files);

                    }

                }

                foreach ($messages_array as $message){

                    $openai->add_message($thread_id, $message->content, $message->role);

                }

                $assistant_id  = $conversation->assistant->createOpenAiAssistant($system_message);

                if(!is_string($assistant_id)){

                    Log::info(json_encode($assistant_id,true));

                }

                $run_id = $openai->create_run($thread_id, $assistant_id);

                $retryDelay = 2;
                $retryCount = 2;
                $maxRetries = 2;

                do {

                    sleep($retryDelay);

                    try {
                        $run = $openai->get_run($thread_id, $run_id);
                    } catch (\Exception $e) {
                        Log::error("Error retrieving run (attempt $retryCount): " . $e->getMessage());
                        if (++$retryCount > $maxRetries) {
                            throw $e;
                        }
                        continue;
                    }

                    if ($run['status'] == 'requires_action') {

                        $optional_object = new ToolExecutor();
                        $outputs = [];

                        $calls = $run['required_action']['submit_tool_outputs']['tool_calls'];
                        foreach ($calls as $call) {

                            $method_name = $call['function']['name'];

                            if ($method_name == 'stage_complete') {


                                if ($assistant->successTool && $method_name == $stage->successTool->name) {

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

                                if ($assistant->successTool && $method_name == $assistant->successTool->name) {

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
                                if ($assistant->successTool && $method_name == $assistant->successTool->name) {
                                    echo "%%%%%%% Success Tool Called, Last Loop  %%%%%%\n\n";
                                    $success_called = true;
                                }

                                continue;

                            }


                            $method_args = json_decode($call['function']['arguments'], true);
                            $callable = $optional_object ?
                                [$optional_object, $method_name] : $method_name;

                            Log::info("OpenAIAssistant $method_name " . class_basename($optional_object) . json_encode($call) . print_r($method_args, true));

                            if (is_callable($callable)) {

                                $data = call_user_func($callable, $method_args);

                                $conversation->addMessage('assistant', json_encode($data));

                                $outputs[] = [
                                    'tool_call_id' => $call['id'],
                                    'output' => json_encode($data)
                                ];

                               Log::info("$method_name -> " . print_r($method_args, true));
                               Log::info("$method_name Outputs -> " . print_r($outputs, true));


                                if ($assistant->successTool && $method_name == $assistant->successTool->name && $data['status'] == 'success') {
                                    Log::info("%%%%%%% Success Tool Called, Last Loop  %%%%%%\n\n");

                                    $assistant_success_called = true;


                                }
                                if ($conversation->stage && $conversation->stage->successTool && $method_name == $conversation->stage->successTool->name && $data['status'] == 'success') {

                                    Log::info("%%%%%%% Stage Success Tool Called, Last Loop  %%%%%%\n\n");

                                    $stage_success_called = true;
                                    $stage = $conversation->getNextStage();
                                    $conversation->stage_id = $stage->id;
                                    $conversation->save();

                                }
                                Log::info("endof calls loop");
                            } else {
                                throw new \Exception("Failed to execute tool: The $method_name you provided is not callable");
                            }

                            Log::info("endof calls loop");

                        }

                        Log::info("endof calls");

                        $openai->submit_tool_outputs($thread_id, $run_id, $outputs);

                        Log::info(json_encode($outputs));

                    }

                } while ($run['status'] != 'completed' && $run['status']  != 'failed');

                $messages = $openai->list_thread_messages($thread_id);

                Log::info(json_encode($messages,true));

                if($messages[0]['content'][0]['type'] == 'text'){

                    $message = $messages[0]['content'][0]['text']['value'];
                    $role = $messages[0]['role'];
                    $conversation->addMessage($role, $message);

                }

                $openai->delete_assistant($assistant_id);

                $messages_array = $conversation->messages()->orderBy('created_at')->get();

              //  dd($messages_array);

            return response()->json([
                'success' => true,
                'status' => 'success',
                'conversation' => $conversation,
                'messages' => $messages_array,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retrieve all messages within a conversation.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessages(Request $request, $id = null)
    {


        $conversationId = ($id) ? $id : $request->input('conversation_id');

        if (!$conversationId) {
            return response()->json([
                'success' => false,
                'error' => 'Missing required parameter: conversation_id',
            ], 400);
        }

        try {
            $conversation = Conversation::find($conversationId);

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'error' => 'Conversation not found',
                ], 404);
            }

            $messages = $conversation->messages()->orderBy('created_at')->get();

            return response()->json([
                'success' => true,
                'messages' => $messages,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve messages: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Switch the assistant type for an ongoing conversation.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function switchAssistant(Request $request)
    {
        $conversationId = $request->input('conversation_id');
        $assistantType = $request->input('assistant_type');

        if (!$conversationId || !$assistantType) {
            return response()->json([
                'success' => false,
                'error' => 'Missing required parameters: conversation_id, assistant_type',
            ], 400);
        }

        try {
            $conversation = Conversation::find($conversationId);

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'error' => 'Conversation not found',
                ], 404);
            }

            // Update tools and system messages based on the new assistant type
            $tools = $this->getToolsForAssistant($assistantType);
            $systemMessages = $this->getSystemMessagesForAssistant($assistantType);

            $conversation->update([
                'assistant_type' => $assistantType,
                'active_tools' => $tools,
                'system_message' => $systemMessages,
            ]);

            // Add a system message indicating the switch
            $this->conversationManager->addMessage($conversationId, 'system', "Assistant switched to: {$assistantType}");

            return response()->json([
                'success' => true,
                'message' => "Assistant switched to: {$assistantType}",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to switch assistant: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper method to get tools based on assistant type.
     *
     * @param string $assistantType
     * @return array
     */
    protected function getToolsForAssistant($assistantType)
    {
        switch ($assistantType) {
            case 'task_manager':
                return ['create_task', 'update_task', 'delete_task', 'list_tasks'];
            case 'project_manager':
                return ['create_project', 'update_project', 'delete_project', 'list_projects'];
            default:
                return []; // Default assistant has no special tools
        }
    }

    /**
     * Helper method to get system messages based on assistant type.
     *
     * @param string $assistantType
     * @return array
     */
    protected function getSystemMessagesForAssistant($assistantType)
    {
        switch ($assistantType) {
            case 'task_manager':
                return ['You are a task management assistant. You help users manage tasks.'];
            case 'project_manager':
                return ['You are a project management assistant. You help users manage projects.'];
            default:
                return ['You are an AI assistant ready to help.'];
        }
    }

    /**
     * Display a listing of conversations.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Conversation::with(['assistant', 'pipeline', 'messages' => function($q) {
                $q->orderBy('created_at', 'desc')->limit(1);
            }]);

            // Add filters if provided
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }
            
            if ($request->has('assistant_type')) {
                $query->where('assistant_type', $request->assistant_type);
            }

            $conversations = $query->orderBy('updated_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'conversations' => $conversations->map(function($conversation) {
                    return [
                        'id' => $conversation->id,
                        'title' => $conversation->title,
                        'type' => $conversation->type,
                        'assistant_name' => $conversation->assistant ? $conversation->assistant->name : null,
                        'pipeline_name' => $conversation->pipeline ? $conversation->pipeline->name : null,
                        'last_message' => $conversation->messages->first() ? [
                            'content' => $conversation->messages->first()->content,
                            'role' => $conversation->messages->first()->role,
                            'created_at' => $conversation->messages->first()->created_at
                        ] : null,
                        'created_at' => $conversation->created_at,
                        'updated_at' => $conversation->updated_at,
                        'status' => $conversation->status
                    ];
                }),
                'pagination' => [
                    'current_page' => $conversations->currentPage(),
                    'last_page' => $conversations->lastPage(),
                    'per_page' => $conversations->perPage(),
                    'total' => $conversations->total()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve conversations: ' . $e->getMessage()
            ], 500);
        }
    }
}
