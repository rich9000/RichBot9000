<?php

namespace App\Http\Controllers;

use App\Models\OpenAIFunction;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Services\OpenAIAssistant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Services\ToolExecutor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RecursiveIteratorIterator;
use DirectoryIterator;
use RecursiveDirectoryIterator;

class ChatController extends Controller
{
    protected $openAIAssistant;

    public string $rules;
    public function __construct(OpenAIAssistant $openAIAssistant)
    {

        $this->openAIAssistant = $openAIAssistant;
        $this->rules = "
You are an expert project management assistant. Your role is to help users understand, utilize, and manage the project management system that you have access to.
If asked a specific task, such as creating a task, or assigning a user to a project, that does not cause large changes can be done without asking permission.
Respond to every user message by processing it and finding the solution.
The information you provide is important and needs to be accurate.
Ask the user for confirmation if your solutions involve deleting goals or projects or users.
You have access to files on project manager server. This is a web server where users of the system can see html that you can generate. You can generate html to display data and ideas when asked.
The folder on the server you can access may be refered to as the sandbox because its where you can play and create files.
You are an expert project management assistant. Your role is to help users utilize the project management system.
Entities in the database include Projects, Deadlines, Goals, Tasks, Issues, Notes, Plans, Teams, and Users. Typically, the hierarchy starts with the Project, which can have Deadlines, Goals, and Tasks assigned to it. Tasks can be assigned to Projects, Goals, or Deadlines. Any entity can be assigned to another entity as needed.

Example Potential Layout.

Project
    User1
    User2
    Team1
    Team2
    Task1
        User1
    Task2
        Issue1
            User1
    Goal1
        Task3
            Note2
                task4
        Task2
        Goal3
    Issue2
        team2
    Deadline
        Issue1
        Task2
            Team2
        Goal3

";


    }
    public function dashboard()
    {

    }

    public function easyMode(Request $request)
    {
        $prompt = $request->input('prompt');
        $assistant_id = 'asst_kIgtLGI33HkIjbb6x1qEw4b5';
        $instructions = 'This is a one shot task, you need to complete everything in this run, because there is only one run.';

        // Call OpenAI API to create a thread with the prompt
        $response = $this->openAIAssistant->runFullThread($prompt,$assistant_id,$instructions);

        return response()->json($response);
    }

    public function easyModeUpdate($id, Request $request)
    {
        $executionId = $request->input('execution_id');

        // Check the status of the thread run
        //$run = $this->openAIAssistant->get_run($id, $executionId);
$run = false;

        if ($run['status'] == 'completed') {
            $messages = $this->openAIAssistant->list_thread_messages($id);
            $resultMessage = end($messages)['content'];

            return response()->json(['status' => 'completed', 'message' => $resultMessage]);
        }

        return response()->json(['status' => 'pending']);
    }
    public function filesDashboard()
    {
        $directory_tree = $this->getFileStructure('/var/www/html/projman');

        $gpt = new OpenAIAssistant();
        $onlineFiles = $gpt->list_files();

        return view('chat.dashboard', [
            'directoryTree' => $directory_tree,
            'onlineFiles' => $onlineFiles,
        ]);

    }



    public function assistantsDashboard()
    {


        $gpt = new OpenAIAssistant();

        $assistants = $gpt->list_assistants();

        $onlineFiles = $gpt->list_files();
        return view('chat.assistants', [

            'assistants' => $assistants,
            'onlineFiles' => $onlineFiles
        ]);

    }


