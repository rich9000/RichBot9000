<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpenAIAssistant;
use App\Services\ToolExecutor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Illuminate\Support\Facades\File;

class OpenAiApiController extends Controller
{
    protected $openAIAssistant;

    protected $disk;

    public function __construct(OpenAIAssistant $openAIAssistant)
    {
        $this->openAIAssistant = $openAIAssistant;
        $this->disk = Storage::disk('richbot_sandbox');
    }
    public function getThreadInfo(Request $request)
    {
        $threadId = $request->input('thread_id');
        try {
            $runs = $this->openAIAssistant->list_runs($threadId);
            return response()->json(['runs' => $runs]);
        } catch (\Exception $e) {
            Log::error("Error fetching thread info: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch thread info.'], 500);
        }
    }
    public function easyMode(Request $request)
    {
        $prompt = $request->input('prompt');

        // Optional inputs with defaults
        $assistant_id = $request->input('assistant_id', 'asst_wKkXDvi1ZpEW1fcappBf4SrN'); // Default assistant ID
        $instructions = $request->input('instructions', 'This is a one-shot task, you need to complete everything in this run, because there is only one run.'); // Default instructions

        $openAIAssistant = new OpenAIAssistant();
        $client = new Client();

        // Create a thread using instructions
        $thread_id = $openAIAssistant->create_thread($instructions, 'user');
        $msg = $openAIAssistant->add_message($thread_id, $prompt, 'user');

        // Create a run with the given or default assistant ID
        $run_id = $openAIAssistant->create_run($thread_id, $assistant_id);
      //  $run = $openAIAssistant->get_run($thread_id, $run_id);

        // echo "status: {$run['status']},{$run['required_action']}, {$run['id']}, {$run['thread_id']} ,{$run['assistant_id']}";

        $retryCount = 0;
        $maxRetries = 10;
        $retryDelay = 2; // seconds

        do {

            sleep($retryDelay);
            try {

                $run = $openAIAssistant->get_run($thread_id, $run_id);
                //echo "status: {$run['status']},{$run['required_action']}, {$run['id']}, {$run['thread_id']} ,{$run['assistant_id']}";

            } catch (\Exception $e) {

                \Log::error("Error retrieving run (attempt $retryCount): " . $e->getMessage());

                if (++$retryCount > $maxRetries) {
                    throw $e;
                }
                continue;
            }

            if ($run['status'] == 'requires_action') {
                $toolExecutor = new ToolExecutor(); // Implement this class
                $outputs = $openAIAssistant->execute_tools($thread_id, $run_id, $toolExecutor);
                $openAIAssistant->submit_tool_outputs($thread_id, $run_id, $outputs);
            }

        } while (!($run['status'] == 'completed' || $run['status'] == 'failed') );


        if($run['status'] == 'completed') {

            $messages = $openAIAssistant->list_thread_messages($thread_id);
            return response()->json($messages);

        } else {

            return response()->json(['error' => 'yes', 'message' => 'Task execution failed', 'status' => 'fail']);

        }


    }

    public function createThread(Request $request)
    {
        $instructions = $request->input('instructions');
        $files = $request->input('files');

        //var_dump($files);


        $file_info = array();
        if(count($files)) {

            foreach ($files as $file_path) {

                $file_info[$file_path] = $this->disk->get($file_path);

            }

        }

        try {

            Log::error("creating thread:".$instructions);

            $threadId = $this->openAIAssistant->create_thread($instructions,'user',$file_info);

            return response()->json(['thread_id' => $threadId]);

        } catch (\Exception $e) {
            Log::error("Error creating thread: " . $e->getMessage());
            return response()->json(['error' => 'Failed to create thread.' . $e->getMessage()], 500);
        }
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'thread_id' => 'required|string',
            'prompt' => 'required|string',
            'assistant' => 'required|string',
        ]);

        $threadId = $request->input('thread_id');
        $question = $request->input('prompt');
        $assistantId = $request->input('assistant');

        Log::error("ThreadId: $threadId question:\n$question\nAssistant: $assistantId");


      /*  //clear out any runs. We will figure out why they are not working later.
        $thread_runs = $this->openAIAssistant->list_runs($threadId);
        foreach ($thread_runs as $run){

            if ($run['status'] == 'requires_action') {

                // echo "Required Action\n";
                // var_dump($run);
                $toolExecutor = new ToolExecutor(); // Implement this class
                $outputs = $this->openAIAssistant->execute_tools($threadId, $run['id'], $toolExecutor);
                $this->openAIAssistant->submit_tool_outputs($threadId, $run['id'], $outputs);

            }

        }

      */
        $retryCount = 0;
        $maxRetries = 10;
        $retryDelay = 2; // seconds

