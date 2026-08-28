<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;


class FileBrowserController extends Controller
{




    public $storage;
    public Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->storage = Storage::disk('local');
    }





    public function browse(Request $request)
    {
        $path = $request->query('path', '/');
        $path = ltrim($path, '/'); // Ensures no leading slash for Storage paths





        if (!$this->storage->exists($path)) {
            return response()->json(['error' => 'Path not found'], 404);
        }

        $directories = $this->storage->directories($path);
        $files = $this->storage->files($path);

        $contents = [
            'directories' => $directories,
            'files' => $files,
        ];

        return response()->json($contents, 200);
    }

    public function download(Request $request)
    {
        $filePath = $request->query('file');

        if (!$this->storage->exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $fileContents = $this->storage->get($filePath);
        $mimeType = $this->storage->mimeType($filePath);

        return response($fileContents, 200)->header('Content-Type', $mimeType);
    }

    public function listConversations()
    {
        $basePath = 'bare_logs';
        
        // Create the directory if it doesn't exist
        if (!$this->storage->exists($basePath)) {
            $this->storage->makeDirectory($basePath);
        }
        
        $directories = $this->storage->directories($basePath);
        
        $conversations = [];
        foreach ($directories as $dir) {
            $conversationId = basename($dir);
            $metadataPath = "{$dir}/metadata.json";
            
            if ($this->storage->exists($metadataPath)) {
                $metadata = json_decode($this->storage->get($metadataPath), true);
                $conversations[] = [
                    'id' => $conversationId,
                    'title' => $metadata['conversation_id'] ?? $conversationId,
                    'start_time' => $metadata['start_time'] ?? null,
                    'user_id' => $metadata['user_id'] ?? null,
                    'room' => $metadata['room'] ?? null
                ];
            }
        }
        
        return response()->json($conversations);
    }

    public function listConversationFiles($conversationId)
    {
        $basePath = "bare_logs/{$conversationId}";
        
        if (!$this->storage->exists($basePath)) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }
        
        $files = $this->storage->files($basePath);
        $directories = $this->storage->directories($basePath);
        
        $result = [
            'files' => [],
            'directories' => []
        ];
        
        foreach ($files as $file) {
            $result['files'][] = [
                'name' => basename($file),
                'path' => $file,
                'size' => $this->storage->size($file),
                'last_modified' => $this->storage->lastModified($file)
            ];
        }
        
        foreach ($directories as $dir) {
            $result['directories'][] = [
                'name' => basename($dir),
                'path' => $dir
            ];
        }
        
        return response()->json($result);
    }





}
