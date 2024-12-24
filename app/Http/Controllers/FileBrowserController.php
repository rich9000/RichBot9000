<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class FileBrowserController extends Controller
{




    public $storage;
    public Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->storage = Storage::disk('richbot_sandbox');
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

        if (!Storage::exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $fileContents = Storage::get($filePath);
        $mimeType = Storage::mimeType($filePath);

        return response($fileContents, 200)->header('Content-Type', $mimeType);
    }





}
