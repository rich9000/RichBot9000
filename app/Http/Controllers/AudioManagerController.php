<?php

namespace App\Http\Controllers;

use App\Models\AudioFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Exception;

class AudioManagerController extends Controller
{
    public function index(Request $request)
    {
        $query = AudioFile::query();
        
        // Filter by type if provided
        if ($request->has('type')) {
      //      $query->where('type', $request->type);
        }
        
        // Filter by context if provided
        if ($request->has('context')) {
      //      $query->where('context', $request->context);
        }

        // Filter by user_id if not admin
        if (!$request->user()->hasRole('admin')) {
      //      $query->where('user_id', $request->user()->id);
        }

        $audioFiles = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $audioFiles
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|mimes:mp3,wav,ogg|max:10240', // 10MB max
            'source_type' => 'required|in:upload,recording',
            'type' => 'required|string|in:' . implode(',', array_keys(AudioFile::getAvailableTypes())),
            'context' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        $path = $file->store('audio', 'public');

        $audioFile = AudioFile::create([
            'name' => $request->name,
            'description' => $request->description,
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'source_type' => $request->source_type,
            'type' => $request->type,
            'context' => $request->context,
            'user_id' => $request->user()->id,
            'file_size' => $file->getSize(),
            'metadata' => [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize()
            ]
        ]);

        return response()->json([
            'success' => true,
            'data' => $audioFile
        ]);
    }

