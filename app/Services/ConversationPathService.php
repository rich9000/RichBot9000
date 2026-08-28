<?php

namespace App\Services;

use App\Models\ConversationPath;
use App\Models\Conversation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\AudioFile;
use App\Models\Tool;
use App\Services\Executors\RainbowExecutor;
use App\Services\Executors\RainbowDashboardTicketExecutor;  
use App\Services\Executors\RichBotExecutor;



/**
 * ConversationPathService
 *
 * This service manages the flow of a conversation along a defined path of nodes.
 *
 * Node Types and Behaviors:
 *
 * - action: Performs an action. Subtypes include:
 *   - say: Speak a message to the user.
 *   - play: Play an audio file or URL.
 *   - assistant: Starts an assistant process and streams audio.
 *   - pipeline: Starts a pipeline process and streams audio.
 *   - script: Executes a PHP script and runs the returned action (say, redirect, etc.).
 *   - websocket: Connects to a custom WebSocket endpoint.
 *   - sms/email: Sends an SMS or email using Twilio/Mail.
 *   - hangup/transfer/route/etc.: Standard TwiML actions.
 *
 * - decision: Presents a branching point. Subtypes include:
 *   - user: Presents a DTMF menu (like a phone tree) for user input.
 *   - assistant: Logs the state, prompt, and assistant id for review or future use.
 *   - conditional: Executes a script and branches based on the returned type (say, redirect, etc.).
 *
 * - data: Handles data or context. Subtypes include:
 *   - custom: Executes a script and adds its result as a system message to the conversation.
 *   - contextAssistant: Logs a prompt and adds it to the conversation's system message.
 *   - APIData, outageCheck, customerLookup, etc.: Reserved for future or custom data logic.
 *
 * - entry/info: Reserved for entry points or informational nodes.
 *
 * Each node type and subtype is handled in its respective method.
 */

class ConversationPathService
{
    /**
     * Start a new conversation for a given conversation path ID.
     * Returns the Conversation instance.
     */
    public function startCallConversation($conversationPathId, $extra = [])
    {


        $path_state = $extra;


        $path = ConversationPath::findOrFail($conversationPathId);
        $conversation = Conversation::create([
            'id' => Str::uuid(),
            'title' => $path->name,
            'conversation_path_id' => $path->id,
            'type' => 'phone_call', // or other type as needed
            'status' => 'active',
            'current_node_index' => 0,
            'path_state' => $path_state,            
        ]);
        return $conversation;
    }

    /**
     * Continue a conversation by conversation_id.
     * Returns the Conversation instance and current node info.
     */
    public function continueCallConversation($conversationId, $request = null)
    {
        $conversation = Conversation::with('conversationPath')->findOrFail($conversationId);
        $path = $conversation->conversationPath;
        $nodes = $path ? $path->nodes : [];
        $currentNodeIndex = $conversation->current_node_index ?? 1;

        if($currentNodeIndex == $conversation->current_node_index){
            Log::info('[ConversationPathService]: *******continueCallConversation: currentNodeIndex is the same as the conversation current_node_index', [
                'conversationId' => $conversationId,
                'currentNodeIndex' => $currentNodeIndex,
                'conversationCurrentNodeIndex' => $conversation->current_node_index,
            ]);

          

        } else {

            $conversation->current_node_index = $currentNodeIndex;
            $conversation->save();

            Log::info('[ConversationPathService]: *******continueCallConversation: currentNodeIndex is not the same as the conversation current_node_index', [
                'conversationId' => $conversationId,
                'currentNodeIndex' => $currentNodeIndex,
                'conversationCurrentNodeIndex' => $conversation->current_node_index,
            ]);


        }
        

        $currentNode = $nodes[$currentNodeIndex] ?? null;

        $conversation->path_state = $conversation->path_state ?? [];


        Log::info('[ConversationPathService]: continueCallConversation', [
            'conversationId' => $conversationId,
            'currentNodeIndex' => $currentNodeIndex,
            'currentNode' => $currentNode,
        ]);

        if (!$currentNode) {
            return [
                'action' => 'end',
                'message' => 'No more nodes in the path.',
            ];
        }

        switch ($currentNode['type']) {
            case 'action':
                return $this->handleActionNode($conversation, $currentNode, $request);
            case 'decision':
                return $this->handleDecisionNode($conversation, $currentNode, $request);
            case 'data':
                return $this->handleDataNode($conversation, $currentNode, $request);
            case 'entry':
                return $this->handleEntryNode($conversation, $currentNode, $request);
            case 'info':
                return $this->handleInfoNode($conversation, $currentNode, $request);
            default:
                Log::info('[ConversationPathService]: *******continueCallConversation: default', [
                    'conversationId' => $conversationId,
                    'currentNodeIndex' => $currentNodeIndex,
                    'currentNode' => $currentNode,
                    'type' => $currentNode['type'],
                    'subtype' => $currentNode['subtype'],
                    'content' => $currentNode['content'],
                ]);
                return response('<Response><Say>Unknown node type: ' . ($currentNode['type'] ?? 'none') . '</Say></Response>', 200)->header('Content-Type', 'text/xml');
        }
    }

