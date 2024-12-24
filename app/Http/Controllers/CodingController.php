<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use App\Models\CodingSession;
use App\Models\Conversation;
use App\Models\Pipeline;
use App\Services\CodingExecutor;
use App\Services\OpenAIAssistant;
use App\Services\ToolExecutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class CodingController extends Controller
{
    /**
     * Create a new coding session.
     */
    public function createSession(Request $request)
    {
        $request->validate([
            'prompt' => 'sometimes|string',
            'files' => 'sometimes|array',
        ]);

        $session = CodingSession::create([
            'user_id' => $request->user()->id, // Associate with the authenticated user
         //   'prompt' => json_encode($request->input('prompt')),
          //  'files' => json_encode($request->input('files')),
            'status' => 'pending',
        ]);

        return response()->json(['success' => true, 'session_id' => $session->id]);
    }

    /**
     * Start the coding process.
     */
    public function startCoding(Request $request)
    {



        $sessionId = $request->input('session_id');
        $session = CodingSession::where('id', $sessionId)
            ->where('user_id', $request->user()->id) // Ensure the user owns the session
            ->first();

        if (!$session) {
            // Create a new session if it doesn't exist
            $session = CodingSession::create([
                'user_id' => $request->user()->id,
                'status' => 'in_progress',
            ]);
        }

        $session->status = 'in_progress';
        $session->save();

       $data = $request->all();



      //  if(!$session->prompt && $request->input('prompt')){
      //      $session->prompt = $request->input('prompt');
     //   }
     //   if(!$session->files && $request->input('files')){
     //       $session->prompt = $request->input('prompt');
     //   }
        //$session->save();

        $result = $this->runCodingWorkflow($data);

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'workflow_result' => $result,
        ]);
    }

    /**
     * Run the coding workflow.
     */
    private function runCodingWorkflow($session)
    {


        $openai = new OpenAIAssistant();

        $storage = Storage::disk('richbot_sandbox');



      //  $conversation = Conversation::find($session['conversation_id']);
        $assistant = Assistant::find($session['assistant_id']);
        $pipeline = Pipeline::find($session['pipeline_id']);
        $prompt = $session['prompt'];
        $files = $session['files'];
        $action = $session['action'];


        $runs = array();


        // lol do session last
        $session = CodingSession::find($session['session_id']);
       // $extraParams = json_decode($session->files, true);

        if(!$files){
            $files = [];
        }


        $stage = $pipeline->stages()->first();




        if($stage->type == 'assistant-swarm') {

            foreach ($stage->assistants as $stage_assistant) {

                if($stage_assistant->type == 'assistant-swarm' || $stage_assistant->type == 'context') {

                    foreach ($stage->files as $file){

                        if (!$storage->exists($file['file_path'])) {
                            return response()->json(['error' => 'File not found'], 404);
                        }

                        $fileContents = $storage->get($file['file_path']);
                        $mimeType = $storage->mimeType($file['file_path']);

                        $content = "Here is a file for additional context or information, use it to answer any questions.\n{$file['file_path']}:\n$fileContents";


                        $files[] = ['reason'=>'context','content' => $content, 'mimeType' => $mimeType, 'path' => $file['file_path']];

                        //$conversation->addMessage('system', $content);
                        //$openai->add_message($thread_id, $content, 'user');

                    }

                    $info = $stage_assistant->startOpenAiRun($prompt, $files);

                    $runs[$info['run_id']] = $info;

                }

            }

            //$assistant->askOpenAiPrompt($prompt, $files);

        }


        $retryCount = 3;
        $maxRetries = 3;

        $run_runs = true;

        while($run_runs == true) {

            $run_runs = false;

            Log::info("Starting round of runs.");


            foreach ($runs as $run_info) {

                $conversation = Conversation::find($run_info['conversation_id']);

                //dump($run_info);

                Log::info('Run Info:'.json_encode($run_info,true));

                try {
                    $run = $openai->get_run($run_info['thread_id'], $run_info['run_id']);
                } catch (\Exception $e) {
                    Log::error("Error retrieving run (attempt $retryCount): " . $e->getMessage());
                    if (++$retryCount > $maxRetries) {
                        throw $e;
                    }
                    continue;
                }



                if ($run['status'] == 'in_progress') {

                    $run_runs = true;
                }

                if ($run['status'] == 'requires_action') {

                    Log::info('Run Info:'.json_encode($run,JSON_PRETTY_PRINT));

                    $run_runs = true;

                    $optional_objects = [];
                    $optional_objects[] = new CodingExecutor();
                    $optional_objects[] = new ToolExecutor();

                    $outputs = [];

                    $calls = $run['required_action']['submit_tool_outputs']['tool_calls'];

                    foreach ($calls as $call) {

                        $method_name = $call['function']['name'];
                        $method_args = json_decode($call['function']['arguments'], true);

                        Log::info('Call Info:'.json_encode($call,JSON_PRETTY_PRINT));
                        Log::info('Method:'.$method_name.' '.json_encode($method_args,JSON_PRETTY_PRINT));

                        if(is_callable([$optional_objects[0],$method_name])){

                            Log::info('CALLABLE CODER:'.$method_name.' '.json_encode($method_args,JSON_PRETTY_PRINT));

                            $data = call_user_func([$optional_objects[0],$method_name], $method_args);

                        } else if (is_callable([$this, $method_name])) {
                            Log::info('CALLABLE THIS:'.$method_name.' '.json_encode($method_args,JSON_PRETTY_PRINT));

                            $data = call_user_func([$this,$method_name], $method_args);

                        } elseif(is_callable([$optional_objects[1],$method_name])) {
                            Log::info('CALLABLE Tool Executor:'.$method_name.' '.json_encode($method_args,JSON_PRETTY_PRINT));

                            $callable = $optional_objects[1];
                            $data = call_user_func([$optional_objects[1],$method_name], $method_args);

                        }

                        $conversation->addMessage('assistant', "$method_name:args".json_encode($method_args).":response:".json_encode($data));

                        $outputs[] = [
                            'tool_call_id' => $call['id'],
                            'output' => json_encode($data)
                        ];



                    }

                    Log::info("Outputs:");
                    Log::info(json_encode($outputs, JSON_PRETTY_PRINT));

                    $response = $openai->send_post_request("/threads/{$run_info['thread_id']}/runs/{$run_info['run_id']}/submit_tool_outputs", [
                        'tool_outputs' => $outputs
                    ]);






                }

            }



        }

        $message_array = [];
        $messages_array = [];




        foreach ($runs as $run){

        //    dump($run);

            $conversation = Conversation::find($run['conversation_id']);

            $messages = $openai->list_thread_messages($run['thread_id']);
            if($messages[0]['content'][0]['type'] == 'text'){


                $message = $messages[0]['content'][0]['text']['value'];
                $role = $messages[0]['role'];

                $conversation->addMessage($role,$message);

            }

            $messages_array += $conversation->getConversationMessages();
        }

        $audioUrl = false;


        dd($messages_array);

        return response()->json(['messages'=>$messages_array,'messages_array' => $messages_array,'message_array'=>$message_array, 'audio' => $audioUrl]);

    }

    /**
     * Simulate running an assistant.
     */
    private function runAssistant($assistantName, $data)
    {
        // Simulated assistant output (replace with actual AI logic if integrated)
        return [
            'assistant' => $assistantName,
            'output' => "Processed data for assistant: " . json_encode($data),
        ];
    }

    /**
     * Compile assistant outputs into a single result.
     */
    private function compileAssistantInfo(array $outputs)
    {
        // Combine the outputs from all assistants
        return collect($outputs)->pluck('output')->implode("\n");
    }
}
