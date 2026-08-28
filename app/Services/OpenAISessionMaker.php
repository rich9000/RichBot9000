<?php

namespace App\Services;

use App\Models\Assistant;
use App\Models\Conversation;
use App\Models\ConversationPath;
use Illuminate\Support\Facades\Log;

class OpenAISessionMaker
{
    protected $defaultConfig = [
        'modalities' => ['audio', 'text'],
        'input_audio_format' => 'pcm16',
        'output_audio_format' => 'pcm16',
        'temperature' => 0.7,
        'tool_choice' => 'auto',
        'max_response_output_tokens' => 'inf',
        'turn_detection' => [
            'type' => 'server_vad',
            'prefix_padding_ms' => 300,
            'silence_duration_ms' => 500,
            'threshold' => 0.5
        ],
        'input_audio_transcription' => [
            'model' => 'whisper-1'
        ]
    ];

    protected $turnDetectionPresets = [
        'aggressive' => [
            'type' => 'server_vad',
            'prefix_padding_ms' => 200,
            'silence_duration_ms' => 300,
            'threshold' => 0.4,
            'create_response' => true,
            'interrupt_response' => true
        ],
        'conservative' => [
            'type' => 'server_vad',
            'prefix_padding_ms' => 400,
            'silence_duration_ms' => 800,
            'threshold' => 0.6,
            'create_response' => true,
            'interrupt_response' => false
        ],
        'semantic' => [
            'type' => 'semantic_vad',
            'create_response' => true,
            'interrupt_response' => true,
            'eagerness' => 'auto'
        ],
        'disabled' => null
    ];

    /**
     * Generate a complete session configuration
     * 
     * @param Assistant $assistant The assistant model
     * @param Conversation $conversation The conversation model
     * @param array $overrides Optional configuration overrides
     * @return array The complete session configuration
     */
    public function generateSessionConfig(Assistant $assistant, Conversation $conversation, array $overrides = [])
    {
        $config = array_merge($this->defaultConfig, [
            'instructions' => $this->generateInstructions($assistant, $conversation),
            'tools' => $this->generateTools($assistant),
            'voice' => $overrides['voice'] ?? 'alloy',
            'model' => $assistant->model ? $assistant->model->name : 'gpt-4o-realtime-preview'
        ], $overrides);

        // Remove any null values to use defaults
        $config = array_filter($config, function($value) {
            return $value !== null;
        });

        return $config;
    }

    /**
     * Generate instructions combining conversation and assistant settings
     * 
     * @param Assistant $assistant The assistant model
     * @param Conversation $conversation The conversation model
     * @return string Combined instructions
     */
    protected function generateInstructions(Assistant $assistant, Conversation $conversation)
    {
        $instructions = '';

        // Add conversation system message if exists
        if (!empty($conversation->system_message)) {
            $instructions .= $conversation->system_message . "\n\n";
        }

        // Include path state
        $instructions .= "PATH STATE:\n\n" . json_encode($conversation->path_state) . "\n\n:END PATH STATE;\n\n";

        // Include conversation history
        $instructions .= "CONVERSATION HISTORY:\n\n" . json_encode($conversation->getConversationMessages()) . "\n\n:END CONVERSATION HISTORY;\n\n";

        // Add general instructions
        $instructions .= "INSTRUCTIONS:\n
        Use the PATH STATE and CONVERSATION HISTORY for context if needed when following your instructions. 
        ASSISTANT INSTRUCTIONS ARE THE IMPORTANT ONES TO FOLLOW.
        \n:END INSTRUCTIONS;\n\n";

        // Add assistant instructions
        $instructions .= "ASSISTANT INSTRUCTIONS:\n\n" . $assistant->system_message . "\n\n:END ASSISTANT INSTRUCTIONS;\n\n";

        // Add node prompt if in a conversation path
        if ($conversation->current_node_index) {
            $conversation_path = ConversationPath::find($conversation->conversation_path_id);
            
            if ($conversation_path && 
                isset($conversation_path->nodes[$conversation->current_node_index]) && 
                isset($conversation_path->nodes[$conversation->current_node_index]['content']['prompt'])) {
                
                $node_prompt = $conversation_path->nodes[$conversation->current_node_index]['content']['prompt'];
                $instructions .= "NODE PROMPT:\n\n" . $node_prompt . "\n\n:END NODE PROMPT;\n\n";
            }
        }

        return $instructions;
    }

