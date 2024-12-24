<?php

namespace App\Services;

use App\Models\Assistant;
use App\Models\Conversation;
use App\Models\Message;
use Exception;
use ArdaGnsrn\Ollama\Ollama;
use Illuminate\Support\Facades\Log;


class ConversationManager
{
    protected $ollamaApiClient;
    protected $toolExecutor;
    protected $ollamaClient;

    /**
     * Constructor to inject dependencies.
     *
     * @param OllamaApiClient $ollamaApiClient
     * @param ToolExecutor    $toolExecutor
     */
    public function __construct()
    {
        $this->ollamaApiClient = new OllamaApiClient();
        $this->toolExecutor = new ToolExecutor();
        $this->ollamaClient =Ollama::client('http://192.168.0.104:11434');
    }



    public function add_context_message($arguments): array
    {


        Log::info(json_encode($arguments));


        return [
            'success' => true,
            'data' => $arguments,
        ];

        //return [
        //    'success' => false,
        //    'error' => $e->getMessage(),
        //];

    }
    public function submit_context($arguments): array
    {

        Log::info(json_encode($arguments));


        return [
            'success' => true,
            'data' => $arguments,
        ];

        //return [
        //    'success' => false,
        //    'error' => $e->getMessage(),
        //];

    }







    /**
     * Retrieve all messages for a specific conversation.
     *
     * @param int $conversationId
     * @return array
     * @throws Exception
     */
    public function getConversationMessages($conversationId)
    {
        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            throw new Exception('Conversation not found');
        }

        $messages = $conversation->messages()->orderBy('created_at')->get();

        // Format messages for Ollama API
        $formattedMessages = $messages->map(function ($message) {
            return [
                'role' => $message->role, // 'user', 'assistant', or 'system'
                'content' => $message->content,
            ];
        })->toArray();

        return $formattedMessages;
    }

    /**
     * Add a message to a conversation.
     *
     * @param int    $conversationId
     * @param string $role          'user', 'assistant', or 'system'
     * @param string $content
     * @return Message
     * @throws Exception
     */
    public function addMessage($conversationId, $role, $content)
    {
        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            throw new Exception('Conversation not found');
        }

        $message = Message::create([
            'conversation_id' => $conversationId,
            'role'            => $role,
            'content'         => $content,
        ]);

        return $message;
    }

    /**
     * Send a user message and get the assistant's response from Ollama.
     *
     * @param int    $conversationId
     * @param string $userMessage
     * @return string
     */
    public function ollamaSendMessage($conversationId, $userMessage)
    {
        try {
            // Retrieve the conversation's messages

            $conversation = Conversation::find($conversationId);

            // Add the user's message
            $this->addMessage($conversationId, 'user', $userMessage);

            $messages = $this->getConversationMessages($conversationId);

            $client =Ollama::client('http://192.168.0.104:11434');

            $assistant = Assistant::where('name',$conversation->assistant_type)->with('tools')->first();

            if(!$assistant){

                throw new Exception('Assistant not found'.$conversation->assistant_type );
            }

            $tools = $assistant->generateTools()->toArray();

            $response = $client->chat()->create([
                'model' => $conversation->model,
                'messages' => $messages,
                'tools' => $tools,
            ]);

            dd($response);

            // Prepare the request to Ollama
            $response = $this->ollamaApiClient->generateChatCompletion(
                'llama3.2', // Replace with your actual Ollama model name
                $messages,
                //$tools,
                 // Passing conversation ID as context if needed
            );

            dd($response);


            // Extract the assistant's reply
            if (isset($response['message'])) {
                $assistantMessage = $response['message']['content'];
                $role = $response['message']['role'];
            } else {
                $role = 'assistant';
                $assistantMessage = 'Sorry, I could not process your request.';
            }


            //dump($assistantMessage);

            // Add the assistant's message to the conversation
            $this->addMessage($conversationId, $role, $assistantMessage);

            // Check for tool invocation in the assistant's message
            $toolInvocation = $this->parseToolInvocation($assistantMessage);

            if ($toolInvocation) {
                $tool = $toolInvocation['tool'];
                $arguments = $toolInvocation['arguments'];

                // Execute the tool using ToolExecutor
                if (method_exists($this->toolExecutor, $tool)) {
                    $toolResult = $this->toolExecutor->{$tool}($arguments);

                    if (isset($toolResult['success']) && $toolResult['success']) {
                        $toolResponse = $toolResult['data'] ?? $toolResult['message'];

                        // Add the tool's response to the conversation
                        $this->addMessage($conversationId, 'assistant', $toolResponse);

                        // Optionally, send the tool's response back to the assistant for further processing
                        // For example, to have the assistant continue the conversation based on the tool's output
                    } else {
                        $error = $toolResult['error'] ?? 'Unknown error occurred while executing the tool.';
                        $this->addMessage($conversationId, 'assistant', "Error executing tool: {$error}");
                    }
                } else {
                    $this->addMessage($conversationId, 'assistant', "Requested tool '{$tool}' does not exist.");
                }
            }

            return $assistantMessage;

        } catch (Exception $e) {
            // Log the error for debugging (ensure you have logging set up)
            \Log::error($e->getMessage());

            return 'An error occurred while processing your request. '.$e->getMessage();
        }
    }

    /**
     * Parse tool invocation from the assistant's message.
     *
     * Define a convention for tool invocation within the assistant's messages.
     * For example, JSON commands or specific keywords.
     *
     * @param string $message
     * @return array|null
     */
    protected function parseToolInvocation($message)
    {
        // Example convention: "Tool: tool_name; Arguments: {\"key\": \"value\"}"
        $pattern = '/Tool:\s*(\w+);\s*Arguments:\s*(\{.*\})/i';
        if (preg_match($pattern, $message, $matches)) {
            $tool = $matches[1];
            $arguments = json_decode($matches[2], true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return ['tool' => $tool, 'arguments' => $arguments];
            }
        }

        return null;
    }

    /**
     * Get tools assigned to a conversation.
     *
     * @param Conversation $conversation
     * @return array
     */
    protected function getToolsForConversation(Conversation $conversation)
    {
        return $conversation->active_tools ?? [];
    }

    /**
     * Get system messages for a conversation.
     *
     * @param Conversation $conversation
     * @return string
     */
    protected function getSystemMessagesForConversation(Conversation $conversation)
    {
        return $conversation->system_message ?? 'You are an AI assistant ready to help.';
    }
}
