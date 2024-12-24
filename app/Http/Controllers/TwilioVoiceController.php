<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;
use Twilio\Jwt\ClientToken;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VoiceGrant;
use Illuminate\Support\Facades\Log;
class TwilioVoiceController extends Controller
{
    /**
     * Handle incoming voice calls
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleCall(Request $request)
    {
        try {
            $response = new VoiceResponse();
            Log::info('Incoming call', [
                'from' => $request->input('From'),
                'to' => $request->input('To'),
                'callSid' => $request->input('CallSid')
            ]);

           // dump($request->all());
           // dump($request->all());

        //    Log::info(__FUNCTION__.':'.__FILE__.':'.__LINE__, 'Request:', $request->all());
            $url = 'wss://richbot9000.com:9501?to=' . urlencode($request->input('To')) . '&from=' . urlencode($request->input('From'));
            
            Log::info(__FUNCTION__.':'.__FILE__.':'.__LINE__. ' URL: '. $url);
            // First, establish the stream connection
            $connect = $response->connect();
            $connect->stream([
                'url' => $url,
                'statusCallback' => route('stream-status'),
                'statusCallbackMethod' => 'POST'
            ]);

            // Then gather input AFTER the stream is connected
            $gather = $response->gather([
                'numDigits' => 1,
                'action' => route('menu-response'),
                'method' => 'POST',
                'timeout' => 10,
                'numAttempts' => 3
            ]);

            $gather->say(
                'Thank you for calling Rich Bot 9000. ' .
                'Press 1 to talk to Rich Bot 9000. ' .
                'Press 2 to call Rich Carroll. ' .
                'Press 3 to call Katie Shaffee. ' .
                
                'Press 4 to leave a voicemail.',
                ['voice' => 'alice', 'language' => 'en-US']
            );

            // Remove the play and redirect as they might interfere with the stream
            return response($response)
                ->header('Content-Type', 'text/xml');
        } catch (\Exception $e) {
            Log::error('Error in handleCall: ' . $e->getMessage());
            $response = new VoiceResponse();
            $response->say('We apologize, but an error has occurred. Please try your call again.');
            return response($response)->header('Content-Type', 'text/xml');
        }
    }

    public function handleMenuResponse(Request $request)
    {
        $response = new VoiceResponse();
        $digits = $request->input('Digits');
       // $attempts = $request->session()->get('attempts', 0);

        switch ($digits) {
            case '1':
                $response = new VoiceResponse();
                
                // First inform the caller
                $response->say(
                    'Connecting you to Rich Bot 9000. This call may be recorded for quality assurance.', 
                    ['voice' => 'alice']
                );


                /*
  // If you need recording, add it after the stream
                $response->record([
                    'action' => route('handle-recording'),
                    'transcribe' => true,
                    'transcribeCallback' => route('handle-transcription')
                ]);
*/



                // Then establish the stream connection
                $connect = $response->connect();
                $connect->stream([
                    'url' => 'wss://richbot9000.com:9501?to=' . urlencode($request->input('To')) . '&from=' . urlencode($request->input('From')),
                ]);

              
                
                break;

            case '2':
                // Call Rich Carroll with status callback
                $response->say('Connecting you to Rich Carroll');
                $response->dial('785-288-1144', [
                    'timeout' => 30,
                    'record' => 'record-from-answer',
                    'recordingStatusCallback' => route('recording-status'),
                    'answerOnBridge' => true
                ]);
                break;

                case '3':
                    // Call Rich Carroll with status callback
                    $response->say('Connecting you to Katie Peregrin Falcon Larry Harry Potter Shaffer');
                    $response->dial('816-383-4066', [
                        'timeout' => 30,
                        'record' => 'record-from-answer',
                        'recordingStatusCallback' => route('recording-status'),
                        'answerOnBridge' => true
                    ]);
                    break;
    