    private function handleActionNode($conversation, $node, $request = null)
    {

        $conversation->current_node_index++;
        $conversation->save();

        $twiml = new \Twilio\TwiML\VoiceResponse();
        switch ($node['subtype']) {
            case 'monitorCall':
                $twiml->say('MonitorCall action not implemented');
                Log::info('[ConversationPathService]: monitorCall', [
                    'conversation' => $conversation,
                    'node_content' => $node['content'],
                ]); 


                    $path_state = $conversation->path_state ?? [];
                    $path_state['monitor_call'] = $node['content'];
                    $conversation->path_state = $path_state;
                    $conversation->save();
                      
                break;
            case 'say':
                $text = $node['content']['say_text'] ?? '';
                $voice = $node['content']['voice'] ?? 'alice';


                if(str_contains($text,'#')){


                    Log::info('[ConversationPathService]: say: text', [
                        'text' => $text,
                    ]);

                    $parts = [];

                    $voice_parts = explode(' ', $text);

                    foreach($voice_parts as $part){
                        if(str_starts_with($part, '#')){

                            $resolved_part = $this->resolveUiVariable($part,$conversation); 

                            Log::info('[ConversationPathService]: say: part', [
                                'part' => $part,
                                'resolved_part' => $resolved_part,
                            ]);
                            
                            if($resolved_part){

                                $part = $resolved_part;                              
                               
                            }
                           
                        }

                        $parts[] = $part;
                    }

                    $text = implode(' ', $parts);
                }

                $twiml->say($text, ['voice' => $voice]);
                break;
            case 'play':
                $audioUrl = $node['content']['audioUrl'] ?? null;
                $audioFileId = $node['content']['audioFileId'] ?? null;
                $loop = $node['content']['loopCount'] ?? 1;
                if ($audioUrl) {
                    $twiml->play($audioUrl, ['loop' => $loop]);
                } else if ($audioFileId) {
                    //$audioFile = AudioFile::find($audioFileId);
                    $twiml->play("/api/audio-files/{$audioFileId}/serve", ['loop' => $loop]);
                    // TODO: Lookup audio file URL by ID
                }                
                break;
            case 'assistant':
                Log::info('[ConversationPathService]: assistant', [
                    'conversation' => $conversation,
                    'node' => $node,
                ]);

                $callSid = $conversation->path_state['twilio_call']['CallSid'] ?? null;
                $assistantId = $node['content']['assistantId'] ?? 22;
                $room = $conversation->id;

                Log::info('[ConversationPathService]: assistant:*********************** room', [
                    'room' => $room,
                    'assistantId' => $assistantId,
                    'conversationId' => $conversation->id,
                ]);
                //"room":"-14","assistantId":14,"conversationId":"01920202-0202-0202-0202-020202020202"
                // Start the assistant process (like PhoneTree) - Using V2 relay
                $basePath = config('app.base_path');
                $command = "nohup /usr/bin/php {$basePath}/artisan bare:assistant-v2 {$room} {$assistantId} --conversation_id={$conversation->id} --second-delay=0 >> {$basePath}/storage/logs/assistant_{$room}.log 2>&1 & echo $!";
                exec($command, $output);
                $pid = (int)($output[0] ?? 0);
                Log::info("ConversationPathService: assistant: process started", [$command, $output, $pid]);

                // Use twilio URL pattern for V2 server
                $url = sprintf(
                    'wss://%s:%s/twilio/%s/%s',
                    config('app.domain'),
                    config('app.ws_port_alt'),
                    $room,
                    $callSid
                );
                $connect = $twiml->connect();
                $connect->stream([
                    'url' => $url,
                ]);

                break;
            case 'pipeline':
                Log::info('[ConversationPathService]: pipeline', [
                    'conversation' => $conversation,
                    'node' => $node,
                ]);

                $callSid = $conversation->path_state['twilio_call']['CallSid'] ?? null;
                $pipelineId = $node['content']['pipelineId'] ?? null;
                $room = $callSid . '-' . $pipelineId;

                // Start the pipeline process (like PhoneTree)
                $basePath = config('app.base_path');
                $command = "nohup /usr/bin/php {$basePath}/artisan bare:pipeline {$room} {$conversation->id} --second-delay=4 >> {$basePath}/storage/logs/pipeline_{$room}.log 2>&1 & echo $!";
                exec($command, $output);
                $pid = (int)($output[0] ?? 0);
                Log::info("ConversationPathService: pipeline: process started", [$command, $output, $pid]);

                // Use twilio URL pattern for V2 server
                $url = sprintf(
                    'wss://%s:%s/twilio/%s/%s',
                    config('app.domain'),
                    config('app.ws_port_alt'),
                    $room,
                    $callSid
                );
                $connect = $twiml->connect();
                $connect->stream([
                    'url' => $url,
                ]);

              






                break;
            case 'phoneTree':
                $twiml->say('PhoneTree action not implemented');
                break;
            case 'survey':
                $twiml->say('Survey action not implemented');
                break;
            case 'hangup':
                $twiml->hangup();
                break;
            case 'voiceMail':
                $twiml->say('VoiceMail action not implemented');
                break;
            case 'transfer':
                
                $phoneNumber = $node['content']['phoneNumber'] ?? null;
                if($phoneNumber){
                    $dial = $twiml->dial('');
                    $dial->number($phoneNumber);
                }
                
                break;
            case 'route':
                $twiml->say('Route action not implemented');
                break;
            case 'conversationPath':
                Log::info('[ConversationPathService]: conversationPath case:', [
                    'node' => $node['content'],
                ]);

                $conversationPathId = $node['content']['targetPathId'] ?? null;
                if($conversationPathId){

                    Log::info('[ConversationPathService]: conversationPath case:', [
                        'conversationPathId' => $conversationPathId,
                    ]);
                   

                    $conversationPath = ConversationPath::findOrFail($conversationPathId);
                   

                    $path_state = $conversation->path_state ?? [];
                    if(!isset($path_state['conversation_path_ids'])){
                        $path_state['conversation_path_ids'] = [];
                    }
                    $path_state['conversation_path_ids'][] = $conversation->conversation_path_id;                    
                    $conversation->path_state = $path_state;

                    $conversation->conversation_path_id = $conversationPathId;

                    $conversation->current_node_index = 1;
                    $conversation->save();  

                } else {
                    
                    $twiml->say('there is converstaion path id but it is not found');
                }

                break;
            case 'script':
                // Script Action Node: Exec script, run returned action
                $script = $node['content']['script'] ?? null;
                if ($script && file_exists($script)) {

                    $result = include $script;
                    
                    if (is_string($result)) {
                        $twiml->say($result);
                    } elseif (is_array($result) && isset($result['action'])) {
                        if ($result['action'] === 'say') {
                            $twiml->say($result['value']);
                        } elseif ($result['action'] === 'redirect') {
                            $twiml->redirect($result['value']);
                        }
                    } else {
                        $twiml->say('Script did not return a valid action.');
                    }
                } else {
                    $twiml->say('Script not found.');
                }
                break;
            case 'websocket':
                Log::info('[ConversationPathService]: websocket', [
                    'conversation' => $conversation,
                    'node' => $node,
                ]);
                $endpointUrl = $node['content']['endpointUrl'] ?? null;
                if ($endpointUrl) {
                    $connect = $twiml->connect();
                    $connect->stream([
                        'url' => $endpointUrl,
                        'track' => 'both'
                    ]);
                } else {
                    $twiml->say('Websocket endpoint not specified.');
                }
                break;
            case 'sms':
                Log::info('[ConversationPathService]: sms', [
                    'conversation' => $conversation,
                    'node' => $node,
                ]);
                $to = $node['content']['to'] ?? null;
                $body = $node['content']['body'] ?? null;
                $user = request()->user();
                $body = $user ? ("From: $user->name $user->email\n$body") : $body;
                if (!$to || !$body) {
                    $twiml->say('Missing required parameters: to, body');
                    break;
                }
                try {
                    $sid = env('TWILIO_SID');
                    $token = env('TWILIO_TOKEN');
                    $twilioNumber = env('TWILIO_FROM');
                    $client = new \Twilio\Rest\Client($sid, $token);
                    $client->messages->create($to, [
                        'from' => $twilioNumber,
                        'body' => $body,
                    ]);
                    $twiml->say('SMS sent successfully.');
                } catch (\Exception $e) {
                    $twiml->say('Failed to send SMS: ' . $e->getMessage());
                }
                break;
            case 'email':
                Log::info('[ConversationPathService]: email', [
                    'conversation' => $conversation,
                    'node' => $node,
                ]);
                $to = $node['content']['to'] ?? null;
                $subject = $node['content']['subject'] ?? null;
                $body = $node['content']['body'] ?? null;
                if (!$to || !$subject || !$body) {
                    $twiml->say('Missing required parameters: to, subject, body');
                    break;
                }
                try {
                    \Mail::raw($body, function ($message) use ($to, $subject) {
                        $message->to($to)->subject($subject);
                    });
                    $twiml->say('Email sent successfully.');
                } catch (\Exception $e) {
                    $twiml->say('Failed to send email: ' . $e->getMessage());
                }
                break;
            case 'wait':
                $twiml->say('Waiting for ' . $node['content']['duration'] ?? 1 . ' seconds');
                $twiml->pause(['length' => $node['content']['duration'] ?? 1]);
                break;

            case 'assistantTool':

                    $toolId = $node['content']['toolId'] ?? null;
                    $tool = Tool::find($toolId);
                    if($tool){
                        $twiml->say('Tool Data found: ' . $toolId);
                    } else {
                        $twiml->say('Tool Data not found: ' . $toolId);
                    }
    
                    break;

            default:
                $twiml->say('THIS IS DEFAULT ACTION NOT IMPLEMENTED: ' . ($node['subtype'] ?? 'none'));
                break;
        }

        $twiml->redirect('/api/conversation-path-call/continue/' . $conversation->id);

        return response($twiml)->header('Content-Type', 'text/xml');
    }