    /**
     * Generate tools configuration for an assistant
     * 
     * @param Assistant $assistant The assistant model
     * @return array The tools configuration
     */
    protected function generateTools(Assistant $assistant)
    {
        if ($assistant->tools->isEmpty()) {
            return [];
        }

        return $assistant->tools->map(function ($tool) {
            $toolConfig = [
                'type' => 'function',
                'name' => $tool->name,
                'description' => $tool->description ?? "Tool to " . str_replace('_', ' ', $tool->name)
            ];

            if ($tool->parameters()->count()) {
                $toolConfig['parameters'] = [
                    'type' => 'object',
                    'properties' => $tool->parameters()->get()->mapWithKeys(function ($param) {
                        $property = [
                            'type' => $param->type ?? 'string',
                            'description' => $param->description ?? "The " . str_replace('_', ' ', $param->name) . " parameter"
                        ];

                        if ($param->type === 'array') {
                            $property['items'] = [
                                'type' => 'string'
                            ];
                        }

                        return [$param->name => $property];
                    })->toArray(),
                    'required' => $tool->parameters()->where('required', true)->pluck('name')->toArray()
                ];
            }

            return $toolConfig;
        })->toArray();
    }

    /**
     * Get a preset turn detection configuration
     * 
     * @param string $presetName Name of the preset ('aggressive', 'conservative', 'semantic', 'disabled')
     * @return array|null Turn detection configuration
     */
    public function getTurnDetectionPreset(string $presetName)
    {
        if (!isset($this->turnDetectionPresets[$presetName])) {
            Log::warning("[OpenAISessionMaker] Unknown turn detection preset", [
                'requested_preset' => $presetName,
                'available_presets' => array_keys($this->turnDetectionPresets)
            ]);
            return $this->turnDetectionPresets['conservative'];
        }

        return $this->turnDetectionPresets[$presetName];
    }

    /**
     * Update session configuration with new settings
     * 
     * @param array $currentConfig Current session configuration
     * @param array $updates New configuration values
     * @return array Updated configuration
     */
    public function updateSessionConfig(array $currentConfig, array $updates)
    {
        // Deep merge the configurations
        $config = array_merge_recursive($currentConfig, $updates);

        // Remove any null values to use defaults
        $config = array_filter($config, function($value) {
            return $value !== null;
        });

        return $config;
    }

    /**
     * Generate turn detection configuration
     * 
     * @param array $config Optional configuration overrides
     * @return array|null Turn detection configuration or null if disabled
     */
    public function generateTurnDetectionConfig(array $config = [])
    {
        if (isset($config['turn_detection']) && $config['turn_detection'] === null) {
            return null;
        }

        return array_merge($this->defaultConfig['turn_detection'], $config['turn_detection'] ?? []);
    }

    /**
     * Generate audio transcription configuration
     * 
     * @param array $config Optional configuration overrides
     * @return array|null Audio transcription configuration or null if disabled
     */
    public function generateAudioTranscriptionConfig(array $config = [])
    {
        if (isset($config['input_audio_transcription']) && $config['input_audio_transcription'] === null) {
            return null;
        }

        return array_merge($this->defaultConfig['input_audio_transcription'], $config['input_audio_transcription'] ?? []);
    }

    /**
     * Generate a speech started message to clear buffers and cancel responses
     * 
     * @return array Message to send to OpenAI
     */
    public function generateSpeechStartedMessage()
    {
        return [
            'type' => 'response.cancel'
        ];
    }

    /**
     * Generate a clear buffer message for the client
     * 
     * @param string $streamSid The stream ID
     * @return array Message to send to client
     */
    public function generateClearBufferMessage($streamSid)
    {
        return [
            'streamSid' => $streamSid,
            'event' => 'clear'
        ];
    }

    /**
     * Generate a complete speech interruption sequence
     * 
     * @param string $streamSid The stream ID
     * @return array Messages to send in sequence
     */
    public function generateSpeechInterruptionSequence($streamSid)
    {
        return [
            'client' => $this->generateClearBufferMessage($streamSid),
            'openai' => $this->generateSpeechStartedMessage()
        ];
    }

    /**
     * Generate a speech stopped message
     * 
     * @return array Message to send to OpenAI
     */
    public function generateSpeechStoppedMessage()
    {
        return [
            'type' => 'response.create'
        ];
    }
} 