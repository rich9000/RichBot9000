<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AlexaController extends Controller
{
    public function handle(Request $request)
    {
        // Log the incoming request
        Log::info('Alexa Request Received', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'ip' => $request->ip()
        ]);

        // Basic validation for Alexa requests
        if (!$request->has('request')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request format'
            ], 400);
        }

        // Handle different types of Alexa requests
        $requestType = $request->input('request.type');
        
        switch ($requestType) {
            case 'LaunchRequest':
                return $this->handleLaunchRequest($request);
            case 'IntentRequest':
                return $this->handleIntentRequest($request);
            case 'SessionEndedRequest':
                return $this->handleSessionEndedRequest($request);
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Unsupported request type'
                ], 400);
        }
    }

    protected function handleLaunchRequest(Request $request)
    {
        Log::info('Alexa Launch Request', [
            'request' => $request->all()
        ]);

        return response()->json([
            'version' => '1.0',
            'response' => [
                'outputSpeech' => [
                    'type' => 'PlainText',
                    'text' => 'Welcome to Richbot nine thousand. I am the Assistant Assistnat, How can I help you today? need a list of available commands? say "help"'
                ],
                'shouldEndSession' => false
            ]
        ]);
    }

    protected function handleIntentRequest(Request $request)
    {
        Log::info('Alexa Intent Request', [
            'request' => $request->all()
        ]);

        $intent = $request->input('request.intent.name');
        
        // Handle different intents here
        switch ($intent) {
            case 'SearchKnowledgeBase':
                return $this->handleSearchIntent($request);
            default:
                return response()->json([
                    'version' => '1.0',
                    'response' => [
                        'outputSpeech' => [
                            'type' => 'PlainText',
                            'text' => 'I\'m not sure how to handle that request.'
                        ],
                        'shouldEndSession' => false
                    ]
                ]);
        }
    }

    protected function handleSessionEndedRequest(Request $request)
    {
        Log::info('Alexa Session Ended', [
            'request' => $request->all()
        ]);

        return response()->json([
            'version' => '1.0',
            'response' => [
                'shouldEndSession' => true
            ]
        ]);
    }

    protected function handleSearchIntent(Request $request)
    {
        $searchQuery = $request->input('request.intent.slots.search.value');
        
        Log::info('Alexa Search Intent', [
            'search_query' => $searchQuery
        ]);

        // Here you would integrate with your RainbowKnowledgeBaseExecutor
        // For now, just return a placeholder response
        return response()->json([
            'version' => '1.0',
            'response' => [
                'outputSpeech' => [
                    'type' => 'PlainText',
                    'text' => "I searched for {$searchQuery} in the knowledge base."
                ],
                'shouldEndSession' => false
            ]
        ]);
    }
} 