<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use App\Models\Conversation;
use App\Models\Assistant;
use Twilio\TwiML\VoiceResponse;
use Twilio\Jwt\ClientToken;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VoiceGrant;

class BareCallController extends Controller
{
    /**
     * Show the form for starting a bare call
     */
    public function showForm()
    {
        return view('webapp.bare._bare_call');
    }

    /**
     * Start a new bare call
     */
    public function startCall(Request $request)
    {

        $validated = $request->validate([
            'phone_number' => 'required|string',
            'additional_phones' => 'nullable|array',
            'additional_phones.*' => 'string',
            'room' => 'nullable|string',
            'assistant_id' => 'nullable|exists:assistants,id',
            'conversation_id' => 'nullable|exists:conversations,id',
            'pipeline_id' => 'nullable|exists:pipelines,id',
            'conversation_path_id' => 'nullable|exists:conversation_paths,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'add_monitor' => 'nullable|boolean',
            'record_call' => 'nullable|boolean'
        ]);

        
        $conversation = Conversation::create([
            'id' => \Str::uuid(),
            'title' => 'Bare Call',            
            'type' => 'phone_call', // or other type as needed
            'status' => 'active',                        
        ]);

        $room = $validated['room'] ?? uniqid('call_');

        $conversation->room = $room;          

        $conversation->addToPathState('start_call',$validated);
        $conversation->save();



        try {
            // Format main phone number
            $phoneNumber = $this->formatPhoneNumber($validated['phone_number']);
            
            // Generate room name if not provided
            
            if($validated['conversation_path_id']){

                $conversationPath = ConversationPath::find($validated['conversation_path_id']);
                $conversation->conversationPath()->associate($conversationPath);
                $conversation->save();

                $conversation->addToPathState('start_call',$validated);
                $conversation->save();

                $twilio = new \App\Services\Twilio();
                $call = $twilio->startCallFromConversation($phoneNumber, $conversation->id);
                Log::info('Started main call', [
                    'phone_number' => $phoneNumber,
                    'room' => $room,
                    'call_sid' => $call->sid
                ]);

                if (!$call) {
                    throw new \Exception('Failed to start call');
                }

               
            } else {

    // Start the main call using Twilio service
                $twilio = new \App\Services\Twilio();
                $call = $twilio->startCallFromConversation($phoneNumber, $conversation->id);
                Log::info('Started main call', [
                    'phone_number' => $phoneNumber,
                    'room' => $room,
                    'call_sid' => $call->sid
                ]);

                if (!$call) {
                    throw new \Exception('Failed to start call');
                }

            }


           

            // Get contact's phone number if contact_id is provided
            $additionalPhones = $validated['additional_phones'] ?? [];
            if (!empty($validated['contact_id'])) {
                $contact = \App\Models\Contact::find($validated['contact_id']);
                if ($contact && !in_array($contact->phone, $additionalPhones)) {
                    $additionalPhones[] = $contact->phone;
                }
            }

            // Start calls to additional numbers
            $additionalCalls = [];
            foreach ($additionalPhones as $phone) {
                $formattedPhone = $this->formatPhoneNumber($phone);
                $additionalCall = $twilio->startCallFromConversation($formattedPhone, $conversation->id);

                Log::info('Started additional call', [
                    'phone_number' => $formattedPhone,
                    'room' => $room,
                    'call_sid' => $additionalCall->sid
                ]);

                if ($additionalCall) {
                    $additionalCalls[] = $additionalCall->sid;
                }
            }

            // Build the command for the assistant process if assistant_id is provided
            $process = null;
            if (!empty($validated['assistant_id'])) {
                $basePath = config('app.base_path');
                $command = "nohup /usr/bin/php {$basePath}/artisan bare:assistant-v2 $room {$validated['assistant_id']} ";
             

                // Add logging and get PID
                $command .= " >> {$basePath}/storage/logs/bare_call_{$room}.log 2>&1 & echo $!";
                exec($command, $output);
                $pid = (int)$output[0];

                Log::info('Started bare call assistant process', [
                    'pid' => $pid,
                    'command' => $command
                ]);
            }



            Log::info('Started bare call process', [
                'phone_number' => $phoneNumber,
                'room' => $room,
                'assistant_id' => $validated['assistant_id'] ?? null,
                'conversation_id' => $validated['conversation_id'] ?? null,
                'pipeline_id' => $validated['pipeline_id'] ?? null,
                'conversation_path_id' => $validated['conversation_path_id'] ?? null,
                'contact_id' => $validated['contact_id'] ?? null,
                'additional_phones' => $additionalPhones,
                'process_id' => $process ? $process->getPid() : null,
                'call_sid' => $call->sid,
                'additional_call_sids' => $additionalCalls
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Call started successfully',
                'data' => [
                    'room' => $room,
                    'phone_number' => $phoneNumber,
                    'assistant_id' => $validated['assistant_id'] ?? null,
                    'conversation_id' => $validated['conversation_id'] ?? null,
                    'pipeline_id' => $validated['pipeline_id'] ?? null,
                    'conversation_path_id' => $validated['conversation_path_id'] ?? null,
                    'contact_id' => $validated['contact_id'] ?? null,
                    'additional_phones' => $additionalPhones,
                    'call_sid' => $call->sid,
                    'additional_call_sids' => $additionalCalls
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to start bare call', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start call: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format phone number to standard format
     */
    private function formatPhoneNumber($number)
    {
        // Remove any non-digit characters
        $number = preg_replace('/[^0-9]/', '', $number);
        
        // Add country code if not present
        if (strlen($number) === 10) {
            $number = '1' . $number;
        }
        
        return '+' . $number;
    }

    /**
     * Get the status of a call
     */
    public function getCallStatus($callId)
    {
        try {
            $twilio = new \App\Services\Twilio();
            $call = $twilio->client->calls($callId)->fetch();

            return response()->json([
                'success' => true,
                'status' => $call->status
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get call status', [
                'error' => $e->getMessage(),
                'call_id' => $callId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get call status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * End a call
     */
    public function endCall($callId)
    {
        try {
            $twilio = new \App\Services\Twilio();
            $call = $twilio->client->calls($callId)->update(['status' => 'completed']);

            return response()->json([
                'success' => true,
                'message' => 'Call ended successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to end call', [
                'error' => $e->getMessage(),
                'call_id' => $callId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to end call: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle connecting calls to the bare websocket server for an existing conversation
     */
    public function handleCall(Request $request, $conversationId)
    {
        try {


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


            // Find the conversation
            $conversation = Conversation::findOrFail($conversationId);

            $state = $conversation->path_state ?? [];
            $state['conversation_id'] = $conversationId;
            $state['twilio_call'] = $validated;
            $conversation->path_state = $state;
            $conversation->save();
            
            if (!$conversation->room) {
                throw new \Exception('No room found in conversation');
            }

            $room = $conversation->room ;

            // Use twilio-inbound URL pattern
            $url = sprintf(
                'wss://%s:%s/twilio-inbound/%s/%s',
                config('app.domain'),
                config('app.ws_port_alt'),
                $room,
                $validated['CallSid']
            );

            $twiml = new VoiceResponse();
            $connect = $twiml->connect();
            $connect->stream([
                'url' => $url,
            ]);
                        


            return response($twiml)->header('Content-Type', 'text/xml');

        } catch (\Exception $e) {
            Log::error('Failed to handle bare call', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'conversation_id' => $conversationId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to handle call: ' . $e->getMessage()
            ], 500);
        }
    }
} 