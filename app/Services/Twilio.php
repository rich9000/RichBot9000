<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use App\Models\AssistantFunction;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;



class Twilio
{


    var $sid;
    var $token;
    var $twilioNumber;
    var $targetNumber;

    function __construct($targetNumber = null, $sid = null, $token = null, $twilioNumber = null)
    {

        $this->sid = $sid ?? env('TWILIO_SID');
        $this->token = $token ?? env('TWILIO_TOKEN');
        $this->twilioNumber = $twilioNumber ?? env('TWILIO_FROM');
        $this->targetNumber = $targetNumber;

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




}
