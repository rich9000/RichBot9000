<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OpenAIRealtimeMessageHandler
{
    private $richbotConn;
    private $chatId;
    
    public function __construct($richbotConn, $chatId)
    {
        $this->richbotConn = $richbotConn;
        $this->chatId = $chatId;
    }

    public function handleServerEvent($msg)
    {
        $message = json_decode($msg, true);
        $type = $message['type'] ?? '';

        switch ($type) {
            case 'response.text.delta':
                $this->handleTextDelta($message);
                break;
                
            case 'response.audio.delta':
                $this->handleAudioDelta($message);
                break;
                
            case 'error':
                $this->handleError($message);
                break;
                
            // Add other event types as needed
        }
    }

    private function handleTextDelta($message)
    {
        try {
            $this->richbotConn->send(json_encode([
                'type' => 'assistant_text_delta',
                'chat_id' => $this->chatId,
                'data' => $message['delta']
            ]));
        } catch (\Exception $e) {
            Log::error("Error sending text delta", ['error' => $e->getMessage()]);
        }
    }

    private function handleAudioDelta($message)
    {
        try {
            $this->richbotConn->send(json_encode([
                'type' => 'assistant_audio_delta',
                'chat_id' => $this->chatId,
                'data' => $message['delta']
            ]));
        } catch (\Exception $e) {
            Log::error("Error sending audio delta", ['error' => $e->getMessage()]);
        }
    }

    private function handleError($message)
    {
        Log::error("OpenAI Error", [
            'error' => $message['error'],
            'chat_id' => $this->chatId
        ]);
        
        try {
            $this->richbotConn->send(json_encode([
                'type' => 'error',
                'chat_id' => $this->chatId,
                'data' => $message['error']
            ]));
        } catch (\Exception $e) {
            Log::error("Error sending error message", ['error' => $e->getMessage()]);
        }
    }

    public function createInitialSession()
    {
        return json_encode([
            'type' => 'session.update',
            'session' => [
                'modalities' => ['text', 'audio'],
                'instructions' => 'You are a helpful assistant.',
                'voice' => 'sage',
                'input_audio_format' => 'pcm16',
                'output_audio_format' => 'pcm16',
                'input_audio_transcription' => [
                    'model' => 'whisper-1'
                ]
            ]
        ]);
    }

    public function handleClientMessage($msg)
    {
        $message = json_decode($msg, true);
        
        switch ($message['type']) {
            case 'user_text':
                return $this->createTextMessage($message['data']);
                
            case 'user_audio':
                return $this->createAudioMessage($message['data']);
                
            default:
                Log::warning("Unknown message type from client", ['type' => $message['type']]);
                return null;
        }
    }

    private function createTextMessage($text)
    {
        return json_encode([
            'type' => 'conversation.item.create',
            'item' => [
                'type' => 'message',
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'input_text',
                        'text' => $text
                    ]
                ]
            ]
        ]);
    }

    private function createAudioMessage($base64Audio)
    {
        return json_encode([
            'type' => 'input_audio_buffer.append',
            'audio' => $base64Audio
        ]);
    }
} 