      $thread_id =  $threadId;
      $this->openAIAssistant->add_message($threadId, $question);

      $run_id = $this->openAIAssistant->create_run($threadId, $assistantId);

      $run = $this->openAIAssistant->get_run($thread_id, $run_id);

        do {

            sleep($retryDelay);

            try {

                \Log::error("Trying Run $thread_id $run_id\n");

                $run = $this->openAIAssistant->get_run($thread_id, $run_id);

                \Log::error("status: {$run['status']},, {$run['id']}, {$run['thread_id']} ,{$run['assistant_id']}");



                if ($run['status'] == 'requires_action') {

                    $toolExecutor = new ToolExecutor(); // Implement this class
                    $outputs = $this->openAIAssistant->execute_tools($thread_id, $run_id, $toolExecutor);
                    $this->openAIAssistant->submit_tool_outputs($thread_id, $run_id, $outputs);

                }

            } catch (\Exception $e) {

                \Log::error("Error retrieving run (attempt $retryCount): " . $e->getMessage());

                if (++$retryCount > $maxRetries) {
                    throw $e;
                }
                continue;
            }


        } while (!($run['status'] == 'completed' || $run['status'] == 'failed') );


        try {
            $messages = $this->openAIAssistant->list_thread_messages($threadId);
            return response()->json(['messages' => $messages]);

        } catch (\Exception $e) {
            Log::error("Error sending message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to process the request.','message'=>$e->getMessage()], 500);

        }

    }

    public function sendMessageOld(Request $request)
    {


        exit;




        $sessionId = Session::get('session_id', 'default');
        $threadId = Session::get('thread_id_' . $sessionId, false);
        $question = $request->input('prompt');
        $assistantId = $request->input('assistant');

        try {
            if (!$threadId) {
                $threadId = $this->openAIAssistant->create_thread("Start the conversation.");
                Session::put('thread_id_' . $sessionId, $threadId);
            }

            $this->openAIAssistant->add_message($threadId, $question);
            $executionId = $this->openAIAssistant->run_thread($threadId);

            // Handle tool calls if needed
            if ($this->openAIAssistant->has_tool_calls) {
                $toolExecutor = new ToolExecutor(); // Implement this class
                $outputs = $this->openAIAssistant->execute_tools($threadId, $executionId, $toolExecutor);
                $this->openAIAssistant->submit_tool_outputs($threadId, $executionId, $outputs);
                $executionId = $this->openAIAssistant->run_thread($threadId);
            }

            $messages = $this->openAIAssistant->list_thread_messages($threadId);

            return response()->json(['messages' => $messages]);
        } catch (\Exception $e) {
            Log::error("Error sending message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to process the request.'], 500);
        }
    }

    public function getUpdates($thread_id)
    {

        $threadId = $thread_id;

        try {

            $runs = $this->openAIAssistant->list_runs($threadId);
            $messages = $this->openAIAssistant->list_thread_messages($threadId);

            return response()->json(['messages' => $messages, 'runs' => $runs]);

        } catch (\Exception $e) {

            Log::error("Error fetching updates: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch updates.'], 500);

        }
    }

    // Other methods related to OpenAI API interactions...
    public function listFiles($directory = '/var/www/html/richbot9000.com/', $relativePath = '')
    {
        $result = [];




        $blacklist = [0=>'test'];
        $blacklist[] = 'vendor';
        $blacklist[] = 'bootstrap';
        $blacklist[] = 'node_modules';
        $blacklist[] = '.idea';
        $blacklist[] = '.';
        $blacklist[] = 'config';
        $blacklist[] = 'backup';
        $blacklist[] = 'mnt';
        $blacklist[] = 'storage';


        $files = $this->disk->allFiles('/');


        $filteredFiles = array_filter($files, function($file) use ($blacklist) {
            // Split the file path into parts
            $parts = explode('/', $file);

            if(count($parts) == 1) return false;

            // Remove the filename from the path
           $filename = array_pop($parts);

            // Check each part of the path
            foreach ($parts as $part) {
                foreach ($blacklist as $blacklistedDir) {
                    // Check if the directory name starts with or equals any blacklisted name
                    if ($part === $blacklistedDir || strpos($part, $blacklistedDir) === 0) {
                        // Exclude this file
                        return false;
                    }
                }
            }
            // Include this file
            return true;
        });

        $files = array();
        foreach ($filteredFiles as $file) {
         //   echo "$file\n";
            $files[] = $file;
        }
        return response()->json($files);
       // return json_encode($filteredFiles);
    }
}