            case '4':
                // Voicemail option
                $response->say('Please leave your message after the beep. Press pound when finished.');
                $response->record([
                    'action' => route('handle-voicemail'),
                    'transcribe' => true,
                    'maxLength' => 300,
                    'finishOnKey' => '#',
                    'transcribeCallback' => route('handle-transcription'),
                    'playBeep' => true
                ]);
                break;

            default:
                if ($attempts >= 3) {
                    $response->say('Too many invalid attempts. Please call back later.');
                    $response->hangup();
                } else {
                    $request->session()->put('attempts', $attempts + 1);
                    $response->say('Invalid input received. Please try again.');
                    $response->redirect(url('/voice'));
                }
                break;
        }

        return response($response)
            ->header('Content-Type', 'text/xml')
            ->header('Cache-Control', 'no-cache');
    }

    public function handleRecording(Request $request)
    {
        Log::info('Recording URL: ' . $request->input('RecordingUrl'));
        // Store recording URL in database or process as needed
        
        $response = new VoiceResponse();
        $response->say('Thank you for your call. Goodbye!');
        return response($response)->header('Content-Type', 'text/xml');
    }

    public function handleTranscription(Request $request)
    {
        Log::info('Transcription: ' . $request->input('TranscriptionText'));
        // Store transcription in database or process as needed
        
        // Could send email/notification with transcription
        // Mail::to('admin@richbot9000.com')->send(new TranscriptionNotification($request->all()));
    }

    public function handleVoicemail(Request $request)
    {
        $response = new VoiceResponse();
        $response->say('Thank you for your message. We will get back to you soon.');
        
        // Store voicemail metadata
        Log::info('Voicemail received', [
            'duration' => $request->input('RecordingDuration'),
            'url' => $request->input('RecordingUrl')
        ]);
        
        return response($response)->header('Content-Type', 'text/xml');
    }

    public function techSupportOptions(Request $request)
    {
        $response = new VoiceResponse();
        $digits = $request->input('Digits');

        switch ($digits) {
            case '1':
                $response->say('Connecting you to technical support');
                $response->dial('800-TECH-SUPPORT');
                break;
            case '2':
                // Send SMS with support information
                // You'll need to implement the actual SMS sending logic
                $response->say('Support information has been sent to your phone number');
                break;
            case '3':
                $response->redirect(url('/voice'));
                break;
        }

        return response($response)->header('Content-Type', 'text/xml');
    }

    // Add a new method to generate Twilio Client tokens
    public function generateToken()
    {
        $accountSid = config('services.twilio.sid');
        $authToken = config('services.twilio.token');

        // Create access token
        $token = new AccessToken(
            $accountSid,
            $accountSid, // Using Account SID as API Key SID
            $authToken,
            3600,
            'user-' . uniqid() // Generate a unique identifier
        );

        // Create Voice grant
        $voiceGrant = new VoiceGrant();
        $voiceGrant->setOutgoingApplicationSid($accountSid);
        $voiceGrant->setIncomingAllow(true);

        // Add grant to token
        $token->addGrant($voiceGrant);

        return response()->json(['token' => $token->toJWT()]);
    }

    public function answer(Request $request)
    {
        $response = new VoiceResponse();
        
        // Start the Media Stream
        $connect = $response->connect();
        $connect->stream([
            'url' => 'wss://richbot9000.com:9501?to=' . urlencode($request->input('To')) . '&from=' . urlencode($request->input('From')),
            'track' => 'both'  // Enable both inbound and outbound audio
        ]);

        // Add some basic TwiML to keep the call active
        $response->say('Connected to Rich Bot 9000');
        $response->pause(['length' => 60]);  // Keep call alive for 60 seconds

        return response($response)
            ->header('Content-Type', 'text/xml');
    }

    public function streamStatus(Request $request)
    {
        Log::info('Stream Status', $request->all());







        return response()->json(['status' => 'ok']);
    }
} 