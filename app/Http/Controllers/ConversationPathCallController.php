<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ConversationPathService;
use Illuminate\Support\Facades\Log;
use Twilio\TwiML\VoiceResponse;
use Twilio\Jwt\ClientToken;
use Twilio\Jwt\AccessToken;
use App\Models\User;


class ConversationPathCallController extends Controller
{
    protected $service;

    public function __construct(ConversationPathService $service)
    {
        $this->service = $service;
    }

    /**
     * Start a new phone call conversation for a given conversation path ID.
     * POST /api/conversation-path-call/start/{conversationPathId}
     */
    public function startCall($conversationPathId, Request $request)
    {
        // Validate all possible Twilio variables as nullable, except required ones
        $validated = $request->validate([
            'From' => 'required|string',
            'To' => 'required|string',
            'CallSid' => 'required|string',
            //'Called' => 'nullable|string',
            'ToState' => 'nullable|string',
            //'CallerCountry' => 'nullable|string',
            'Direction' => 'nullable|string',
            //'CallerState' => 'nullable|string',
            'ToZip' => 'nullable|string',
            'ApiVersion' => 'nullable|string',
            //'CalledZip' => 'nullable|string',
            //'CalledCity' => 'nullable|string',
            //'CallStatus' => 'nullable|string',
            'FromCountry' => 'nullable|string',
            //'CallerCity' => 'nullable|string',
            'ToCity' => 'nullable|string',
            'FromCity' => 'nullable|string',
            //'CalledCountry' => 'nullable|string',
            //'Caller' => 'nullable|string',
            'FromZip' => 'nullable|string',
            'FromState' => 'nullable|string',
            'StirVerstat' => 'nullable|string',
            'CallToken' => 'nullable|string',
            'AccountSid' => 'nullable|string',
        ]);

        $path_state = ['twilio_call'=>$validated];

        //log the request
        Log::info('[ConversationPathCallController]: startCall', [
            'conversationPathId' => $conversationPathId,
            'request' => $validated
        ]);

        

            // Remove + prefix if present and format the number
            $from_formatted = preg_replace('/^\+?1?(\d{3})(\d{3})(\d{4})$/', '$1-$2-$3', $validated['From']);

            $formatted_for_richbot = str_replace('-','',$from_formatted);

            $users = User::where('phone_number',$formatted_for_richbot)->get();

            if($users->count() > 1) {
                Log::info('[ConversationPathCallController]: too many users found for phone number taking first one', [
                    'conversationPathId' => $conversationPathId,
                    'request' => $validated
                ]);
            } 

            if($users->count() == 0) {
                Log::info('[ConversationPathCallController]: no users found for phone number', [
                    'conversationPathId' => $conversationPathId,
                    'request' => $validated
                ]);

            }

            $richbot_user = $users->first();

            if($richbot_user) {
                $path_state['richbot_user_id'] = $richbot_user->id;
                $path_state['richbot_user'] = $richbot_user;
            }

        

        $conversation = $this->service->startCallConversation($conversationPathId, $path_state);
        
        $path = $conversation->conversationPath;
        $nodes = $path ? $path->nodes : [];
        $currentNode = $nodes[0] ?? null;

        $conversation->current_node_index = 1;
        $conversation->save();


        //twiml redirect to the continue call
        $twiml = new VoiceResponse();
       // $twiml->say('Redirecting to the next node');
        $twiml->redirect('/api/conversation-path-call/continue/' . $conversation->id);
        
        return response($twiml)->header('Content-Type', 'text/xml');

    }

    
    /**
     * Continue a phone call conversation by conversation_id.
     * POST /api/conversation-path-call/continue/{conversationId}
     */
    public function continueCall($conversationId, Request $request)
    {

        Log::info('[ConversationPathCallController]: continueCall', [
            'conversationId' => $conversationId,
            //'request' => $request->all()
        ]);

        return $this->service->continueCallConversation($conversationId, $request);
        
    }

    
} 