    public function show(Request $request, AudioFile $audioFile)
    {
        // Check if user has permission to view this file
        if (!$request->user()->hasRole('admin') && $audioFile->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to audio file'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $audioFile
        ]);
    }

    public function update(Request $request, AudioFile $audioFile)
    {
        // Check if user has permission to update this file
        if (!$request->user()->hasRole('admin') && $audioFile->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to audio file'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:' . implode(',', array_keys(AudioFile::getAvailableTypes())),
            'context' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'file' => 'nullable|file|mimes:mp3,wav,ogg|max:10240' // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // If a new file is provided, update the file
        if ($request->hasFile('file')) {
            // Delete the old file
            Storage::disk('public')->delete($audioFile->file_path);
            
            // Store the new file
            $file = $request->file('file');
            $path = $file->store('audio', 'public');
            
            // Update file-related fields
            $audioFile->file_path = $path;
            $audioFile->file_type = $file->getMimeType();
            $audioFile->file_size = $file->getSize();
            $audioFile->metadata = array_merge($audioFile->metadata ?? [], [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'updated_at' => now()
            ]);
        }

        // Update other fields
        $audioFile->update($request->only(['name', 'description', 'type', 'context', 'is_active']));

        return response()->json([
            'success' => true,
            'data' => $audioFile
        ]);
    }

    public function destroy(Request $request, AudioFile $audioFile)
    {

            

        // Check if user has permission to delete this file
        if (!$request->user()->hasRole('Admin') && $audioFile->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to audio file'
            ], 403);
        }

        Storage::disk('public')->delete($audioFile->file_path);
        $audioFile->delete();

        return response()->json([
            'success' => true,
            'message' => 'Audio file deleted successfully'
        ]);
    }

    public function stream(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'chunk' => 'required|file',
            'is_transcribe' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $chunk = $request->file('chunk');
        $isTranscribe = $request->boolean('is_transcribe');
        
        // Store the chunk temporarily
        $path = $chunk->store('audio/chunks', 'public');
        
        // If transcription is requested, queue the job
        if ($isTranscribe) {
            // TODO: Implement transcription logic
            // TranscribeAudio::dispatch($path);
        }

        return response()->json([
            'success' => true,
            'message' => 'Chunk received successfully'
        ]);
    }

    public function createStream(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(AudioFile::getAvailableTypes())),
            'context' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $audioFile = AudioFile::create([
            'name' => $request->name,
            'description' => 'Streaming in progress...',
            'file_path' => 'audio/streaming/' . uniqid() . '.wav',
            'file_type' => 'audio/wav',
            'source_type' => 'stream',
            'type' => $request->type,
            'context' => $request->context,
            'user_id' => $request->user()->id,
            'is_active' => false,
            'metadata' => [
                'status' => 'streaming',
                'started_at' => now()
            ]
        ]);

        return response()->json([
            'success' => true,
            'data' => $audioFile
        ]);
    }

    public function streamChunk(Request $request, AudioFile $audioFile)
    {
        // Check if user has permission to stream to this file
        if (!$request->user()->hasRole('admin') && $audioFile->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to audio file'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'chunk' => 'required|file',
            'is_final' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $chunk = $request->file('chunk');
        $isFinal = $request->boolean('is_final');
        
        // Append the chunk to the file
        Storage::disk('public')->append($audioFile->file_path, $chunk->get());
        
        if ($isFinal) {
            // Update the audio file status
            $audioFile->update([
                'is_active' => true,
                'description' => 'Stream completed',
                'metadata' => array_merge($audioFile->metadata, [
                    'status' => 'completed',
                    'completed_at' => now()
                ])
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Chunk received successfully'
        ]);
    }

    public function cancelStream(AudioFile $audioFile)
    {
        if (!$request->user()->hasRole('admin') && $audioFile->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Storage::disk('public')->delete($audioFile->file_path);
        $audioFile->delete();

        return response()->json(['message' => 'Stream cancelled successfully']);
    }

    public function serveAudio(AudioFile $audioFile)
    {
        $path = storage_path('app/public/' . $audioFile->file_path);
        
        if (!file_exists($path)) {
            return response()->json(['message' => 'Audio file not found'], 404);
        }

        return response()->file($path, [
            'Content-Type' => $audioFile->file_type,
            'Content-Length' => filesize($path),
            'Accept-Ranges' => 'bytes'
        ]);
    }

    public function convert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'audio_file_id' => 'required|exists:audio_files,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(AudioFile::getAvailableTypes())),
            'convert_to' => 'required|string|in:ulaw'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $audioFile = AudioFile::findOrFail($request->audio_file_id);
            $inputPath = storage_path('app/public/' . $audioFile->file_path);
            
            \Log::info('Starting audio conversion', [
                'original_file' => $audioFile->file_path,
                'mime_type' => $audioFile->file_type,
                'size' => $audioFile->file_size
            ]);
            
            // Ensure the output directory exists
            $outputDir = storage_path('app/public/audio');
            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
            
            // Convert to G.711 µ-law using FFmpeg
            $outputPath = $outputDir . '/' . uniqid() . '.wav';
            $command = "ffmpeg -i {$inputPath} -ar 8000 -ac 1 -acodec pcm_mulaw -f wav {$outputPath} 2>&1";
            
            \Log::info('Executing FFmpeg command', ['command' => $command]);
            
            exec($command, $output, $returnCode);
            
            \Log::info('FFmpeg execution completed', [
                'return_code' => $returnCode,
                'output' => $output
            ]);

            if ($returnCode !== 0) {
                throw new Exception('FFmpeg conversion failed: ' . implode("\n", $output));
            }

            if (!file_exists($outputPath)) {
                throw new Exception('Output file was not created');
            }

            $fileSize = filesize($outputPath);
            \Log::info('Conversion successful', [
                'output_path' => $outputPath,
                'file_size' => $fileSize
            ]);

            $convertedFile = AudioFile::create([
                'name' => $request->name,
                'description' => 'Converted to G.711 µ-law',
                'file_path' => str_replace(storage_path('app/public/'), '', $outputPath),
                'file_type' => 'audio/x-mulaw',
                'source_type' => 'conversion',
                'type' => $request->type,
                'user_id' => $request->user()->id,
                'file_size' => $fileSize,
                'metadata' => [
                    'original_file_id' => $audioFile->id,
                    'original_name' => $audioFile->name,
                    'conversion_type' => 'ulaw',
                    'converted_at' => now(),
                    'ffmpeg_output' => $output
                ]
            ]);

            return response()->json([
                'success' => true,
                'data' => $convertedFile
            ]);
        } catch (Exception $e) {
            \Log::error('Audio conversion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to convert audio file: ' . $e->getMessage()
            ], 500);
        }
    }
} 