        public function index(Request $request)
    {

        $sessionId = Session::get('session_id','default');
        $threadId = Session::get('thread_id_' . $sessionId, false);
        $gpt = new OpenAIAssistant();

        if (!$threadId) {
            $threadId = $this->openAIAssistant->create_thread($this->rules);
            Session::put('thread_id_' . $sessionId, $threadId);
        }

        $runs = $this->openAIAssistant->list_runs($threadId);

        $threadMessages = $gpt->list_thread_messages($threadId);

        $messages = [];

        foreach ($threadMessages as $threadMessage) {

            $assistantId = $threadMessage['assistant_id'];
            $runId = $threadMessage['run_id'];
            $threadId = $threadMessage['thread_id'];
            $createdAt = $threadMessage['created_at'];
            //dump($threadMessage);

            $role = $threadMessage['role'];
            foreach($threadMessage['content'] as $content){

                if($content['type'] == 'text'){

                    $message = [];
                    $message['role'] =$role;
                    $message['text'] = $content['text']['value'];
                    $message['assistant_id'] = $assistantId ?? false;
                    $message['run_id'] = $runId;
                    $message['thread_id'] = $threadId;
                    $message['created_at'] = $createdAt;
                    $messages[] = $message;

                }


            }


        }


        $assistants = $gpt->list_assistants();

/*
  $onlineFiles = $gpt->list_files();

        $directory_tree = $this->getFileStructure('/var/www/html/projman');

        $tables = DB::select('SHOW TABLES');


        $modelPath = base_path('app/Models');
        $controllerPath = base_path('app/Http/Controllers');
        $viewPath = base_path('resources/views');
        $routePath = base_path('routes');
        $migrationPath = base_path('database/migrations');

        $modelFiles = File::allFiles($modelPath);
        $controllerFiles = File::allFiles($controllerPath);
        $viewFiles = File::allFiles($viewPath);
        $routeFiles = File::allFiles($routePath);
        $migrationFiles = File::allFiles($migrationPath);
*/
        //$modelFiles = $this->listFilesInDir('path/to/Models');
        //$controllerFiles = $this->listFilesInDir('path/to/Controllers');
        //$viewFiles = $this->listFilesInDir('path/to/Views');

        return view('chat.dashboard', [

            'messages' => $messages,
            'runs'=>$runs,
            'sessionId' => $sessionId,
            'assistants' => $assistants,

        ]);

    }
    public function sendMessage(Request $request)
    {

        $sessionId = Session::get('session_id','default');
        $threadId = Session::get('thread_id_' . $sessionId, false);

        Log::info($request);

        $question = $request->input('prompt');
        $assistant_id = $request->input('assistant');

        \Log::info("Send Message: $assistant_id to " . json_encode($question));

        $this->openAIAssistant->assistant_id = $assistant_id;


        try {

            if($this->openAIAssistant->add_message($threadId, $question)) {

                    $executionId = $this->openAIAssistant->run_thread($threadId);

                    $count = 0;
                    while($this->openAIAssistant->has_tool_calls){

                        $toolExecutor = new ToolExecutor(); // Implement this class
                        $outputs = $this->openAIAssistant->execute_tools($threadId, $executionId, $toolExecutor);
                        $this->openAIAssistant->submit_tool_outputs($threadId, $executionId, $outputs);

                        $this->openAIAssistant->run_thread($threadId);

                        if($count++ > 50) break;

                    }


            } else {


                Log::error("Error in sendMessage unable to add message ");
                return response()->json(['error' => 'unable to add message. '], 500);


            }

            sleep(1);

            $messages = $this->openAIAssistant->list_thread_messages($threadId);

           // Log::error($messages);

          //  Session::put('messages_' . $sessionId, $messages);

            $audioUrl = false;

            return response()->json(['messages' => $messages, 'audio' => $audioUrl]);

        } catch (\Exception $e) {
            Log::error("Error in sendMessage: " . $e->getMessage());
            return response()->json(['error' => 'Failed to process the request. ' . $e->getMessage()], 500);
        }
    }



