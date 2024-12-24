<?php

namespace App\Services;

use Exception;

class OllamaApiClient
{
    public $baseUrl;
    public $curlOptions;

    /**
     * Constructor for OllamaApiClient.
     *
     * @param string $baseUrl The base URL of the API.
     */
    public function __construct($baseUrl = 'http://192.168.0.104:11434')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ];
    }

    /**
     * Sends an HTTP request to the API.
     *
     * @param string        $method         The HTTP method (GET, POST, etc.).
     * @param string        $endpoint       The API endpoint.
     * @param array|null    $data           The data to send in the request body.
     * @param array         $headers        Additional HTTP headers.
     * @param callable|null $streamCallback A callback function to handle streaming responses.
     *
     * @return mixed The decoded JSON response or true if streaming.
     *
     * @throws Exception If an error occurs during the request.
     */
    private function sendRequest($method, $endpoint, $data = null, $headers = [], $streamCallback = null)
    {
        $url = $this->baseUrl . $endpoint;
        $ch  = curl_init($url);


       // echo "$url\n";
        dump($data);

        $options = $this->curlOptions;
        $options[CURLOPT_CUSTOMREQUEST] = $method;

        if (!empty($headers)) {
            $options[CURLOPT_HTTPHEADER] = array_merge($this->curlOptions[CURLOPT_HTTPHEADER], $headers);
        }

        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            if ($data !== null) {
                $jsonData = json_encode($data);


                //dd($jsonData);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Failed to encode JSON data: ' . json_last_error_msg());
                }
                $options[CURLOPT_POSTFIELDS] = $jsonData;
            }
        }

        if ($streamCallback !== null) {
            $options[CURLOPT_WRITEFUNCTION] = function ($ch, $chunk) use ($streamCallback) {
                $streamCallback($chunk);
                return strlen($chunk);
            };
        }
     //   dump($options);
        curl_setopt_array($ch, $options);


     //   dump($options);


        $response    = curl_exec($ch);

    //    dump($response);

        $error       = curl_error($ch);
        $errno       = curl_errno($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($errno) {
            throw new Exception("cURL error ({$errno}): {$error}");
        }

        if ($statusCode >= 400) {
            // Include raw response in the exception for better debugging
            throw new Exception("HTTP error ({$statusCode}): {$response}");
        }

        if ($streamCallback !== null) {
            // Streaming responses are handled via the callback.
            return true;
        } else {
            $decodedResponse = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Include raw response in the exception for better debugging
                throw new Exception('Failed to parse JSON response: ' . json_last_error_msg() . '. Raw response: ' . $response);
            }
            return $decodedResponse;
        }
    }

    /**
     * Generate a completion for a given prompt.
     *
     * @param string        $model          The model name (required).
     * @param string|null   $prompt         The prompt to generate a response for.
     * @param string|null   $suffix         The text after the model response.
     * @param array|null    $images         A list of base64-encoded images.
     * @param array|null    $options        Additional model parameters.
     * @param string|null   $system         System message to override what's defined in the Modelfile.
     * @param string|null   $template       The prompt template to use.
     * @param mixed|null    $context        The context parameter returned from a previous request.
     * @param bool|null     $stream         If false, the response will be returned as a single response object.
     * @param bool|null     $raw            If true, no formatting will be applied to the prompt.
     * @param string|null   $keep_alive     Controls how long the model will stay loaded into memory.
     * @param callable|null $streamCallback A callback function to handle streaming responses.
     *
     * @return mixed The API response.
     *
     * @throws Exception If an error occurs during the request.
     */
    public function generateCompletion(
        $model,
        $prompt = null,
        $suffix = null,
        array $images = null,
        array $options = null,
        $system = null,
        $template = null,
        $context = null,
        $stream = null,
        $raw = null,
        $keep_alive = null,
        callable $streamCallback = null
    ) {
        $params = [
            'model' => $model,
        ];

        if ($prompt !== null) {
            $params['prompt'] = $prompt;
        }
        if ($suffix !== null) {
            $params['suffix'] = $suffix;
        }
        if ($images !== null) {
            $params['images'] = $images;
        }
        if ($options !== null) {
            $params['options'] = $options;
        }
        if ($system !== null) {
            $params['system'] = $system;
        }
        if ($template !== null) {
            $params['template'] = $template;
        }
        if ($context !== null) {
            $params['context'] = $context;
        }
        if ($stream !== null) {
            $params['stream'] = $stream;
        }
        if ($raw !== null) {
            $params['raw'] = $raw;
        }
        if ($keep_alive !== null) {
            $params['keep_alive'] = $keep_alive;
        }


        \Log::info("/api/generate ".var_export($params,true));



        return $this->sendRequest('POST', '/api/generate', $params, [], $streamCallback);
    }

    /**
     * Generate a chat completion.
     *
     * @param string        $model          The model name (required).
     * @param array|null    $messages       The messages of the chat.
     * @param array|null    $tools          Tools for the model to use if supported.
     * @param array|null    $options        Additional model parameters.
     * @param bool|null     $stream         If false, the response will be returned as a single response object.
     * @param string|null   $keep_alive     Controls how long the model will stay loaded into memory.
     * @param callable|null $streamCallback A callback function to handle streaming responses.
     *
     * @return mixed The API response.
     *
     * @throws Exception If an error occurs during the request.
     */
    public function generateChatCompletion(
        $model,
        array $messages = null,
        array $tools = null,
        array $options = null,
        $stream = false,
        $keep_alive = null,
        callable $streamCallback = null
    ) {


        $params = [
            'model' => $model,
        ];

        if ($messages !== null) {
            $params['messages'] = $messages;
        }
        if ($tools !== null) {
            $params['tools'] = $tools;

        }
        if ($options !== null) {
            $params['options'] = $options;
        }
        if ($stream !== null) {
            $params['stream'] = $stream;
        }
        if ($keep_alive !== null) {
           // $params['keep_alive'] = $keep_alive;
        }

        $json = json_decode(  ' [{
  "name": "storeCleanedLog",
  "description": "Judges text as appropriate for processing.",
  "strict": false,
  "parameters": {
    "type": "object",
    "properties": {
      "call_log": {
        "type": "string",
        "description": "Text of the phone call log."
      }
    },
    "required": [
      "call_log"
    ]
  }
}]');
       // $params['tools'] = $json;
        $params['raw'] = true;


       // dd($params);


        return $this->sendRequest('POST', '/api/chat', $params, [], $streamCallback);
    }

    /**
     * Create a model.
     *
     * @param string      $name      The name of the model to create.
     * @param string|null $modelfile The contents of the Modelfile.
     * @param string|null $path      The path to the Modelfile.
     * @param bool|null   $stream    If false, the response will be returned as a single response object.
     *
     * @return mixed The API response.
     *
     * @throws Exception If an error occurs during the request.
     */
    public function createModel($name, $modelfile = null, $path = null, $stream = null)
    {
        $params = [
            'name' => $name,
        ];

        if ($modelfile !== null) {
            $params['modelfile'] = $modelfile;
        }
        if ($path !== null) {
            $params['path'] = $path;
        }
        if ($stream !== null) {
            $params['stream'] = $stream;
        }

        return $this->sendRequest('POST', '/api/create', $params);
    }

    /**
     * List local models.
     *
     * @return array The list of models.
     *
     * @throws Exception If an error occurs during the request.
     */
    public function listLocalModels()
    {
        return $this->sendRequest('GET', '/api/tags');
    }

    /**
     * Show model information.
     *
     * @param string    $name    The name of the model to show.
     * @param bool|null $verbose If true, returns full data for verbose response fields.
     *
     * @return array The model information.
     *
     * @throws Exception If an error occurs during the request.
     */
    public function showModelInformation($name, $verbose = null)
    {
        $params = ['name' => $name];

        if ($verbose !== null) {
            $params['verbose'] = $verbose;
        }

        return $this->sendRequest('POST', '/api/show', $params);
    }

    /**
     * Copy a model.
     *
     * @param string $source      The source model name.
     * @param string $destination The destination model name.
     *
     * @return mixed The API response.
     *
     * @throws Exception If an error occurs during the request.
     */
    public function copyModel($source, $destination)
    {
        $params = [
            'source'      => $source,
            'destination' => $destination,
        ];

        return $this->sendRequest('POST', '/api/copy', $params);
    }

    /**
     * Delete a model.
     *
     * @param string $name The name of the model to delete.
     *
     * @return mixed The API response.
     *
     * @throws Exception If an error occurs during the request.
     */
    public function deleteModel($name)
    {
        $params = ['name' => $name];

        return $this->sendRequest('DELETE', '/api/delete', $params);
    }

    /**
     * Pull a model.
     *
     * @param string        $name           The name of the model to pull.
     * @param bool|null     $insecure       Allow insecure connections to the library.
     * @param bool|null     $stream         If false, the response will be returned as a single response object.
     * @param callable|null $streamCallback A callback function to handle streaming responses.
     *
     * @return mixed The API response.
     *
     * @throws Exception If an error occurs during the request.
     */
    public function pullModel($name, $insecure = null, $stream = null, callable $streamCallback = null)
    {
        $params = ['name' => $name];

        if ($insecure !== null) {
            $params['insecure'] = $insecure;
        }
        if ($stream !== null) {
            $params['stream'] = $stream;
        }

        return $this->sendRequest('POST', '/api/pull', $params, [], $streamCallback);
    }

    /**
     * Push a model.
     *
     * @param string        $name           The name of the model to push.
     * @param bool|null     $insecure       Allow insecure connections to the library.
     * @param bool|null     $stream         If false, the response will be returned as a single response object.
     * @param callable|null $streamCallback A callback function to handle streaming responses.
     *
     * @return mixed The API response.
     *
     * @throws Exception If an error occurs during the request.
     */
    public function pushModel($name, $insecure = null, $stream = null, callable $streamCallback = null)
    {
        $params = ['name' => $name];

        if ($insecure !== null) {
            $params['insecure'] = $insecure;
        }
        if ($stream !== null) {
            $params['stream'] = $stream;
        }

        return $this->sendRequest('POST', '/api/push', $params, [], $streamCallback);
    }

    /**
     * Generate embeddings.
     *
     * @param string       $model      The name of the model to generate embeddings from.
     * @param mixed        $input      Text or list of text to generate embeddings for.
     * @param bool|null    $truncate   Truncate the input if it exceeds context length.
     * @param array|null   $options    Additional model parameters.
     * @param string|null  $keep_alive Controls how long the model will stay loaded into memory.
     *
     * @return array The embeddings.
     *
     * @throws Exception If an error occurs during the request.
     */
    public function generateEmbeddings($model, $input, $truncate = null, array $options = null, $keep_alive = null)
    {
        $params = [
            'model' => $model,
            'input' => $input,
        ];

        if ($truncate !== null) {
            $params['truncate'] = $truncate;
        }
        if ($options !== null) {
            $params['options'] = $options;
        }
        if ($keep_alive !== null) {
            $params['keep_alive'] = $keep_alive;
        }

        return $this->sendRequest('POST', '/api/embed', $params);
    }

    /**
     * Generate an image from a prompt.
     *
     * @param string        $model          The model name (required).
     * @param string        $prompt         The prompt to generate an image for.
     * @param array|null    $options        Additional model parameters.
     * @param bool|null     $stream         If false, the response will be returned as a single response object.
     * @param callable|null $streamCallback A callback function to handle streaming responses.
     *
     * @return mixed The API response.
     *
     * @throws Exception If an error occurs during the request.
     */
    public function generateImage(
        $model,
        $prompt,
        array $options = null,
        $stream = false,
        callable $streamCallback = null
    ) {
        $params = [
            'model'  => $model,
            'prompt' => $prompt,
        ];

        if ($options !== null) {
            $params['options'] = $options;
        }
        if ($stream !== null) {
            $params['stream'] = $stream;
        }

        return $this->sendRequest('POST', '/api/generate', $params, [], $streamCallback);
    }

    /**
     * List running models.
     *
     * @return array The list of running models.
     *
     * @throws Exception If an error occurs during the request.
     */
    public function listRunningModels()
    {
        return $this->sendRequest('GET', '/api/ps');
    }

    function getToolsForAssistant($assistantType)
    {
        switch ($assistantType) {
            case 'appointment_keeper':
                return [ 'update_task', 'delete_task', 'list_tasks'];
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
    function getSystemMessagesForAssistant($assistantType)
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



}
