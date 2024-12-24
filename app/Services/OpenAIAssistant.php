<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use App\Models\AssistantFunction;
use Illuminate\Support\Facades\Log;

class OpenAIAssistant
{
    public $client;
    public $apiKey;
    public $assistant_id;
    public $base_url;
    public $version_header;

    public $has_tool_calls = false;
    public $tool_call_id = null;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('OPENAI_API_KEY');
        $this->base_url = 'https://api.openai.com/v1';
        $this->version_header = 'OpenAI-Beta: assistants=v1';
    }

    private function send_get_request($route)
    {





        try {



            $response = $this->client->get("{$this->base_url}{$route}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'OpenAI-Beta' => 'assistants=v2',
                ]
            ]);




            if ($response->getStatusCode() != 200) {
                throw new \Exception("OpenAI API Returned Unexpected HTTP code {$response->getStatusCode()}.");
            }

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error("GET request failed: {$e->getMessage()}");
            throw $e;
        }
    }












    public function generateImageUrl ($prompt,$size){



        // Step 2: Call OpenAI API to generate the image
        $apiKey = env('OPENAI_API_KEY');

        $response = $this->client->post('https://api.openai.com/v1/images/generations', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'OpenAI-Beta' => 'assistants=v2',
            ],
            'json' => ['prompt' => $prompt,
            'n' => 1,  // Number of images to generate
            'size' => $size]
        ]);

        $responseBody = json_decode($response->getBody(), true);

        $imageUrl = $responseBody['data'][0]['url'];

        \Log::info("OpenAIAssistan generate image url $imageUrl");

        //exit;

        return $imageUrl;


    }









    public function send_post_request($route, $payload = null)
    {
        try {
            $response = $this->client->post("{$this->base_url}{$route}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'OpenAI-Beta' => 'assistants=v2',
                ],
                'json' => $payload,
            ]);

            if ($response->getStatusCode() != 200) {
                throw new \Exception("OpenAI API Returned Unexpected HTTP code {$response->getStatusCode()}.");
            }

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error("POST request failed: {$e->getMessage()}");
            throw $e;
        }
    }

    public function delete_file($id)
    {
        try {
            $response = $this->client->delete("{$this->base_url}/files/{$id}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'OpenAI-Beta' => 'assistants=v1',
                ]
            ]);

            if ($response->getStatusCode() != 200) {
                throw new \Exception("OpenAI API Returned Unexpected HTTP code {$response->getStatusCode()}.");
            }

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error("DELETE request failed: {$e->getMessage()}");
            throw $e;
        }
    }

    public function delete_assistant($id)
    {
        try {
            $response = $this->client->delete("{$this->base_url}/assistants/{$id}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'OpenAI-Beta' => 'assistants=v1',
                ]
            ]);

            if ($response->getStatusCode() != 200) {
                throw new \Exception("OpenAI API Returned Unexpected HTTP code {$response->getStatusCode()}.");
            }

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error("DELETE request failed: {$e->getMessage()}");
            throw $e;
        }
    }

    public function create_assistant($name, $instructions, $selectedFunctions = [], $selectedOnlineFiles= [],$tools = [],$model = 'gpt-4o-mini')
    {

        if($selectedFunctions){

            $functions = AssistantFunction::whereIn('name', $selectedFunctions)->get();

            $tools = $functions->map(function ($function) {
                return [
                    "type" => "function",
                    "function" => [
                        "name" => $function->name,
                        "description" => $function->description,
                        "parameters" => json_decode($function->parameters, true)
                    ]
                ];
            })->toArray();
        }

        $tools[] = ["type" => "code_interpreter"];



        $response = $this->send_post_request('/assistants', [
            'name' => $name,
            'instructions' => $instructions,
            'model' => $model,
            'tools' => $tools->toArray(),

        ]);


        if (empty($response['id'])) {
            throw new \Exception('Unable to create an assistant');
        }

        $this->assistant_id = $response['id'];
        return $response['id'];
    }

    public function modify_assistant($name, $instructions, $tools)
    {
        if (!$this->assistant_id) {
            throw new \Exception('You need to provide an assistant_id or create an assistant.');
        }

        $response = $this->send_post_request("/assistants/{$this->assistant_id}", [
            'name' => $name,
            'instructions' => $instructions,
            'model' => 'gpt-4-1106-preview',
            'tools' => $tools
        ]);

        if (empty($response['id'])) {
            throw new \Exception('Unable to modify the assistant');
        }

        return $response['id'];
    }

    public function list_assistants()
    {
        $response = $this->send_get_request('/assistants');

        if (empty($response['data'])) {
            return [];
        }

        return $response['data'];
    }

    public function list_files()
    {
        $response = $this->send_get_request('/files');

        if (empty($response['data'])) {
            return [];
        }

        return $response['data'];
    }

    public function create_thread($content = '', $role = 'user',$files = [])
    {

        $messages = [];

        if($content){

            $messages = [
                [
                    'role' => $role,
                    'content' => $content
                ]
            ];

        }


        foreach ($files as $filePath => $fileContent) {
            $messages[] = [
                'role' => 'user',
                'content' => "Content of {$filePath}:\n\n{$fileContent}"
            ];
        }

        $response = $this->send_post_request('/threads', [
            'messages' => $messages,
            "tool_choice" => "auto"
        ]);

        if (empty($response['id'])) {
            throw new \Exception('Unable to create a thread');
        }

        return $response['id'];
    }
    public function create_base_thread()
    {

        $response = $this->send_post_request('/threads');

        if (empty($response['id'])) {
            throw new \Exception('Unable to create a thread');
        }

        return $response['id'];
    }
    public function list_threads()
    {
        return $this->send_post_request('/threads');
    }

    public function get_thread($thread_id)
    {
        $response = $this->send_get_request("/threads/{$thread_id}");

        if (empty($response['id'])) {
            throw new \Exception('Unable to retrieve the thread');
        }

        return $response;
    }

    public function add_message($thread_id, $content, $role = 'user')
    {


        $runs = $this->list_runs($thread_id);


        if(is_object($content)){

           $content = $content->content;
        }


        if (count($runs) > 0) {
            $last_run = $runs[0];

            if ($last_run['status'] == 'requires_action') {
                $this->has_tool_calls = true;
                $this->tool_call_id = $last_run['id'];
                return false;
            } else {
                $this->has_tool_calls = false;
                $this->tool_call_id = null;
            }
        }

        $response = $this->send_post_request("/threads/{$thread_id}/messages", [
            'role' => $role,
            'content' => $content
        ]);




       // Log::error("ThreadId runs: $thread_id\n".print_r($runs, true));

        if (empty($response['id'])) {
            throw new \Exception('Unable to create a message');
        }

        return $response['id'];
    }

    public function get_message($thread_id, $message_id)
    {
        $response = $this->send_get_request("/threads/{$thread_id}/messages/{$message_id}");

        if (empty($response['id'])) {
            throw new \Exception('Unable to retrieve the message');
        }

        return $response;
    }

    public function list_thread_messages($thread_id)
    {
        $response = $this->send_get_request("/threads/{$thread_id}/messages");

        if (empty($response['data'])) {
            return [];
        }

        return $response['data'];
    }

    public function runFullThread($prompt, $assistant_id, $instructions = false)
    {




        if ($instructions) {

            $thread_id = $this->create_thread($instructions, 'system');

            echo "thread created $thread_id, instructions: $instructions";

            $this->add_message($thread_id, $prompt, 'user');

        } else {
            $thread_id = $this->create_thread($prompt, 'user');
        }


        echo "thread id: $thread_id\n";
        var_dump($thread_id);

        exit;

        $run_id = $this->create_run($thread_id, $assistant_id);

        $retryCount = 0;
        $maxRetries = 10;
        $retryDelay = 5; // seconds


      exit;

        do {
            sleep($retryDelay);
            try {
                $run = $this->get_run($thread_id, $run_id);
            } catch (\Exception $e) {
                \Log::error("Error retrieving run (attempt $retryCount): " . $e->getMessage());
                if (++$retryCount > $maxRetries) {
                    throw $e;
                }
                continue;
            }

            if ($run['status'] == 'requires_action') {
                $toolExecutor = new ToolExecutor(); // Implement this class
                $outputs = $this->execute_tools($thread_id, $run_id, $toolExecutor);
                $this->submit_tool_outputs($thread_id, $run_id, $outputs);
            }
        } while (!($run['status'] == 'completed'));

        return $this->list_thread_messages($thread_id);
    }

    public function run_thread($thread_id,$assistant_id = null)
    {
        $runs = $this->list_runs($thread_id);


        if(!$assistant_id){

            $assistant_id = $this->assistant_id;
        }

        if (count($runs) > 0) {
            $last_run = $runs[0];

            if ($last_run['status'] == 'requires_action') {
                $this->has_tool_calls = true;
                $this->tool_call_id = $last_run['id'];
                return false;
            } else {
                $this->has_tool_calls = false;
                $this->tool_call_id = null;
            }
        }

        $run_id = $this->create_run($thread_id, $assistant_id);

        $retryCount = 0;
        $maxRetries = 5;
        $retryDelay = 5; // seconds

        do {
            sleep($retryDelay);
            try {
                $run = $this->get_run($thread_id, $run_id);
            } catch (\Exception $e) {
                \Log::error("Error retrieving run (attempt $retryCount): " . $e->getMessage());
                if (++$retryCount > $maxRetries) {
                    throw $e;
                }
                continue;
            }
        } while (!($run['status'] == 'completed' || $run['status'] == 'requires_action'));

        if ($run['status'] == 'requires_action') {
            $this->has_tool_calls = true;
            $this->tool_call_id = $run['id'];
            return $run['id'];
        } else if ($run['status'] == 'completed') {
            return $run['id'];
        }

        return false;
    }

    public function execute_tools($thread_id, $execution_id, $optional_object = null)
    {
        $run = $this->get_run($thread_id, $execution_id);
        $calls = $run['required_action']['submit_tool_outputs']['tool_calls'];
        $outputs = [];
        $log_entry = '';

        foreach ($calls as $call) {

            $method_name = $call['function']['name'];
            $method_args = json_decode($call['function']['arguments'], true);
            $callable = $optional_object ?
                [$optional_object, $method_name] : $method_name;

            \Log::info("OpenAIAssistant $method_name " . class_basename($optional_object) . json_encode($call). print_r($method_args,true));

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
        }

        $this->has_tool_calls = false;
        return $outputs;
    }
    public function submitToolOutputs($thread_id, $execution_id, $outputs)
    {
        return $this->send_post_request("/threads/{$thread_id}/runs/{$execution_id}/submit_tool_outputs", [
            'tool_outputs' => $outputs
        ]);


    }



    public function submit_tool_outputs($thread_id, $execution_id, $outputs)
    {
        $response = $this->send_post_request("/threads/{$thread_id}/runs/{$execution_id}/submit_tool_outputs", [
            'tool_outputs' => $outputs
        ]);

        if (empty($response['id'])) {
            throw new \Exception('Unable to submit tool outputs');
        }

        $retryCount = 0;
        $maxRetries = 5;
        $retryDelay = 5; // seconds

        do {
            sleep($retryDelay);
            try {
                $run = $this->get_run($thread_id, $response['id']);
            } catch (\Exception $e) {
                \Log::error("Error retrieving run after submit_tool_outputs (attempt $retryCount): " . $e->getMessage());
                if (++$retryCount > $maxRetries) {
                    throw $e;
                }
                continue;
            }
        } while (!($run['status'] == 'completed' || $run['status'] == 'requires_action'));

        if ($run['status'] == 'requires_action') {

            $toolExecutor = new ToolExecutor(); // Implement this class
            $outputs = $this->execute_tools($thread_id, $run['id'], $toolExecutor);
            $this->submit_tool_outputs($thread_id, $run['id'], $outputs);

            return $run['id'];
        } else if ($run['status'] == 'completed') {
            return $run['id'];
        }

        return false;
    }

    public function create_run($thread_id, $assistant_id)
    {
        $response = $this->send_post_request("/threads/{$thread_id}/runs", [
            'assistant_id' => $assistant_id,
            "tool_choice" => "auto"
        ]);

        if (empty($response['id'])) {
            throw new \Exception('Unable to create a run');
        }

        return $response['id'];
    }

    public function get_run($thread_id, $run_id)
    {
        $response = $this->send_get_request("/threads/{$thread_id}/runs/{$run_id}");

        if (empty($response['id'])) {
            throw new \Exception('Unable to retrieve the run');
        }

        return $response;
    }

    public function list_runs($thread_id)
    {

      //  echo "List runs on $thread_id\n";
        $response = $this->send_get_request("/threads/{$thread_id}/runs");
     //   var_dump($response);

        if (empty($response['data'])) {
            return [];
        }

        return $response['data'];
    }
}