    private function handleDecisionNode($conversation, $node, $request = null)
    {


        if($request){

            $dtmf = intval($request->input('Digits'));
            if($dtmf){

                //$conversation->path_state['dtmf_selection'] = $dtmf;
                //$conversation->save();

                Log::info('[ConversationPathService]: decision: path_state', [
                    'path_state' => $conversation->path_state,
                ]);
                Log::info('[ConversationPathService]: decision: request', [
                    'request' => $request->all(),
                ]);
                Log::info('[ConversationPathService]: decision: node actions', [
                    'node' => $node['actions'],
                ]);

                $actionNode = $node['actions'][$dtmf - 1] ?? null;   

                Log::info('[ConversationPathService]: decision: actionNode', [
                    'actionNode' => $actionNode,
                ]);

                if($actionNode){                                      

                    return $this->handleActionNode($conversation, $actionNode,$request);

                }
            }
        }



        $twiml = new \Twilio\TwiML\VoiceResponse();

        

        switch ($node['subtype']) {
            case 'user':
                $type = $node['content']['userDecisionType'] ?? null;
                switch ($type) {
                    case 'realtime':
                        // Realtime: Start assistant process and stream audio (like assistant action node)
                        $callSid = $conversation->path_state['twilio_call']['CallSid'] ?? null;
                        $assistantId = $node['content']['assistantId'] ?? 22;
                        $room = $callSid . '-' . $assistantId;
                        $basePath = config('app.base_path');
                        $command = "nohup /usr/bin/php {$basePath}/artisan bare:assistant-v2 {$room} {$assistantId} --second-delay=4 >> {$basePath}/storage/logs/assistant_{$room}.log 2>&1 & echo $!";
                        exec($command, $output);
                        $pid = (int)($output[0] ?? 0);
                        \Log::info("DecisionNode:user:realtime: process started", [$command, $output, $pid]);
                        $url = sprintf('wss://%s:%s/twilio-inbound/%s/%s', config('app.domain'), config('app.ws_port_alt'), $room, $callSid);
                        $connect = $twiml->connect();
                        $connect->stream(['url' => $url]);
                        break;
                    case 'askandwait':
                        // Ask and Wait: Use <Gather input="speech">
                        $prompt = $node['content']['message'] ?? 'Please say your response and press # when done.';
                        $gather = $twiml->gather([
                            'input' => 'speech',
                            'timeout' => 10,
                            'action' => '/api/conversation-path-call/continue/' . $conversation->id,
                            'method' => 'POST',
                            'finishOnKey' => '#',
                        ]);
                        $gather->say($prompt);
                        $twiml->say('No speech input received. Please try again.');
                        break;
                    case 'sms':
                        // SMS: Send SMS and say result
                        $to = $node['content']['smsTo'] ?? null;
                        $body = $node['content']['smsBody'] ?? null;
                        if (!$to || !$body) {
                            $twiml->say('Missing SMS recipient or body.');
                            break;
                        }
                        try {
                            $sid = env('TWILIO_SID');
                            $token = env('TWILIO_TOKEN');
                            $twilioNumber = env('TWILIO_FROM');
                            $client = new \Twilio\Rest\Client($sid, $token);
                            $client->messages->create($to, [
                                'from' => $twilioNumber,
                                'body' => $body,
                            ]);
                            $twiml->say('SMS sent successfully.');
                        } catch (\Exception $e) {
                            $twiml->say('Failed to send SMS: ' . $e->getMessage());
                        }
                        break;
                    case 'email':
                        // Email: Send email and say result
                        $to = $node['content']['emailTo'] ?? null;
                        $subject = $node['content']['emailSubject'] ?? null;
                        $body = $node['content']['emailBody'] ?? null;
                        if (!$to || !$subject || !$body) {
                            $twiml->say('Missing email recipient, subject, or body.');
                            break;
                        }
                        try {
                            \Mail::raw($body, function ($message) use ($to, $subject) {
                                $message->to($to)->subject($subject);
                            });
                            $twiml->say('Email sent successfully.');
                        } catch (\Exception $e) {
                            $twiml->say('Failed to send email: ' . $e->getMessage());
                        }
                        break;
                    default:
                        // Legacy: DTMF menu (phone tree)
                        

                        $gather = $twiml->gather([
                            'input' => 'dtmf',
                            'numDigits' => 1,
                            'timeout' => 10,
                            'action' => '/api/conversation-path-call/continue/' . $conversation->id,
                            'method' => 'POST'
                        ]);

                        if(!empty($node['content']['message'])){
                            $gather->say($node['content']['message']);
                        }
                
                        if(!empty($node['content']['audioFileId'])){
                            $gather->play("/api/audio-files/{$node['content']['audioFileId']}/serve");
                        }
                        
                        
                        $twiml->say('No input received. Please try again.');
                        break;
                }
                break;

            case 'assistant':
                // Assistant Decision Node: Log state, prompt, assistant id
                \Log::info('[DecisionNode:assistant]', [
                    'state' => $conversation->path_state,
                    'prompt' => $node['content']['prompt'] ?? null,
                    'assistant_id' => $node['content']['assistantId'] ?? null,
                ]);
                $twiml->say('Assistant decision node logged.');
                break;
            case 'conditional':
                // Conditional Decision Node: Handle different condition types
                $conditionType = $node['content']['conditionType'] ?? null;
                $variable = $node['content']['variable'] ?? null;
                $value = $node['content']['value'] ?? null;
                $script = $node['content']['script'] ?? null;
                $returnType = $node['content']['returnType'] ?? 'boolean';

                switch ($conditionType) {
                    case 'valueExists':
                        $path_state = $conversation->path_state ?? [];                       
                        //$conversation->path_state = $path_state;
                        //$conversation->save();

                        Log::info('[ConversationPathService]: DecisionNode: valueExists', [                            
                            'conversation' => $conversation,
                        ]);

                        Log::info('[ConversationPathService]:********************');

                        Log::info('[ConversationPathService]: decision: path_state', [
                            'path_state' => $path_state,
                        ]);
                        Log::info('[ConversationPathService]:********************');


                        Log::info('[ConversationPathService]: decision: valueExists', [
                            
                            'variable' => $variable,
                            
                            'exists' => isset($path_state[$variable]) || isset($conversation[$variable]),
                        ]);

                        $exists = isset($path_state[$variable]) || isset($conversation[$variable]);
                        if ($returnType === 'boolean') {

                            Log::info('[ConversationPathService]: DecisionNode: valueExists', [
                                'exists' => $exists,
                            ]);
                            $choice = $exists ? 1 : 2;
                            $actionNode = $node['actions'][$choice - 1] ?? null;  

                            Log::info('[ConversationPathService]: decision: actionNode', [
                                'actionNode' => $actionNode,
                            ]);

                            if($actionNode){                                      

                                return $this->handleActionNode($conversation, $actionNode,$request);

                            }

                            
                        } else {                     
                            
                            
                            // For index return type, we'll use the value as the index
                            //$nextNode = $exists ? $value : null;
                        }
                        break;

                    case 'hasProperty':
                        $object = $pathState[$variable] ?? $conversation[$variable] ?? null;
                        $hasProperty = is_array($object) && isset($object[$value]);
                        if ($returnType === 'boolean') {
                            $twiml->say($hasProperty ? 'Property exists' : 'Property does not exist');
                        } else {
                            // For index return type, we'll use the value as the index
                            $nextNode = $hasProperty ? $value : null;
                        }
                        break;

                    case 'propertyEquals':
                        $object = $pathState[$variable] ?? $conversation[$variable] ?? null;
                        $propertyValue = is_array($object) ? ($object[$value] ?? null) : null;
                        $equals = $propertyValue === $value;
                        if ($returnType === 'boolean') {
                            $twiml->say($equals ? 'Property equals value' : 'Property does not equal value');
                        } else {
                            // For index return type, we'll use the value as the index
                            $nextNode = $equals ? $value : null;
                        }
                        break;

                    case 'script':
                        if ($script && file_exists($script)) {
                            $result = include $script;
                            if (is_array($result) && isset($result['type'], $result['value'])) {
                                if ($result['type'] === 'say') {
                                    $twiml->say($result['value']);
                                } elseif ($result['type'] === 'redirect') {
                                    $twiml->redirect($result['value']);
                                }
                            } else {
                                $twiml->say('Conditional script did not return a valid action.');
                            }
                        } else {
                            $twiml->say('Conditional script not found.');
                        }
                        break;
                        case 'assistantTool':

                            $toolId = $node['content']['toolId'] ?? null;
                            $tool = Tool::find($toolId);
                            if($tool){
                                $twiml->say('Tool decision found: ' . $toolId);
                            } else {
                                $twiml->say('Tool decision not found: ' . $toolId);
                            }
            
                            break;

                    default:
                        $twiml->say('Invalid condition type.');
                        break;
                }
                break;
            default:
                $twiml->say('Decision not implemented: ' . ($node['subtype'] ?? 'none'));
                break;
        }
        return response($twiml)->header('Content-Type', 'text/xml');
    }

