<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use App\Models\AssistantFunction;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;
use App\Models\Conversation;


class Twilio
{


    var $sid;
    var $token;
    var $twilioNumber;
    var $targetNumber;
    public $client;

    function __construct($targetNumber = null, $sid = null, $token = null, $twilioNumber = '7853699633')
    {

        $this->sid = $sid ?? env('TWILIO_SID');
        $this->token = $token ?? env('TWILIO_TOKEN');
        $this->twilioNumber = $twilioNumber ?? env('TWILIO_FROM');
        $this->targetNumber = $targetNumber;
        $this->client = new TwilioClient($this->sid, $this->token);

    }

    function getTwilioBalance($sid = null, $token = null){

        $sid = $sid ?? $this->sid;
        $token = $token ?? $this->token;

        $endpoint = "https://api.twilio.com/2010-04-01/Accounts/$sid/Balance.json";
// Define the Guzzle Client
        $client = new Client();
        $response = $client->get($endpoint, [
            'auth' => [
                $sid,
                $token
            ]
        ]);

        $info  = json_decode($response->getBody(),true);
        return $info['balance'];

        //var_dump($body);

    }

    function sendTwilioText($msg, $targetNumber = null, $sid = null, $token = null, $twilioNumber = null)
    {
        $sid = $sid ?? $this->sid;
        $token = $token ?? $this->token;
        $twilioNumber = $twilioNumber ?? $this->twilioNumber;
        $targetNumber = $targetNumber ?? $this->targetNumber;

        try {
            $client = new TwilioClient($sid, $token);

            Log::info("Sending message: $msg to $targetNumber from $twilioNumber");

            $message = $client->messages->create(
                $targetNumber,
                [
                    'from' => $twilioNumber,
                    'body' => $msg
                ]
            );

            Log::info("Message sent successfully!");

            return $message;
        } catch (RequestException $e) {
            Log::error("Failed to send message: " . $e->getMessage());
            return false;
        }
    }



    function startCallFromConversation($targetNumber, $conversationId){

        //$conversation = Conversation::find($conversationId);
      
       
        //log the request
        Log::info('[Twilio]: startCallFromConversation', [
            'conversationId' => $conversationId,
            'targetNumber' => $targetNumber,
        ]);

        


        try {
            $client = new TwilioClient($this->sid, $this->token);

            Log::info("[Twilio][startCallFromConversation][Starting call to $targetNumber from $this->twilioNumber] ", [
                'conversationId' => $conversationId,
                'targetNumber' => $targetNumber,
                'twilioNumber' => $this->twilioNumber
            ]);

            $call = $client->calls->create(
                $targetNumber,
                $this->twilioNumber,
                ["url" => "'.config('app.url')."/api/bare/call/conversation/{$conversationId}/handle"]
            );

            Log::info("Call started successfully!", [
                'call' => $call,
                'conversationId' => $conversationId
            ]);

            return $call;
        } catch (\Exception $e) {
            Log::error("Failed to start call: " . $e->getMessage());
            return false;
        }




    }

    /**
     * Start a phone call using Twilio
     * 
     * @param string $targetNumber The phone number to call
     * @param string $room The room name for the call
     * @param string|null $sid Optional Twilio SID
     * @param string|null $token Optional Twilio token
     * @param string|null $twilioNumber Optional Twilio phone number
     * @return \Twilio\Rest\Api\V2010\Account\CallInstance|false
     */
    function startCall($targetNumber = null, $room = null, $sid = null, $token = null, $twilioNumber = null)
    {
        $sid = $sid ?? $this->sid;
        $token = $token ?? $this->token;
        $twilioNumber = $twilioNumber ?? $this->twilioNumber;
        $targetNumber = $targetNumber ?? $this->targetNumber;
        $room = $room ?? uniqid('call_');

        
        //log the request
        Log::info('[ConversationPathCallController]: startCall', [
            'conversationPathId' => $conversationPathId,
            'request' => $validated
        ]);

        

        $path_state = ['twilio_incoming_call'=>$validated];

        try {
            $client = new TwilioClient($sid, $token);

            Log::info("Starting call to $targetNumber from $twilioNumber in room $room");

            $call = $client->calls->create(
                $targetNumber,
                $twilioNumber,
                ["url" => "'.config('app.url').'/api/conversation-path-call/continue/{conversationId}"]
            );

            Log::info("Call started successfully!", [
                'call_sid' => $call->sid,
                'room' => $room
            ]);

            return $call;
        } catch (\Exception $e) {
            Log::error("Failed to start call: " . $e->getMessage());
            return false;
        }
    }




}
