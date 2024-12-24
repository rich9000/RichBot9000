<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Twilio;
use Twilio\Rest\Client;
use App\Models\SmsMessage;

use App\Models\User;


class SmsController extends Controller
{



    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            // Return all SMS messages as JSON
            $smsMessages = SmsMessage::with('user')->orderBy('created_at', 'desc')->get();
            return response()->json($smsMessages);
        }

        // Return the view
        return view('sms.index');
    }





    public function handleReply(Request $request)
    {
        $messageBody = $request->input('Body');
        $from = $request->input('From'); // e.g., "+1234567890"
        $to = $request->input('To');     // Your Twilio number

        \Log::error("SMS From: $from To: $to $messageBody");


        // Normalize the phone number
        $fromNumber = preg_replace('/[^+\d]/', '', $from);

        $fromNumber = substr($fromNumber,-10);




        \Log::error("SMS From: $from / $fromNumber To: $to $messageBody");

        // Find or create the user
        $user = User::where('phone_number', $fromNumber)->first();


        // Define opt-in and opt-out keywords
        $optInKeywords = ['START', 'YES', 'OPTIN', 'SUBSCRIBE'];
        $optOutKeywords = ['STOP', 'UNSUBSCRIBE', 'OPTOUT', 'QUIT'];

        $messageUpper = strtoupper(trim($messageBody));

        if (in_array($messageUpper, $optInKeywords)) {
            $user->update(['receive_sms' => true]);
            $replyMessage = "You have successfully opted in to receive SMS notifications.";
        } elseif (in_array($messageUpper, $optOutKeywords)) {
            $user->update(['receive_sms' => false]);
            $replyMessage = "You have successfully opted out of SMS notifications.";
        } else {
            // Handle other messages or commands
            $replyMessage = "Thank you for your message.";
        }

        // Log the incoming message
        SmsMessage::create([
            'user_id'     => $user->id,
            'from_number' => $fromNumber,
            'to_number'   => $to,
            'body'        => $messageBody,
            'direction'   => 'incoming',
            'status'      => null, // Status can be updated later if needed
        ]);

        // Send a reply
        $sid          = env('TWILIO_SID');
        $token        = env('TWILIO_TOKEN');
        $twilioNumber = $to;

        $client = new Client($sid, $token);

        try {
            $message = $client->messages->create(
                $fromNumber,
                [
                    'from' => $twilioNumber,
                    'body' => $replyMessage,
                ]
            );

            // Log the outgoing message
            SmsMessage::create([
                'user_id'     => $user->id,
                'from_number' => $twilioNumber,
                'to_number'   => $fromNumber,
                'body'        => $replyMessage,
                'direction'   => 'outgoing',
                'status'      => $message->status ?? null, // Capture status if available
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send message: ' . $e->getMessage());
        }

        // Return an empty response to Twilio
        return response('', 200)->header('Content-Type', 'text/xml');
    }
}