    private function handleDataNode($conversation, $node, $request = null)
    {

        $conversation->current_node_index++;
        $conversation->save();
        $twiml = new \Twilio\TwiML\VoiceResponse();
        switch ($node['subtype']) {

            case 'assistantTool':

                Log::info('[ConversationPathService]: data: assistantTool', [
                    'node' => $node,
                ]);
                

                $tools = $node['content']['tools'] ?? [];
                foreach($tools as $nodeTool){
                    $toolId = $nodeTool['toolId'] ?? null;
                    $tool = Tool::find($toolId);

                    Log::info('[ConversationPathService]: data: assistantTool: tool', [
                        'tool' => $tool,
                        'nodeTool' => $nodeTool,
                    ]);

                    if($tool){
                        $twiml->say('Tool data node found: ' . $toolId);

                        $parameters = $nodeTool['parameters'] ?? [];

                        $tool_params = [];
                        
                        foreach($parameters as $paramaterName => $paramaterValue){
                            Log::info('[ConversationPathService]: data: assistantTool: parameter', [
                                'paramaterName' => $paramaterName,
                                'paramaterValue' => $paramaterValue,
                            ]);
                           
                            $twiml->say('Parameter: ' . $paramaterName . ' . ');

                                if(str_starts_with($paramaterValue, '#')){
                                    $paramaterValue = $this->resolveUiVariable($paramaterValue,$conversation);
                                }

                            $tool_params[$paramaterName] = $paramaterValue;
                        }

                                    // Loop through optional objects
                            $optional_objects = [                          
                                
                                new RainbowExecutor(),
                                new RainbowDashboardTicketExecutor(),
                                new RichBotExecutor(),
                                
                            ];

                            foreach ($optional_objects as $index => $object) {
                                $class_name = get_class($object);
                                if (method_exists($object, $tool->name)) {                       

                                
                                    if(method_exists($object, 'setConversation')) {
                                        $object->setConversation($conversation);  
                                    }

                                    $data = call_user_func([$object, $tool->name], $tool_params);
                                    
                                    Log::info('[ConversationPathService][FUNCTION CALLING] Optional object method execution completed', [
                                        'class' => $class_name,
                                        'method' => $tool->name,
                                        'has_data' => !is_null($data)
                                    ]);

                                    Log::info('[ConversationPathService][FUNCTION CALLING] data', [
                                        'data' => $data,
                                    ]);

                                    if(isset($data['message'])){
                                        $twiml->say($data['message']);
                                    }

                                   

                                    if(isset($data['success'])){                                

                                        $path_state = $conversation->path_state ?? [];
                                        $path_state['tool_data'][] = $data;
                                        $conversation->path_state = $path_state;
                                        $conversation->save();

                                        Log::info('[ConversationPathService][FUNCTION CALLING] path state tool_data', [
                                            'tool_data' => $path_state['tool_data'],
                                        ]);

                                    }

                                     
                                    break;
                                }
                            }
                        

                        // do executors like relay, etc.
                        



                    } else {
                        $twiml->say('Tool data node not found: ' . $toolId);
                    }
                }

                break;               

            case 'file':

                $selected = $node['content']['selected'] ?? [];
                // Add to conversation path_state

                $path_state = $conversation->path_state ?? [];
                $path_state['file_data_node_selected'][] = $selected;
                $conversation->path_state = $path_state;
                $conversation->save();

                if (empty($selected)) {
                    Log::info('[ConversationPathService]: file: no files or folders were selected');                    
                    //$twiml->say('No files or folders were selected.');
                } else {

                    Log::info('[ConversationPathService]: file: files or folders have been added to the conversation state');
                    //$twiml->say('Files or folders have been added to the conversation state.');

                }

                break;
            case 'outageCheck':
                $twiml->say('OutageCheck data node not implemented');
                break;
            case 'customerLookup':
                $twiml->say('CustomerLookup data node not implemented');
                break;
            case 'custom':
                // Custom Data Node: Exec script, add result as system message
                $scriptId = $node['content']['scriptId'] ?? null;

                $service = new RainbowDashboardScriptService();
                $result = $service->executeScript($scriptId, $conversation);


                if ($result) {


                    $twiml->say('Custom data processed.');
                } else {
                    $twiml->say('Custom data script not found.');
                }
                break;
            case 'contextAssistant':
                // Context Assistant Data Node: Log prompt, add to conversation system message
                $prompt = $node['content']['prompt'] ?? '';
                \Log::info('[DataNode:contextAssistant]', [
                    'prompt' => $prompt,
                    'conversation_id' => $conversation->id,
                ]);
                $conversation->system_message = ($conversation->system_message ?? '') . "\n" . $prompt;
                $conversation->save();
                $twiml->say('Context assistant prompt added.');
                break;
            case 'APIData':
                $twiml->say('APIData data node not implemented');
                break;
            default:
                $twiml->say('Data node not implemented: ' . ($node['subtype'] ?? 'none'));
                break;
        }

        

        $twiml->redirect('/api/conversation-path-call/continue/' . $conversation->id);
        return response($twiml)->header('Content-Type', 'text/xml');
    }

