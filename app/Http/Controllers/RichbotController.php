<?php

namespace App\Http\Controllers;

use App\Services\ConversationManager;
use App\Services\OllamaApiClient;
use Illuminate\Http\Request;
use App\Models\Conversation;

class RichbotController extends Controller
{
    //
    public function show(){

        $ollamaClient = new OllamaApiClient();
        $cm = $conversationManager = new ConversationManager();

        $assistantType = 'gate_keeper';

        $title = 'New Conversation!';

        // Define default tools and system messages based on assistant type
        $tools = $ollamaClient->getToolsForAssistant($assistantType);
        $systemMessages = $ollamaClient->getSystemMessagesForAssistant($assistantType);

        try {
            $conversation = Conversation::create([
                'title' => $title,
                'assistant_type' => $assistantType,
                'active_tools' => $tools,
                'system_messages' => $systemMessages,
                'model'=>'llama3.2'
            ]);

            // Optionally, add a system message for the assistant's introduction
            $conversation->addMessage('system', "Assistant of type '{$assistantType}' initialized.");

            $txt = "Tools Available:\n";
            foreach ($conversation->assistant->tools as $tool) {
                $txt .= "{$tool->name} {$tool->description}\n";
            }
            $conversation->addMessage('system', $txt);



            $conversation_id = $conversation->id;

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create conversation: ' . $e->getMessage(),
            ], 500);
        }

        return view('richbot.richbot',[ 'conversation_id' => $conversation_id ]);

    }
    public function post(Request $request){

        $text = $request->input('text');
        $conversation_id = $request->input('conversation_id');
        $conversation = Conversation::find($conversation_id);
        $conversation->addMessage('user',$text);

        $response = $conversation->assistant->askPrompt($text);

        return response()->json(['message' => 'Command sent successfully.','prompt'=>$text,'conversation_id'=>$conversation_id,'response'=>$response], 200);

    }




}
