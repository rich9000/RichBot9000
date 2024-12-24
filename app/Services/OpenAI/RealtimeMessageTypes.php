<?php

namespace App\Services\OpenAI;

class RealtimeMessageTypes
{
    // Server Events
    const ERROR = 'error';
    const SESSION_CREATED = 'session.created';
    const SESSION_UPDATED = 'session.updated';
    const CONVERSATION_CREATED = 'conversation.created';
    const CONVERSATION_ITEM_CREATED = 'conversation.item.created';
    const CONVERSATION_ITEM_INPUT_AUDIO_TRANSCRIPTION_COMPLETED = 'conversation.item.input_audio_transcription.completed';
    const CONVERSATION_ITEM_INPUT_AUDIO_TRANSCRIPTION_FAILED = 'conversation.item.input_audio_transcription.failed';
    const CONVERSATION_ITEM_TRUNCATED = 'conversation.item.truncated';
    const CONVERSATION_ITEM_DELETED = 'conversation.item.deleted';
    const INPUT_AUDIO_BUFFER_COMMITTED = 'input_audio_buffer.committed';
    const INPUT_AUDIO_BUFFER_CLEARED = 'input_audio_buffer.cleared';
    const INPUT_AUDIO_BUFFER_SPEECH_STARTED = 'input_audio_buffer.speech_started';
    const INPUT_AUDIO_BUFFER_SPEECH_STOPPED = 'input_audio_buffer.speech_stopped';
    const RESPONSE_CREATED = 'response.created';
    const RESPONSE_DONE = 'response.done';
    const RESPONSE_OUTPUT_ITEM_ADDED = 'response.output_item.added';
    const RESPONSE_OUTPUT_ITEM_DONE = 'response.output_item.done';
    const RESPONSE_CONTENT_PART_ADDED = 'response.content_part.added';
    const RESPONSE_CONTENT_PART_DONE = 'response.content_part.done';
    const RESPONSE_TEXT_DELTA = 'response.text.delta';
    const RESPONSE_TEXT_DONE = 'response.text.done';
    const RESPONSE_AUDIO_TRANSCRIPT_DELTA = 'response.audio_transcript.delta';
    const RESPONSE_AUDIO_TRANSCRIPT_DONE = 'response.audio_transcript.done';
    const RESPONSE_AUDIO_DELTA = 'response.audio.delta';
    const RESPONSE_AUDIO_DONE = 'response.audio.done';
    const RESPONSE_FUNCTION_CALL_ARGUMENTS_DELTA = 'response.function_call_arguments.delta';
    const RESPONSE_FUNCTION_CALL_ARGUMENTS_DONE = 'response.function_call_arguments.done';
    const RATE_LIMITS_UPDATED = 'rate_limits.updated';

    // Client Events
    const SESSION_UPDATE = 'session.update';
    const INPUT_AUDIO_BUFFER_APPEND = 'input_audio_buffer.append';
    const INPUT_AUDIO_BUFFER_COMMIT = 'input_audio_buffer.commit';
    const INPUT_AUDIO_BUFFER_CLEAR = 'input_audio_buffer.clear';
    const CONVERSATION_ITEM_CREATE = 'conversation.item.create';
    const CONVERSATION_ITEM_TRUNCATE = 'conversation.item.truncate';
    const CONVERSATION_ITEM_DELETE = 'conversation.item.delete';
    const RESPONSE_CREATE = 'response.create';
    const RESPONSE_CANCEL = 'response.cancel';
} 