    private function resolveUiVariable($wildcard, $conversation) {

        Log::info('[ConversationPathService]: resolveUiVariable: wildcard', [
            'wildcard' => $wildcard,
            'conversation' => $conversation,
        ]);

        $path_state = $conversation->path_state ?? [];

        Log::info('[ConversationPathService]: resolveUiVariable: path_state', [
            'path_state' => $path_state,
        ]);

        if (strpos($wildcard, '#') !== 0) {
            Log::info('[ConversationPathService]: resolveUiVariable: not a variable', [
                'wildcard' => $wildcard,
            ]);
            return null; // Not a variable
        }
    
        try {
            $wildcard = substr($wildcard, 1); // 


            if(str_contains($wildcard, '?') && str_contains($wildcard, '=') && str_contains($wildcard, ':')){
                Log::info('[ConversationPathService]: resolveUiVariable: condition', [
                    'wildcard' => $wildcard,
                ]);
                $parts = explode('?', $wildcard);
                $condition = $parts[0];
                $wildcard = $parts[1];

                $parts = explode('=', $condition);
                $condition_key = $parts[0];
                $condition_value_check = $parts[1];

                Log::info('[ConversationPathService]: resolveUiVariable: condition_key', [
                    'condition_key' => $condition_key,
                    'condition_value_check' => $condition_value_check,
                ]);


                $condition_key_parts = explode('.', $condition_key);

                $condition_value = $path_state;

                Log::info('[ConversationPathService]: resolveUiVariable: condition_value', [
                    
                    'condition_value' => $condition_value,
                ]);
                
                foreach ($condition_key_parts as $part) {

                    Log::info('[ConversationPathService]: resolveUiVariable: condition_value', [
                        'condition_value' => $condition_value,
                        'part' => $part,
                    ]);

                    if (!isset($condition_value[$part])) {
                        Log::info('[ConversationPathService]: resolveUiVariable: condition_key_parts', [
                            'condition_key_parts' => $condition_key_parts,
                            'part' => $part,
                        ]);
                        return null; // or handle missing key case
                    }
                    $condition_value = $condition_value[$part];
                }


                $parts = explode(':', $wildcard);
                $true_key = $parts[0];
                $else_key = $parts[1];

               

                if($condition_value != $condition_value_check){
                    Log::info('[ConversationPathService]: resolveUiVariable: condition not met', [
                        'condition_key' => $condition_key,
                        'condition_value_check' => $condition_value_check,
                        'condition_value' => $condition_value,
                    ]);
                    $wildcard = $else_key;
                } else {
                    Log::info('[ConversationPathService]: resolveUiVariable: condition met', [
                        'condition_key' => $condition_key,
                        'condition_value_check' => $condition_value_check,
                        'condition_value' => $condition_value,
                    ]);
                    $wildcard = $true_key;
                }

                

            }

            Log::info('[ConversationPathService]: resolveUiVariable: wildcard ****************', [
                'wildcard' => $wildcard,
            ]);

            $parts = explode('.', $wildcard);

            $value = $conversation->path_state;
           
            foreach($parts as $part){
             
                    if (!isset($value[$part])) {
                        return null; // or handle missing key case
                    }
                    $value = $value[$part];
            }

            Log::info('[ConversationPathService]: resolveUiVariable: value', [
                'value' => $value,
            ]);

            return $value;
        
    
        } catch (Exception $e) {
            return null; // Return null for parsing errors or missing keys
        }
    }

    private function handleEntryNode($conversation, $node, $request = null)
    {
        // TODO: Implement entry node logic
        return response('<Response><Say>Entry node not implemented</Say></Response>', 200)->header('Content-Type', 'text/xml');
    }

    private function handleInfoNode($conversation, $node, $request = null)
    {
        $twiml = new \Twilio\TwiML\VoiceResponse();
        $twiml->say('Info node not implemented');
        return response($twiml)->header('Content-Type', 'text/xml');
    }    

    // Add more methods as needed for stepping, updating state, etc.
} 