    public function getUpdates()
    {
        $sessionId = Session::get('session_id', 'default');
        $threadId = Session::get('thread_id_' . $sessionId, false);

        try {

            $runs = $this->openAIAssistant->list_runs($threadId);

            $tool_output = [];

            foreach ($runs as $run) {

                $tool_output[$run['id']] = array();

                if($run['status']=="requires_action"){

                    $toolExecutor = new ToolExecutor;

                    //$this->openAIAssistant->execute_tools($threadId,$run['id'],);
                    $outputs = $this->openAIAssistant->execute_tools($threadId, $run['id'], $toolExecutor);
                    $this->openAIAssistant->submit_tool_outputs($threadId, $run['id'], $outputs);

                    $tool_output[$run['id']][] = $outputs;



                }

            }


            // Get all messages from the thread
            $messages = $this->openAIAssistant->list_thread_messages($threadId);

            return response()->json(['messages' => $messages,'runs'=>$runs,'tool_output'=>$tool_output]);
        } catch (\Exception $e) {
            Log::error("Error in getUpdates: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch updates. ' . $e->getMessage()], 500);
        }
    }







    public function sendMessageOld(Request $request)
    {
        $question = $request->input('question');
        $sessionId = $request->input('session_id', 'default');
        $voiceResponse = $request->input('voice_response', false);

        try {
            // Create or get existing thread ID
            $threadId = Session::get('thread_id_' . $sessionId, false);
            if (!$threadId) {
                $threadId = $this->openAIAssistant->create_thread("Start the conversation.");
                Session::put('thread_id_' . $sessionId, $threadId);
            }

            // Add message to the thread
            $this->openAIAssistant->add_message($threadId, $question);

            // Run the thread
            $executionId = $this->openAIAssistant->run_thread($threadId);

            // Handle tool calls if needed
            if ($this->openAIAssistant->has_tool_calls) {
                // Assuming you have a class `ToolExecutor` that handles the tool calls
                $toolExecutor = new ToolExecutor('rich'); // You need to implement this class
                $outputs = $this->openAIAssistant->execute_tools($threadId, $executionId, $toolExecutor);
                $this->openAIAssistant->submit_tool_outputs($threadId, $executionId, $outputs);

                // Re-run the thread after handling tool calls
                $executionId = $this->openAIAssistant->run_thread($threadId);
            }

            // Get all messages from the thread
            $messages = $this->openAIAssistant->list_thread_messages($threadId);
            $assistantMessages = array_filter($messages, fn($msg) => $msg['role'] === 'assistant');

            // Update session messages
            Session::put('messages_' . $sessionId, $messages);

            $audioUrl = false;
            // Get audio response if voiceResponse is true
            //$audioUrl = $voiceResponse ? $this->openAIAssistant->textToSpeech($assistantMessages[count($assistantMessages) - 1]['content']) : null;

            return response()->json(['messages' => $messages, 'audio' => $audioUrl]);

        } catch (\Exception $e) {
            Log::error("Error in sendMessage: " . $e->getMessage());
            return response()->json(['error' => 'Failed to process the request. ' . $e->getMessage()], 500);
        }
    }




    public function generateCode(Request $request)
    {

        $request->validate([
            'fileType' => 'nullable|array',
            'newModelName' => 'nullable|string',
            'prompt' => 'required|string',
            'selectedFiles' => 'nullable|array',
            'rules' => 'nullable|string',
        ]);

        $tableSchemas = array();

        if($request->has('selectedTables')){

            foreach ($request->input('selectedTables') as $table) {

                if (preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                    $schemaDetails = DB::select('DESCRIBE `' . $table . '`');

                    $tableSchemas[$table] = json_encode($schemaDetails, JSON_PRETTY_PRINT);
                } else {
                    // Handle invalid table name
                    return redirect()->back()->withErrors(['file_error' => "Table you selected is not formatted correctly: $table" ]);
                }
            }

        }



        $selectedFiles = $request->input('selectedFiles');
        $fileContents = [];

        // Validate the files
        foreach ($selectedFiles as $filePath) {
            if (File::exists($filePath) && File::isReadable($filePath)) {
                $fileContents[$filePath] = File::get($filePath);
            } else {
                // Return with an error message if the file doesn't exist or isn't readable
                return redirect()->back()->withErrors(['file_error' => "The file $filePath doesn't exist or isn't readable."]);
            }
        }

        // Step 1: System Introduction
        $conversationHistory = [
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant that creates code written in PHP for the Laravel framework. You always include full examples and you respond in JSON'
            ],

            [
                'role' => 'system',
                'content' => 'You are a helpful assistant that creates code written in PHP for the Laravel framework. You always include full examples and you respond in JSON.'
            ],


        ];

        // Step 2: Include User Provided Rules
        $rules = $request->input('rules');
        $conversationHistory[] = ['role' => 'user', 'content' => $rules];

        // Step 3: Include Selected File Contents
        $selectedFiles = $request->input('selectedFiles');
        foreach ($fileContents as $path => $fileContent) {
            $conversationHistory[] = ['role' => 'user', 'content' => "Here's the example file $path: \n$fileContent"];
        }


        foreach ($tableSchemas as $tableName => $schema) {
            $conversationHistory[] = ['role' => 'user', 'content' => "The schema for table $tableName is: " . $schema];
        }




        // Step 4: Include New Requirements
        $newModelName = $request->input('modelName');

        $fileTypes = implode(', ', $request->input('fileType'));
        $prompt = $request->input('prompt');

        $conversationHistory[] = ['role' => 'user', 'content' => "Generate a new $fileTypes for model $newModelName."];
        $conversationHistory[] = ['role' => 'user', 'content' => "$prompt"];
        $conversationHistory[] = ['role' => 'user', 'content' => "Format your response so its parsable by json_decode."];



        $client = OpenAI::client(env('OPENAI_API_KEY'));

        // Step 5: Make the API request
        $response = $client->chat()->create([
            //'model' => 'gpt-3.5-turbo',
            //'model' => 'gpt-4',
            'model' => 'gpt-4-1106-preview',
            'messages' => $conversationHistory,
        ]);

        dump('Response',$response);

        $assistant_reply = $response['choices'][0]['message']['content'];


// Regular expressions for PHP and Bash code blocks
        $php_pattern = '/```php(.*?)```/s';
        $bash_pattern = '/```bash(.*?)```/s';
        $json_pattern = '/```json(.*?)```/s';

// Initialize empty arrays to hold the code blocks
        $php_blocks = [];
        $bash_blocks = [];
        $json_blocks = [];

        // Search for PHP code blocks and store them in $php_blocks
        preg_match_all($php_pattern, $assistant_reply, $php_matches);
        if (!empty($php_matches[1])) {
            $php_blocks = $php_matches[1];
        }

// Search for Bash code blocks and store them in $bash_blocks
        preg_match_all($bash_pattern, $assistant_reply, $bash_matches);
        if (!empty($bash_matches[1])) {
            $bash_blocks = $bash_matches[1];
        }
        preg_match_all($json_pattern, $assistant_reply, $bash_matches);
        if (!empty($bash_matches[1])) {


            foreach ($bash_matches as $data){
                dump('data',$data);

                dump(json_decode($data[0],true));

            }

            $json_blocks = $bash_matches[1];
        }

        dd('DD:',$assistant_reply,json_decode($assistant_reply, true),$conversationHistory,$bash_blocks,$php_blocks,$json_blocks);


        // Make the API call using $fileContents




    }

