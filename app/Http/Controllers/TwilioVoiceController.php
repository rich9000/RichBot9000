<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;
use Twilio\Jwt\ClientToken;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VoiceGrant;
use Illuminate\Support\Facades\Log;
use App\Models\Assistant;
use App\Services\RainbowDashService;
use App\Models\User;
use App\Models\RainbowAccount;
use App\Models\Conversation;
use Illuminate\Support\Facades\Cache;
use App\Models\PhoneTreeMenu;
use App\Models\PhoneTree;
use App\Models\PhoneTreeCall;
use Symfony\Component\Process\Process;
use App\Models\Pipeline;

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

         //   $response->say('Connecting you to Rich Bot 9000. This call may be recorded for quality assurance.');
         //   $response->pause(['length' => 1]);

            $from = $request->input('From');
            $to = $request->input('To');
            $callSid = $request->input('CallSid');

            //status = ringing

            $assistant = Assistant::where('id', 22)->first();

            //conversation create one with id of the callSid
            $conversation = Conversation::create([
                            
                'id' => $callSid,
                'title' => "TwilioVoiceCall: {$from} To: {$to} CallSid: {$callSid}",
                'assistant_type' => "phone_call",
                'type' => "phone_call",    
                'assistant_id' => $assistant->id,
                'status' => "active",                

            ]);
           
            // Simplified URL format with only essential parameters
            $url = sprintf(
                'wss://'.config('app.domain').':'.config('app.ws_port').'/twilio/%s/%d',
                $callSid,
                $assistant->id,               
            );

            Log::info("WebSocket URL generated", ['url' => $url]);

            // say the lookup info

            // First, establish the stream connection
            $connect = $response->connect();
            $connect->stream([
                                
                'url' => $url,
               
            ]);











            // Remove + prefix if present and format the number
            $from_formatted = preg_replace('/^\+?1?(\d{3})(\d{3})(\d{4})$/', '$1-$2-$3', $from);

            $formatted_for_richbot = str_replace('-','',$from_formatted);

            $users = User::where('phone_number',$formatted_for_richbot)->get();


            Log::info('Users', [$from,$formatted_for_richbot,$from_formatted,$to,$users]);

            if($users->count() > 1){    

                $response->say('Richbot 9000 Test Line. You are registered with multiple accounts. Select the account you are calling for.');
                $response->gather([
                    'numDigits' => 1,
                    'action' => route('select-account-menu-response'),
                    'method' => 'POST',
                    'timeout' => 10,
                ]);

                $response->say('Please select the account you are calling for.');
                $response->pause(['length' => 1]);

                foreach($users as $user){
                   
                    $response->say('1. '.$user->first_name);
                    $response->pause(['length' => 1]);
                    
                }
                

                return response($response)->header('Content-Type', 'text/xml');
                
            }else if($users->count() == 1){

                $user = $users->first();

                $conversation->user_id = $user->id;
                $conversation->save();  

                $response->say('Greetings, '.$user->name);
                //$response->say('You have been granted access to additional Richbot 9000 menu options.');
                $response->pause(['length' => 1]);

            } else {

                $user = false;

            }

            $rainbow = new RainbowDashService();
            $login_info = $rainbow->login('rich@rainbowtel.com','richlikestowork');
            if($login_info['token']){
                $token = $login_info['token'];
            } else {
                $token = false;
            }

            if($token){
                           
                $lookup_info = $rainbow->lookupPhoneNumber($token,$from_formatted);
                Log::info('Lookup info', [$from_formatted,$lookup_info]);

                if($lookup_info['status'] == 'success'){
                    $lookup_info = $lookup_info['data'];

                    $conversation->system_message = "You can use the following information to help identify the caller, This is potential information on the calling user: ".print_r($lookup_info,true);
                    $conversation->save();




                } else {
                    $lookup_info = false;
                }


            }

            if($lookup_info){
               
                $response->say("Welcome to Rich Bot 9000. Is this in reference to the rainbow account {$lookup_info['address_line_1']}? Who am I speaking with?");

            }
          
            Log::info('TwilioVoiceController: handleCall', [
                'from' => $from,
                'from_formatted' => $from_formatted,
                'to' => $to,
                'callSid' => $callSid
            ]);



            


            Log::info('Request', $request->all());

         


      

            // Then gather input AFTER the stream is connected
            $gather = $response->gather([
                'numDigits' => 1,
                'action' => route('menu-response'),
                'method' => 'POST',
                'timeout' => 10,
                
            ]);

            $gather->say('Here are your options.');


            if($user){
                $gather->say('Press 1 to talk to your Rich Bot 9000 personal AI assistant. ',                
                ['voice' => 'alice', 'language' => 'en-US']);
            } else {
                $gather->say('Press 1 to talk to The Rich Bot 9000 System AI Assistant. ');
                
            }

            $gather->say(
                'Press 2 to call Rich Carroll. ' .
                'Press 3 to talk to Rainbow Tech Support Test. ' .
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
        $callSid = $request->input('CallSid');
        $from = $request->input('From');
        $to = $request->input('To');
          
        $conversation = Conversation::where('id', $callSid)->first();

        if($conversation){
            $conversation->addMessage('user',"User pressed $digits");
        }

       // $attempts = $request->session()->get('attempts', 0);

        switch ($digits) {
            case '1':
                $response = new VoiceResponse();
                
                // First inform the caller
                $response->say(
                    'Connecting you to Rich Bot 9000. This call may be recorded for quality assurance.', 
                    ['voice' => 'alice']
                );

            $assistant = Assistant::where('id', 22)->first();
         

            // Simplified URL format with only essential parameters
            $url = sprintf(
                'wss://'.config('app.domain').':'.config('app.ws_port').'/twilio/%s/%d',
                $request->input('CallSid'),
                $assistant->id
                
            );

                // Then establish the stream connection
                $connect = $response->connect();
                $connect->stream([
                    'url' => $url,
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


                    $response = new VoiceResponse();
                
                    // First inform the caller
                    $response->say(
                        'Connecting you to Rainbow Tech Support Test. This call may be recorded for quality assurance.', 
                        ['voice' => 'alice']
                    );
    
                $assistant = Assistant::where('id', 22)->first();
             
    
                // Simplified URL format with only essential parameters
                $url = sprintf(
                    'wss://'.config('app.domain').':'.config('app.ws_port').'/twilio/%s/%d',
                    $request->input('CallSid'),
                    $assistant->id
                    
                );
    
                    // Then establish the stream connection
                    $connect = $response->connect();
                    $connect->stream([
                        'url' => $url,
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
        // Mail::to('admin@admin.richbot9000.com')->send(new TranscriptionNotification($request->all()));
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
            'url' => 'wss://'.config('app.domain').':'.config('app.ws_port').'?to=' . urlencode($request->input('To')) . '&from=' . urlencode($request->input('From')),
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

    public function handleWebSocketCall(Request $request, $room = null)
    {
        try {
            

            Log::info("TwilioVoiceController****************handleWebSocketCall", [
                'request' => $request->all(),                
                'room' => $room
            ]);

            $cacheCallSid = Cache::get($room);

            
            $response = new VoiceResponse();
            $callSid = $request->input('CallSid');
            $from = $request->input('From');
            $to = $request->input('To');

            if($cacheCallSid != $callSid){

                Log::info("TwilioVoiceController: handleWebSocketCall: cacheCallSid != callSid", [
                    'cacheCallSid' => $cacheCallSid,
                    'callSid' => $callSid
                ]);

            }


            Log::info("TwilioVoiceController: handleWebSocketCall", [
                'from' => $from,
                'to' => $to,
                'callSid' => $callSid,
                'room' => $room
            ]);

            // Create a stream connection to our WebSocket server
            $connect = $response->connect();
     //       $response->say('Connecting to Rich Bot 9000.');
     //       $response->pause(['length' => 1]);


            $connect->stream([
                'url' => "wss://'.config('app.domain').':'.config('app.ws_port_alt')."/twilio-outbound/{$room}/{$callSid}",
                //'track' => 'both',
                //'mediaFormat' => 'mulaw',
                //'mediaSampleRate' => '8000',
                //'mediaChannels' => '1',
                //'mediaCodec' => 'ulaw',
                //'mediaCodecPayloadType' => '0'
            ]);

            return response($response)
                ->header('Content-Type', 'text/xml');


        } catch (\Exception $e) {
            Log::error('Error in handleWebSocketCall: ' . $e->getMessage());
            $response = new VoiceResponse();
            $response->say('We apologize, but an error has occurred. Please try your call again.');
            return response($response)->header('Content-Type', 'text/xml');
        }
    }

    private function playMenuAudio(PhoneTreeMenu $menu)
    {
        $response = new VoiceResponse();
        
        // Play welcome audio/message
        if ($menu->welcome_audio_id) {
            $response->play($menu->welcomeAudio->url);
        } else if ($menu->welcome_message) {
            $response->say($menu->welcome_message);
        }
        
        // Play prompt audio/message
        if ($menu->prompt_audio_id) {
            $response->play($menu->promptAudio->url);
        } else if ($menu->prompt_message) {
            $response->say($menu->prompt_message);
        }
        
        // Gather input
        $gather = $response->gather([
            'numDigits' => 1,
            'action' => route('phone-tree-menu-response'),
            'method' => 'POST',
            'timeout' => $menu->timeout_seconds
        ]);
        
        // Present options
        $options = $menu->options()
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        foreach ($options as $option) {
            if ($menu->speak_options) {
                $gather->say("Press {$option->digit} for {$option->description}");
            } else {
                $gather->say($option->description);
            }
        }
        
        return response($response)->header('Content-Type', 'text/xml');
    }

    public function handlePhoneTreeMenu(Request $request, $callSid = null)
    {
        try {
            Log::info("TwilioVoiceController: handlePhoneTreeMenu", [
                'request' => $request->all(),
                'callSid' => $callSid
            ]);

            $response = new VoiceResponse();
            $digits = $request->input('Digits');
            
            $call = PhoneTreeCall::where('call_sid', $callSid)->first();
            if (!$call) {
                Log::error('Call not found', ['callSid' => $callSid]);
                $response->say('Sorry, we could not find your call information.');
                return response($response)->header('Content-Type', 'text/xml');
            }

            $currentMenu = $call->currentMenu;
            if (!$currentMenu) {
                Log::error('Menu not found', ['callSid' => $callSid]);
                $response->say('Sorry, we could not find the current menu.');
                return response($response)->header('Content-Type', 'text/xml');
            }

            Log::info("Processing menu", [
                'menu_id' => $currentMenu->id,
                'menu_name' => $currentMenu->name,
                'is_active' => $currentMenu
            ]);

            if (!$currentMenu->is_active) {
                Log::warning('Inactive menu accessed', ['menu_id' => $currentMenu->id]);
                $response->say('This menu is currently inactive.');
                return response($response)->header('Content-Type', 'text/xml');
            }

            // Welcome Section
            if ($currentMenu->welcome_audio_id && $currentMenu->welcomeAudio) {
                Log::info('Playing welcome audio', ['audio_id' => $currentMenu->welcome_audio_id]);
                $response->play($currentMenu->welcomeAudio->url);
            }
            if ($currentMenu->welcome_message) {
                Log::info('Playing welcome message', ['message' => $currentMenu->welcome_message]);
                $response->say($currentMenu->welcome_message);
            }

            // Prompt Section
            if ($currentMenu->prompt_audio_id && $currentMenu->promptAudio) {
                Log::info('Playing prompt audio', ['audio_id' => $currentMenu->prompt_audio_id]);
                $response->play($currentMenu->promptAudio->url);
            }
            if ($currentMenu->prompt_message) {
                Log::info('Playing prompt message', ['message' => $currentMenu->prompt_message]);
                $response->say($currentMenu->prompt_message);
            }

            // Options Section
            $options = $currentMenu->options()
                ->where('is_active', true)
                ->orderBy('order')
                ->get();

            Log::info('Processing options', [
                'menu_id' => $currentMenu->id,
                'option_count' => $options->count()
            ]);

            if ($options->count() > 0) {
                $gather = $response->gather([
                    'input' => 'dtmf',
                    'numDigits' => 1,
                    'action' => route('phone-tree-menu-response', ['callSid' => $callSid]),
                    'method' => 'POST',
                    'timeout' => $currentMenu->timeout_seconds
                ]);

                if ($currentMenu->speak_options) {
                    foreach ($options as $option) {
                        $gather->say("Press {$option->digit} for {$option->description}");
                    }
                }

                // Add redirect for timeout
                $response->redirect(route('phone-tree-menu', ['callSid' => $callSid]));
            }

            if($currentMenu->assistant){

                $room = $callSid . '-' . $currentMenu->assistant->id;

                Log::info("TwilioVoiceController: handlePhoneTreeMenu: assistant", [
                    'assistant' => $currentMenu->assistant
                ]);

                $assistant = $currentMenu->assistant;

                    // Simplified URL format with only essential parameters
                $url = sprintf(
                    'wss://'.config('app.domain').':'.config('app.ws_port_alt').'/twilio-inbound/%s/%s',
                    $room,
                    $callSid                    
                );

                Log::info("WebSocket URL generated", ['url' => $url]);

                // say the lookup info
                $response->say("Connecting to " . $assistant->name . ".");
                // First, establish the stream connection
                $connect = $response->connect();
                $connect->stream([
                                    
                    'url' => $url,

                ]);

                $basePath = config('app.base_path');
                $command = "nohup /usr/bin/php {$basePath}/artisan bare:assistant-v2 {$room} {$assistant->id} --second-delay=4 >> {$basePath}/storage/logs/assistant_{$room}.log 2>&1 & echo $!";
                exec($command, $output);
                $pid = (int)$output[0];

                Log::info("TwilioVoiceController: handlePhoneTreeMenu: assistant: process started",[$command,$output,$pid]);

                Log::info("Command: ".$command);
        
                $username = 'unknown';
                Log::info("[SERVER] Starting assistant chat: {$assistant->id} in room: {$room} for user: {$username} with pid: {$pid}");
        
                return response($response)->header('Content-Type', 'text/xml');

            }

            // pipeline section
            if($currentMenu->pipeline_id && $currentMenu->pipeline){

                $pipeline = $currentMenu->pipeline;

                Log::info("TwilioVoiceController: handlePhoneTreeMenu: pipeline", [
                    'pipeline' => $currentMenu->pipeline
                ]);

                try {
                    // Create a new conversation for this pipeline
                    $conversation = Conversation::create([
                        'id' => $callSid,
                        'title' => $pipeline->name,
                        'pipeline_id' => $pipeline->id,
                        'type' => 'pipeline',
                        'status' => 'active',
                        'phone_tree_call_id' => $call->id
                    ]);
                    
                    // Get the first stage
                    $stage = $pipeline->stages()->orderBy('order')->first();
                    if ($stage) {
                        $conversation->stage_id = $stage->id;
                        $conversation->save();
                        
                        // Add system message about the pipeline
                        $conversation->addMessage('system', "Starting pipeline: {$pipeline->name}");
                    }

                    // Create room identifier using callSid and pipeline ID
                    $room = $callSid . '-' . $pipeline->id;

                    // Start the pipeline relay
                    $basePath = config('app.base_path');
                    $command = "nohup /usr/bin/php {$basePath}/artisan bare:pipeline {$room} {$conversation->id} --second-delay=4 >> {$basePath}/storage/logs/pipeline_{$room}.log 2>&1 & echo $!";
                    exec($command, $output);
                    $pid = (int)$output[0];

                    Log::info("TwilioVoiceController: handlePhoneTreeMenu: pipeline: process started", [$command, $output, $pid]);

                    // Set up WebSocket connection
                    $url = sprintf(
                        'wss://'.config('app.domain').':'.config('app.ws_port_alt').'/twilio-inbound/%s/%s',
                        $room,
                        $callSid                    
                    );

                    Log::info("WebSocket URL generated", ['url' => $url]);

                    // Inform the caller
                    $response->say("Connecting to " . $pipeline->name . ".");
                    
                    // Establish the stream connection
                    $connect = $response->connect();
                    $connect->stream([
                        'url' => $url,
                    ]);

                    return response($response)->header('Content-Type', 'text/xml');

                } catch (\Exception $e) {
                    Log::error("Failed to start pipeline", [
                        'error' => $e->getMessage(),
                        'pipeline_id' => $pipeline->id,
                        'callSid' => $callSid
                    ]);
                    
                    $response->say("We apologize, but an error has occurred. Please try your call again.");
                    return response($response)->header('Content-Type', 'text/xml');
                }
            }

            // WebSocket Section
            if ($currentMenu->websocket_id && $currentMenu->websocket) {
                Log::info('Handling WebSocket connection', [
                    'websocket_id' => $currentMenu->websocket_id
                ]);
                $connect = $response->connect();
                $connect->stream([
                    'url' => $currentMenu->websocket->endpoint_url,
                    'track' => 'both'
                ]);
            }

            // Transfer Section
            if ($currentMenu->transfer_number) {
                Log::info('Transferring call', [
                    'number' => $currentMenu->transfer_number
                ]);
                $response->say('Transferring your call.');
                $response->dial($currentMenu->transfer_number);
            }

            // Script Section
            if ($currentMenu->script_path) {
                Log::info('Executing script', [
                    'path' => $currentMenu->script_path
                ]);
                // Include and execute the script if it exists
                if (file_exists($currentMenu->script_path)) {
                    include $currentMenu->script_path;
                }
            }

            // Finish Section
            if ($currentMenu->finish_audio_id && $currentMenu->finishAudio) {
                Log::info('Playing finish audio', [
                    'audio_id' => $currentMenu->finish_audio_id
                ]);
                $response->play($currentMenu->finishAudio->url);
            }
            if ($currentMenu->finish_message) {
                Log::info('Playing finish message', [
                    'message' => $currentMenu->finish_message
                ]);
                $response->say($currentMenu->finish_message);
            }

            if ($currentMenu->disconnect_on_finish) {
                Log::info('Disconnecting call on finish');
                $response->say('Thank you for calling. Goodbye!');
                $response->hangup();
            }

            if ($currentMenu->finish_menu_id) {
                Log::info('Redirecting to finish menu', [
                    'menu_id' => $currentMenu->finish_menu_id
                ]);
                $call->current_menu_id = $currentMenu->finish_menu_id;
                $call->save();
                $response->redirect(route('phone-tree-menu', ['callSid' => $callSid]));
            }

            return response($response)->header('Content-Type', 'text/xml');

        } catch (\Exception $e) {
            Log::error('Error in handlePhoneTreeMenu', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $response = new VoiceResponse();
            $response->say('We apologize, but an error has occurred. Please try your call again.');
            return response($response)->header('Content-Type', 'text/xml');
        }
    }

    public function handlePhoneTreeCall(Request $request, PhoneTree $phoneTree = null)
    {
        try {
            

            $response = new VoiceResponse();
            $from = $request->input('From');
            $to = $request->input('To');
            $callSid = $request->input('CallSid');



           
            // Normalize phone numbers by removing + and 1 prefix
            $normalizePhoneNumber = function($number) {
                return preg_replace('/^\+?1?(\d{10})$/', '$1', $number);
            };

            $normalizedTo = $normalizePhoneNumber($to);

            // Find the phone tree either by ID or by phone number
            if (!$phoneTree) {
                // Phone number routing - check if the normalized number ends with any of the phone tree numbers
                $phoneTree = PhoneTree::whereHas('numbers', function($query) use ($normalizedTo) {
                    $query->where(function($q) use ($normalizedTo) {
                        $q->where('phone_number', $normalizedTo);
                    })
                    ->where('is_active', true);
                })->where('is_active', true)->first();
            }

            // If still no phone tree found, use default
            if (!$phoneTree) {
                $phoneTree = PhoneTree::where('is_default', true)
                    ->where('is_active', true)
                    ->first();
            }

            Log::info("TwilioVoiceController: handlePhoneTreeCall", [
                'request' => $request->all(),
                'phoneTree' => $phoneTree,
                'from' => $from,
                'to' => $to,
                'callSid' => $callSid,
                'normalizedTo' => $normalizedTo
            ]);




            // Create a new call record
            $call = PhoneTreeCall::create([
                'phone_tree_id' => $phoneTree ? $phoneTree->id : null,
                'call_sid' => $callSid,
                'from_number' => $from,
                'to_number' => $to,
                'start_time' => now(),
                'status' => 'active'
            ]);

            if (!$phoneTree) {
                $response->say('Sorry, this phone tree is not available.');
                return response($response)->header('Content-Type', 'text/xml');
            }


            Log::info("TwilioVoiceController: handlePhoneTreeCall: welcomeAudio ", [
                
                'welcome_audio_id' => $phoneTree->welcome_audio_id,
                'welcomeAudio' => $phoneTree->welcomeAudio
            ]);

            // Play phone tree welcome message/audio
            if ($phoneTree->welcome_audio_id && $phoneTree->welcomeAudio) {

                Log::info("TwilioVoiceController: handlePhoneTreeCall: welcomeAudio", [
                    'welcomeAudio' => $phoneTree->welcomeAudio
                ]);



                $response->play($phoneTree->welcomeAudio->url);
            } 
            if ($phoneTree->welcome_message) {
                $response->say($phoneTree->welcome_message);
            }



            if ($phoneTree->rootMenu) {
                // Start with the root menu              
                    
                $call->current_menu_id = $phoneTree->rootMenu->id;
                $call->save();

                //$response->say('Redirecting to the root menu.');
                $response->redirect(route('phone-tree-menu', ['callSid' => $callSid]));
              //  return response($response)->header('Content-Type', 'text/xml');
                } else {
                    $response->say('Sorry, no options are available.');
                    $response->hangup();
                }


            Log::info("TwilioVoiceController: handlePhoneTreeCall: response", [
                'response' => $response
            ]);
            

            return response($response)->header('Content-Type', 'text/xml');

        } catch (\Exception $e) {
            Log::error('Error in handlePhoneTreeCall: ' . $e->getMessage());
            $response = new VoiceResponse();
            $response->say('We apologize, but an error has occurred. Please try your call again.');
            return response($response)->header('Content-Type', 'text/xml');
        }
    }

    public function handlePhoneTreeMenuResponse(Request $request)
    {

        

        try {
            $response = new VoiceResponse();
            $digits = $request->input('Digits');
            $callSid = $request->input('CallSid');

            // Find the current call
            $call = PhoneTreeCall::where('call_sid', $callSid)->first();
            if (!$call) {
                $response->say('Sorry, we could not find your call information.');
                return response($response)->header('Content-Type', 'text/xml');
            }

            // Find the current menu
            $currentMenu = $call->currentMenu;
            if (!$currentMenu) {
                $response->say('Sorry, we could not find the current menu.');
                return response($response)->header('Content-Type', 'text/xml');
            }

            Log::info("TwilioVoiceController: handlePhoneTreeMenuResponse", [
                'request' => $request->all(),
                'callSid' => $callSid,
                'currentMenu' => $currentMenu,
                'digits' => $digits
            ]);

            // Find the selected option
            $option = $currentMenu->options()
                ->where('digit', $digits)
                ->where('is_active', true)
                ->first();

            if (!$option) {
                // Invalid input
                $call->last_input = $digits;
                $call->save();

                $response->say($currentMenu->invalid_input_message);
                return $this->playMenuAudio($currentMenu);
            }

            Log::info("TwilioVoiceController: handlePhoneTreeMenuResponse: option", [
                'option' => $option
            ]);

            // Handle the option based on its action type
            switch ($option->action_type) {
                case 'menu':
                    Log::info("TwilioVoiceController: handlePhoneTreeMenuResponse: option: menu", [
                        'option' => $option
                    ]);
                    // Navigate to the target menu
                    $call->current_menu_id = $option->target_id;
                    $call->save();

                    $targetMenu = PhoneTreeMenu::find($option->target_id);
                    if ($targetMenu) {
                        $response->redirect(route('phone-tree-menu', ['callSid' => $callSid]));                      

                    } else {
                        $response->say('Sorry, we could not find the target menu.');
                        $response->hangup();
                    }
                    break;

                case 'hangup':
                    // Play finish message/audio and hang up
                    if ($currentMenu->finish_audio_id) {
                        $response->play($currentMenu->finishAudio->url);
                    } else if ($currentMenu->finish_message) {
                        $response->say($currentMenu->finish_message);
                    }
                    $response->hangup();
                    break;

                case 'transfer':
                    // Transfer to another number
                    $response->say('Transferring your call.');
                    $response->dial($currentMenu->transfer_number);
                    
                    break;
                case 'audio_file':

                        $audioFile = $option->audio_file;
                        if($audioFile){
                            $response->play($audioFile->url);
                        } else {
                            $response->say('Sorry, we could not find the audio file.');
                        }
                      
                        break;
            }

            return response($response)->header('Content-Type', 'text/xml');

        } catch (\Exception $e) {
            Log::error('Error in handlePhoneTreeMenuResponse: ' . $e->getMessage());
            $response = new VoiceResponse();
            $response->say('We apologize, but an error has occurred. Please try your call again.');
            return response($response)->header('Content-Type', 'text/xml');
        }
    }

    public function handlePhoneTreeTimeout(Request $request)
    {
        try {
            $response = new VoiceResponse();
            $callSid = $request->input('CallSid');

            // Find the current call
            $call = PhoneTreeCall::where('call_sid', $callSid)->first();
            if (!$call) {
                $response->say('Sorry, we could not find your call information.');
                return response($response)->header('Content-Type', 'text/xml');
            }

            // Find the current menu
            $currentMenu = $call->currentMenu;
            if (!$currentMenu) {
                $response->say('Sorry, we could not find the current menu.');
                return response($response)->header('Content-Type', 'text/xml');
            }

            // Play timeout message
            $response->say($currentMenu->timeout_message);

            // If there's a finish menu, go to it
            if ($currentMenu->finish_menu_id) {
                $call->current_menu_id = $currentMenu->finish_menu_id;
                $call->save();
                return $this->playMenuAudio($currentMenu->finishMenu);
            }

            // Otherwise, hang up
            $response->hangup();
            return response($response)->header('Content-Type', 'text/xml');

        } catch (\Exception $e) {
            Log::error('Error in handlePhoneTreeTimeout: ' . $e->getMessage());
            $response = new VoiceResponse();
            $response->say('We apologize, but an error has occurred. Please try your call again.');
            return response($response)->header('Content-Type', 'text/xml');
        }
    }

    public function handlePhoneTreeWebsocket(Request $request, $callSid)
    {
        try {
            $response = new VoiceResponse();
            
            // Find the current call
            $call = PhoneTreeCall::where('call_sid', $callSid)->first();
            if (!$call) {
                $response->say('Sorry, we could not find your call information.');
                return response($response)->header('Content-Type', 'text/xml');
            }

            // Find the current menu
            $currentMenu = $call->currentMenu;
            if (!$currentMenu || !$currentMenu->websocket) {
                $response->say('Sorry, we could not establish the connection.');
                return response($response)->header('Content-Type', 'text/xml');
            }

            // Connect to the WebSocket
            $connect = $response->connect();
            $connect->stream([
                'url' => $currentMenu->websocket->endpoint_url,
                'track' => 'both'
            ]);

            return response($response)->header('Content-Type', 'text/xml');

        } catch (\Exception $e) {
            Log::error('Error in handlePhoneTreeWebsocket: ' . $e->getMessage());
            
            // If there's a fail menu, go to it
            if ($currentMenu && $currentMenu->websocketFailMenu) {
                $call->current_menu_id = $currentMenu->websocket_fail_menu_id;
                $call->save();
                return $this->playMenuAudio($currentMenu->websocketFailMenu);
            }

            $response = new VoiceResponse();
            $response->say('We apologize, but an error has occurred. Please try your call again.');
            return response($response)->header('Content-Type', 'text/xml');
        }
    }

    public function handlePhoneTreeOption(Request $request)
    {
        try {
            $response = new VoiceResponse();
            $digits = $request->input('Digits');
            $callSid = $request->input('CallSid');

            // Find the current call
            $call = PhoneTreeCall::where('call_sid', $callSid)->first();
            if (!$call) {
                $response->say('Sorry, we could not find your call information.');
                return response($response)->header('Content-Type', 'text/xml');
            }

            // Find the current menu
            $currentMenu = $call->currentMenu;
            if (!$currentMenu) {
                $response->say('Sorry, we could not find the current menu.');
                return response($response)->header('Content-Type', 'text/xml');
            }

            // Find the selected option
            $option = $currentMenu->options()
                ->where('digit', $digits)
                ->where('is_active', true)
                ->first();

            if (!$option) {
                // Invalid input
                $call->last_input = $digits;
                $call->save();

                $response->say($currentMenu->invalid_input_message);
                return $this->playMenuAudio($currentMenu);
            }

            // Handle the option based on its action type
            switch ($option->action_type) {
                case 'menu':
                    // Navigate to the target menu
                    $call->current_menu_id = $option->target_id;
                    $call->last_input = $digits;
                    $call->save();

                    $targetMenu = PhoneTreeMenu::find($option->target_id);
                    if ($targetMenu) {
                        return $this->playMenuAudio($targetMenu);
                    }
                    break;

                case 'hangup':
                    // Play finish message/audio and hang up
                    if ($currentMenu->finish_audio_id) {
                        $response->play($currentMenu->finishAudio->url);
                    } else if ($currentMenu->finish_message) {
                        $response->say($currentMenu->finish_message);
                    }
                    $response->hangup();
                    break;

                case 'transfer':
                    // Transfer to another number
                    $response->say('Transferring your call.');
                    $response->dial($currentMenu->transfer_number);
                    break;

                case 'websocket':
                    // Connect to WebSocket
                    $response->say('Connecting you to an agent.');
                    $connect = $response->connect();
                    $connect->stream([
                        'url' => $currentMenu->websocket->endpoint_url,
                        'track' => 'both'
                    ]);
                    break;

                case 'script':
                    // Execute a custom script
                    if ($option->script_path) {
                        // Include and execute the script
                        $script = include $option->script_path;
                        if (is_callable($script)) {
                            return $script($request, $response, $call, $currentMenu, $option);
                        }
                    }
                    break;
            }

            return response($response)->header('Content-Type', 'text/xml');

        } catch (\Exception $e) {
            Log::error('Error in handlePhoneTreeOption: ' . $e->getMessage());
            $response = new VoiceResponse();
            $response->say('We apologize, but an error has occurred. Please try your call again.');
            return response($response)->header('Content-Type', 'text/xml');
        }
    }

    public function handlePipelineSelection(Request $request)
    {
        $pipelineId = $request->input('pipeline_id');
        $callSid = $request->input('CallSid');
        
        
    }
} 