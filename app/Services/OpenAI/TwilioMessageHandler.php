<?php

namespace App\Services\OpenAI;

use Illuminate\Support\Facades\Log;
use App\Services\Logging\TwilioLogger;


class TwilioMessageHandler
{
    private $streamSid;
    private $chatId;



    public function __construct($chatId, $streamSid = null)
    {
        $this->chatId = $chatId;
        $this->streamSid = $streamSid;
    }


    public function setChatId($chatId)
    {
        $this->chatId = $chatId;
        Log::info("Chat ID set", [
            'chat_id' => $this->chatId,
            'stream_sid' => $this->streamSid
        ]);
    }


    public function setStreamSid($streamSid)
    {
        $this->streamSid = $streamSid;
        Log::info("Stream SID set", [
            'chat_id' => $this->chatId,
            'stream_sid' => $streamSid
        ]);
    }

    /**
     * Convert Twilio message to Richbot format
     * 
     *   // Handle Twilio stream events
        if (isset($event['StreamEvent'])) {
            if ($event['StreamEvent'] === 'stream-started' && isset($event['StreamSid'])) {
                $this->streamSid = $event['StreamSid'];
                Log::info("Twilio stream started from status event", [
                    'stream_sid' => $this->streamSid,
                    'chat_id' => $this->chatId
                ]);
                return;
            }
        }

        // Handle Twilio start event
        if (isset($event['event']) && $event['event'] === 'start') {
            if (isset($event['start']['streamSid'])) {
                $this->streamSid = $event['start']['streamSid'];
                Log::info("Twilio stream started from start event", [
                    'stream_sid' => $this->streamSid,
                    'chat_id' => $this->chatId
                ]);
                return;
            }
        }

        // Handle media events
        if (isset($event['event']) && $event['event'] === 'media') {
            if (!$this->streamSid && isset($event['streamSid'])) {
                $this->streamSid = $event['streamSid'];
                Log::info("Twilio stream SID captured from media event", [
                    'stream_sid' => $this->streamSid,
                    'chat_id' => $this->chatId
                ]);
            }
        }
     */
    public function convertToRichbotMessage($message)
    {
        // Add debug logging here
        Log::debug("Converting Twilio message", [
            'input' => $message,
            'chat_id' => $this->chatId,
            'stream_sid' => $this->streamSid
        ]);

        // Log incoming message from Twilio
        TwilioLogger::inbound($message, [
            'chat_id' => $this->chatId,
            'stream_sid' => $this->streamSid
        ]);

        // Handle raw media message from Twilio
        if (isset($message['media']) && isset($message['media']['payload'])) {
            $audioBytes = base64_decode($message['media']['payload']);





            
            $converted = [
                'type' => 'input_audio_buffer.append',
                'audio' => $audioBytes
            ];

            TwilioLogger::converted($message, $converted, [
                'chat_id' => $this->chatId,
                'conversion' => 'twilio_audio_to_richbot'
            ]);

            return $converted;
        }

        // Handle Twilio stream events
        if (isset($message['StreamEvent'])) {
            if ($message['StreamEvent'] === 'stream-started' && isset($message['StreamSid'])) {
                $this->setStreamSid($message['StreamSid']);
            }
            return null;
        }

        // Handle Twilio start event
        if (isset($message['event']) && $message['event'] === 'start') {
            if (isset($message['start']['streamSid'])) {
                $this->setStreamSid($message['start']['streamSid']);
            }
            return null;
        }

        return null;
    }

    /**
     * Convert Richbot message to Twilio format
     */
    public function convertToTwilioMessage($message,$streamSid = null)
    {
        

        // Handle audio data
        if (isset($message['type']) && $message['type'] === 'assistant_audio_delta') {
            $converted = [
                'event' => 'media',
                'streamSid' => $streamSid,
                'media' => [
                    'payload' => base64_encode($message['data']['delta'])
                ]
            ];

            TwilioLogger::converted($message, $converted, [
                'chat_id' => $this->chatId,
                'conversion' => 'richbot_audio_to_twilio'
            ]);

            return $converted;
        }

        return null;
    }
} 