    public function process(Request $request)
    {



        $api_key = env('OPENAI_API_KEY');

        // $exampleTemplatePath = resource_path('views/example.blade.php');
        //$exampleContent = File::get($exampleTemplatePath);

        $prompt = "USing Laravel and php generate the files for an Index Veiw, Controller, and Model for a model named 'Product'";

        $yourApiKey = getenv('YOUR_API_KEY');

        $client = OpenAI::client($api_key);
        $response = $client->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        //dd($response);

        $examples = [

            //"Example 1: Existing Blade template" => $exampleContent,
        ];

        $result = OpenAI::completions()->create([
            'model' => 'gpt-4',
            'prompt' =>$prompt,
            'max_tokens' => 100
        ]);




        echo "completion response:\n";
        dump($result);
        echo "<h2>Completion result 0: {$result['choices'][0]['text']}</h2>"; // an open-source, widely-used, server-side scripting language.

        exit;
        $result = OpenAI::completions()->create([
            'model' => 'text-davinci-003',
            'prompt' => [
                'prompt' => $prompt,
                'examples' => $examples
            ],
            'max_tokens' => 100
        ]);


        return redirect('/openai')->with('message', $message);








        // Step 1: Read the content of example files
        $exampleFile1 = file_get_contents('example1.txt');
        $exampleFile2 = file_get_contents('example2.txt');

// Step 2: Format the examples and new requirements
        $conversationHistory = [
            ['role' => 'system', 'content' => 'You are a helpful assistant that generates code.'],
            ['role' => 'user', 'content' => "Here's an example from example1.txt:\n$exampleFile1"],
            ['role' => 'user', 'content' => "Here's another example from example2.txt:\n$exampleFile2"],
            ['role' => 'user', 'content' => 'Now generate a new file for a different model, say, Product.']
        ];

// Step 3: Make the API request
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => $conversationHistory,
        ]);

// Extract the assistant's reply
        $assistant_reply = end($response['choices'][0]['message']['content']);














    }









    public function deleteFunction($id)
    {
        $function = OpenAIFunction::where('id', $id)->firstOrFail();
        $function->delete();

        return response()->json(['delete'=>'success'],200);
    }

    public function storeFunction(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'parameters' => 'required|json',
        ]);

        $function = new OpenAIFunction($validated);
        $function->user_id = Auth::id();
        $function->save();

        return response()->json($function, 201);
    }

    public function functions()
    {
        $functions = OpenAIFunction::all();
        return response()->json($functions);
    }

    public function functionsIndex()
    {
        $functions = OpenAIFunction::all();
        return view('chat.functions', [

            'functions' => $functions,

        ]);
    }















    public function uploadFiles(Request $request)
    {
        $selectedFiles = $request->input('files');

        if ($selectedFiles) {
            $uploadedFiles = [];
            foreach ($selectedFiles as $file) {
                $fileContent = file_get_contents($file);
                // Upload file to OpenAI
                $response = $this->client->post("https://api.openai.com/v1/files", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'multipart/form-data'
                    ],
                    'multipart' => [
                        [
                            'name'     => 'file',
                            'contents' => $fileContent,
                            'filename' => basename($filePath)
                        ],
                        [
                            'name'     => 'purpose',
                            'contents' => 'answers'
                        ]
                    ]
                ]);

                $body = $response->getBody();
                $result = json_decode($body, true);
                $uploadedFiles[] = $result['id'];
            }
            $assistantSchema['files'] = $uploadedFiles;
        }


    }




    public function listFiles(Request $request)
    {
        $root = $request->input('root', '/');
        $files = $this->openAIAssistant->list_files($root);
        return response()->json(['files' => $files]);
    }

    public function listAssistants()
    {
        $assistants = $this->openAIAssistant->list_assistants();
        return response()->json(['assistants' => $assistants]);
    }

    public function storeAssistant(Request $request){


        $assistantName = $request->input('name');
        $model = $request->input('model');
        $description = $request->input('description');
        $instructions = $request->input('instructions');
        $persona = $request->input('persona');
        $selectedFunctions = $request->input('functions');
        $jsonOnly = $request->input('json_only') ?? false;
        $selectedFiles = $request->input('files');
        $selectedOnlineFiles = $request->input('onlineFiles');


        if(!$selectedOnlineFiles){
            $selectedOnlineFiles = [];
        }



        if ($selectedFiles) {

            foreach ($selectedFiles as $file) {
                $fileContent = file_get_contents($file);
                // Upload file to OpenAI
                $response = $this->client->post("https://api.openai.com/v1/files", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'multipart/form-data'
                    ],
                    'multipart' => [
                        [
                            'name'     => 'file',
                            'contents' => $fileContent,
                            'filename' => basename($filePath)
                        ],
                        [
                            'name'     => 'purpose',
                            'contents' => 'answers'
                        ]
                    ]
                ]);

                $body = $response->getBody();
                $result = json_decode($body, true);
                $selectedOnlineFiles[] = $result['id'];
            }

        }



            // Define available functions
        $functions = [
            "download_file" => [
                "name" => "download_file",
                "description" => "Download a file from the server.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "filePath" => ["type" => "string", "description" => "The path of the file to download."]
                    ],
                    "required" => ["filePath"]
                ]
            ],
            "delete_file" => [
                "name" => "delete_file",
                "description" => "Delete a file from the server.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "filePath" => ["type" => "string", "description" => "The path of the file to delete."]
                    ],
                    "required" => ["filePath"]
                ]
            ],
            "list_files" => [
                "name" => "list_files",
                "description" => "List all files in a specified directory.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "directory" => ["type" => "string", "description" => "The directory to list files from."]
                    ],
                    "required" => ["directory"]
                ]
            ],
            "list_folders" => [
                "name" => "list_folders",
                "description" => "List all folders in a specified directory.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "directory" => ["type" => "string", "description" => "The directory to list folders from."]
                    ],
                    "required" => ["directory"]
                ]
            ],
            "create_directory" => [
                "name" => "create_directory",
                "description" => "Create a new directory.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "directory" => ["type" => "string", "description" => "The name of the directory to create."]
                    ],
                    "required" => ["directory"]
                ]
            ],
            "delete_directory" => [
                "name" => "delete_directory",
                "description" => "Delete a directory.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "directory" => ["type" => "string", "description" => "The name of the directory to delete."]
                    ],
                    "required" => ["directory"]
                ]
            ],
            "put_text" => [
                "name" => "put_text",
                "description" => "Save text content to a specified file, replacing existing content.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "filePath" => ["type" => "string", "description" => "The path of the file to save content to."],
                        "content" => ["type" => "string", "description" => "The content to save to the file."]
                    ],
                    "required" => ["filePath", "content"]
                ]
            ],
            "append_text" => [
                "name" => "append_text",
                "description" => "Append text content to a specified file.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "filePath" => ["type" => "string", "description" => "The path of the file to append content to."],
                        "content" => ["type" => "string", "description" => "The content to append to the file."]
                    ],
                    "required" => ["filePath", "content"]
                ]
            ],
            "edit" => [
                "name" => "edit",
                "description" => "Edit an existing content.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "filePath" => ["type" => "string", "description" => "The path of the file to edit."],
                        "content" => ["type" => "string", "description" => "The new content to replace the old content."]
                    ],
                    "required" => ["filePath", "content"]
                ]
            ],
            "send_email" => [
                "name" => "send_email",
                "description" => "Send an email to a specified address.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "to" => ["type" => "string", "description" => "The email address to send to."],
                        "subject" => ["type" => "string", "description" => "The subject of the email."],
                        "body" => ["type" => "string", "description" => "The body of the email."]
                    ],
                    "required" => ["to", "subject", "body"]
                ]
            ],

            // Project Management Functions
            "list_users" => [
                "name" => "list_users",
                "description" => "List all users",
                "parameters" => [
                    "type" => "object",
                    "properties" => new \stdClass()
                ]
            ],
            "add_user" => [
                "name" => "add_user",
                "description" => "Add a new user",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "name" => ["type" => "string", "description" => "The name of the user"],
                        "email" => ["type" => "string", "description" => "The email of the user"],
                        "password" => ["type" => "string", "description" => "The password of the user"]
                    ],
                    "required" => ["name", "email", "password"]
                ]
            ],
            "view_user" => [
                "name" => "view_user",
                "description" => "View a specific user by ID",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "id" => ["type" => "integer", "description" => "The ID of the user"]
                    ],
                    "required" => ["id"]
                ]
            ],
            "delete_user" => [
                "name" => "delete_user",
                "description" => "Delete a user by ID",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "id" => ["type" => "integer", "description" => "The ID of the user"]
                    ],
                    "required" => ["id"]
                ]
            ],
            "list_projects" => [
                "name" => "list_projects",
                "description" => "List all projects",
                "parameters" => [
                    "type" => "object",
                    "properties" => new \stdClass()
                ]
            ],
            "add_project" => [
                "name" => "add_project",
                "description" => "Add a new project",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "name" => ["type" => "string", "description" => "The name of the project"],
                        "description" => ["type" => "string", "description" => "The description of the project"]
                    ],
                    "required" => ["name"]
                ]
            ],
            "view_project" => [
                "name" => "view_project",
                "description" => "View a specific project by ID",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "id" => ["type" => "integer", "description" => "The ID of the project"]
                    ],
                    "required" => ["id"]
                ]
            ],
            "delete_project" => [
                "name" => "delete_project",
                "description" => "Delete a project by ID",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "id" => ["type" => "integer", "description" => "The ID of the project"]
                    ],
                    "required" => ["id"]
                ]
            ],
            "list_goals" => [
                "name" => "list_goals",
                "description" => "List all goals",
                "parameters" => [
                    "type" => "object",
                    "properties" => new \stdClass()
                ]
            ],
            "add_goal" => [
                "name" => "add_goal",
                "description" => "Add a new goal",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "name" => ["type" => "string", "description" => "The name of the goal"],
                        "description" => ["type" => "string", "description" => "The description of the goal"],
                        "project_id" => ["type" => "integer", "description" => "The ID of the project"]
                    ],
                    "required" => ["name", "project_id"]
                ]
            ],
            "view_goal" => [
                "name" => "view_goal",
                "description" => "View a specific goal by ID",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "id" => ["type" => "integer", "description" => "The ID of the goal"]
                    ],
                    "required" => ["id"]
                ]
            ],
            "delete_goal" => [
                "name" => "delete_goal",
                "description" => "Delete a goal by ID",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "id" => ["type" => "integer", "description" => "The ID of the goal"]
                    ],
                    "required" => ["id"]
                ]
            ],
            "list_tasks" => [
                "name" => "list_tasks",
                "description" => "List all tasks",
                "parameters" => [
                    "type" => "object",
                    "properties" => new \stdClass()
                ]
            ],
            "add_task" => [
                "name" => "add_task",
                "description" => "Add a new task",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "name" => ["type" => "string", "description" => "The name of the task"],
                        "description" => ["type" => "string", "description" => "The description of the task"],
                        "goal_id" => ["type" => "integer", "description" => "The ID of the goal"]
                    ],
                    "required" => ["name", "goal_id"]
                ]
            ],
            "view_task" => [
                "name" => "view_task",
                "description" => "View a specific task by ID",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "id" => ["type" => "integer", "description" => "The ID of the task"]
                    ],
                    "required" => ["id"]
                ]
            ],
            "delete_task" => [
                "name" => "delete_task",
                "description" => "Delete a task by ID",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "id" => ["type" => "integer", "description" => "The ID of the task"]
                    ],
                    "required" => ["id"]
                ]
            ],
            "list_issues" => [
                "name" => "list_issues",
                "description" => "List all issues",
                "parameters" => [
                    "type" => "object",
                    "properties" => new \stdClass()
                ]
            ],
            "add_issue" => [
                "name" => "add_issue",
                "description" => "Add a new issue",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "title" => ["type" => "string", "description" => "The title of the issue"],
                        "description" => ["type" => "string", "description" => "The description of the issue"],
                        "status" => ["type" => "string", "description" => "The status of the issue"],
                        "priority" => ["type" => "string", "description" => "The priority of the issue"]
                    ],
                    "required" => ["title", "status", "priority"]
                ]
            ],
            "view_issue" => [
                "name" => "view_issue",
                "description" => "View a specific issue by ID",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "id" => ["type" => "integer", "description" => "The ID of the issue"]
                    ],
                    "required" => ["id"]
                ]
            ],
            "delete_issue" => [
                "name" => "delete_issue",
                "description" => "Delete an issue by ID",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "id" => ["type" => "integer", "description" => "The ID of the issue"]
                    ],
                    "required" => ["id"]
                ]
            ],
            "list_deadlines" => [
                "name" => "list_deadlines",
                "description" => "List all deadlines",
                "parameters" => [
                    "type" => "object",
                    "properties" => new \stdClass()
                ]
            ],
            "add_deadline" => [
                "name" => "add_deadline",
                "description" => "Add a new deadline",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "deadline_date" => ["type" => "string", "description" => "The date of the deadline", "format" => "date"],
                        "name" => ["type" => "string", "description" => "The name of the deadline"],
                        "description" => ["type" => "string", "description" => "The description of the deadline"],
                        "status" => ["type" => "string", "description" => "The status of the deadline"],
                        "priority" => ["type" => "string", "description" => "The priority of the deadline"],
                        "type" => ["type" => "string", "description" => "The type of the deadline"]
                    ],
                    "required" => ["deadline_date"]
                ]
            ],
            "view_deadline" => [
                "name" => "view_deadline",
                "description" => "View a specific deadline by ID",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "id" => ["type" => "integer", "description" => "The ID of the deadline"]
                    ],
                    "required" => ["id"]
                ]
            ],
            "delete_deadline" => [
                "name" => "delete_deadline",
                "description" => "Delete a deadline by ID",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "id" => ["type" => "integer", "description" => "The ID of the deadline"]
                    ],
                    "required" => ["id"]
                ]
            ],
            "list_plans" => [
                "name" => "list_plans",
                "description" => "List all plans",
                "parameters" => [
                    "type" => "object",
                    "properties" => new \stdClass()
                ]
            ],
            "add_plan" => [
                "name" => "add_plan",
                "description" => "Add a new plan",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "name" => ["type" => "string", "description" => "The name of the plan"],
                        "description" => ["type" => "string", "description" => "The description of the plan"],
                        "type" => ["type" => "string", "description" => "The type of the plan"],
                        "status" => ["type" => "string", "description" => "The status of the plan"],
                        "priority" => ["type" => "integer", "description" => "The priority of the plan"],
                        "date" => ["type" => "string", "description" => "The date of the plan", "format" => "date"]
                    ],
                    "required" => ["name", "type", "status", "priority", "date"]
                ]
            ],
            "view_plan" => [
                "name" => "view_plan",
                "description" => "View a specific plan by ID",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "id" => ["type" => "integer", "description" => "The ID of the plan"]
                    ],
                    "required" => ["id"]
                ]
            ],
            "delete_plan" => [
                "name" => "delete_plan",
                "description" => "Delete a plan by ID",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "id" => ["type" => "integer", "description" => "The ID of the plan"]
                    ],
                    "required" => ["id"]
                ]
            ],
            "assign" => [
                "name" => "assign",
                "description" => "Assign an item to a user or another entity",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "assigned_id" => ["type" => "integer", "description" => "The ID of the assigned item"],
                        "assigned_type" => ["type" => "string", "description" => "The type of the assigned item"],
                        "assigned_to_id" => ["type" => "integer", "description" => "The ID of the assigned to item"],
                        "assigned_to_type" => ["type" => "string", "description" => "The type of the assigned to item"],
                        "context" => ["type" => "string", "description" => "The context of the assignment"]
                    ],
                    "required" => ["assigned_id", "assigned_type", "assigned_to_id", "assigned_to_type"]
                ]
            ],
            "unassign" => [
                "name" => "unassign",
                "description" => "Unassign an item by ID",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "id" => ["type" => "integer", "description" => "The ID of the assignment"]
                    ],
                    "required" => ["id"]
                ]
            ]
        ];


        $response_format = ($jsonOnly) ? "json" : "auto";






        $assistantSchema = [
            "name" => $assistantName,
            "model" => $model,
            "description" => $description,
            "instructions" => $instructions,
            "response_format" => "auto",
            "tools" => [["type"=>"code_interpreter"]],
            'file_ids' => $selectedOnlineFiles,

        ];

            // Add selected functions to the assistant schema
            foreach ($selectedFunctions as $function) {
                if (isset($functions[$function])) {
                    $assistantSchema['tools'][] = [
                        "type" => "function",
                        "function" => $functions[$function]
                    ];
                }
            }




            try {
                // Make API call to create the assistant
                $response = $this->openAIAssistant->client->post("https://api.openai.com/v1/assistants", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                        'Content-Type' => 'application/json',
                        'OpenAI-Beta' => 'assistants=v1' // Ensure the correct version
                    ],
                    'json' => $assistantSchema,
                ]);

                $body = $response->getBody();
                $result = json_decode($body, true);

                return redirect()->back()->with('success', 'Assistant created with ID: ' . $result['id']);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Failed to create assistant: ' . $e->getMessage());
            }


    }


    public function deleteAssistant($id){

        return $this->openAIAssistant->delete_assistant($id);

    }


    public function deleteFile($id){

        return $this->openAIAssistant->delete_file($id);

    }

    public function newSession(){

        Session::put('session_id' ,uniqid() );

        redirect()->back();

    }


    public function getDirectoryTree($path = '/')
    {

        $this->disk = Storage::disk('rich');

        if ($path != '/' && !$this->disk->exists($path) ) {
            return [];
        }

        if($path!='/'){
            $path = ltrim($path, '/');
        }

        $directoryTree = $this->getDirectoryContents($path);

        return $directoryTree;
    }

    /**
     * Get the contents of the directory recursively.
     *
     * @param  string  $path
     * @return array
     */
    private function getDirectoryContents($path)
    {



        $blacklist = [0=>'test'];
        $blacklist[] = 'vendor';
        $blacklist[] = 'bootstrap';
        $blacklist[] = 'node_modules';
        $blacklist[] = 'public';
        $blacklist[] = 'storage';


        // Output the current path for debugging purposes


        // Get the list of directories and files from the specified disk and path
        $directories = $this->disk->directories($path);
        $files = $this->disk->files($path);

        // Initialize an array to hold the directory contents
        $contents = [];

        // Loop through each directory
        foreach ($directories as $directory) {
            // Skip hidden directories (starting with a dot)
            if (str_starts_with(basename($directory), '.')) {
                continue;
            }

            if(array_search(basename($directory), $blacklist)) {

                continue;

            }

            // Output the current directory for debugging purposes
            echo "$path/$directory" . PHP_EOL;

            // Recursively get the contents of the current directory
            $contents[$directory] = $this->getDirectoryContents($directory);
        }

        // Loop through each file
        foreach ($files as $file) {
            // Add the file to the contents array
            $contents[] = $file;
        }

        // Return the contents of the directory
        return $contents;
    }

    private function getFileStructure($directory, $relativePath = '')
    {
        $result = [];


        $blacklist = [0=>'test'];
        $blacklist[] = 'vendor';
        $blacklist[] = 'bootstrap';
        $blacklist[] = 'node_modules';
        $blacklist[] = '.idea';
        //$blacklist[] = 'public';
        //$blacklist[] = 'storage';


        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );



        foreach ($files as $file) {
            $path = $file->getPathname();
            $relativePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $path);
            $parts = explode(DIRECTORY_SEPARATOR, $relativePath);


            // Skip directories in the blacklist
            if (in_array($parts[0], $blacklist)) {
                continue;
            }


            $current = &$result;
            $currentPath = '';

            foreach ($parts as $part) {
                $currentPath .= $part . DIRECTORY_SEPARATOR;
                if (!isset($current[$part])) {
                    $current[$part] = [
                        'path' => $currentPath,
                        'children' => []
                    ];
                }
                $current = &$current[$part]['children'];
            }
        }

        return $result;
    }




}
