<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class ApiFileController extends Controller
{
    public $storage;
    public Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->storage = Storage::disk('richbot_sandbox');
    }

    // Download a file from the server
    public function download(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $file = $request->input('file');
        $file = ltrim($file, '/'); // Prevent path traversal

        try {
            if ($this->storage->exists($file)) {
                Log::info("File downloaded: {$file} by User ID: {$request->user()->id}");
                return response()->download($this->storage->path($file));
            }

            return response()->json([
                'status' => 'error',
                'message' => 'File not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Download failed for file {$file}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download file'
            ], 500);
        }
    }

    // Delete a file from the server
    public function delete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $file = $request->input('file');
        $file = ltrim($file, '/'); // Prevent path traversal

        try {
            if ($this->storage->exists($file)) {
                $this->backupFile($file);
                $this->storage->delete($file);
                Log::info("File deleted: {$file} by User ID: {$request->user()->id}");
                return response()->json([
                    'status' => 'success',
                    'message' => 'File deleted successfully'
                ], 200);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'File not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Deletion failed for file {$file}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete file'
            ], 500);
        }
    }

    // List all files in a directory
    public function listFiles(Request $request): JsonResponse
    {


        Log::info('List FIles');


        $validator = Validator::make($request->all(), [
            'directory' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $directory = $request->input('directory', '/');
        $directory = ltrim($directory, '/'); // Prevent path traversal

        try {
            if (!$this->storage->exists($directory)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Directory not found'
                ], 404);
            }

            $files = $this->storage->files($directory);
            Log::info("Files listed in directory {$directory} by User ID: {$request->user()->id}");

            return response()->json([
                'status' => 'success',
                'data' => ['files' => $files]
            ], 200);
        } catch (\Exception $e) {
            Log::error("Listing files failed for directory {$directory}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to list files'
            ], 500);
        }
    }

    // List all folders in a directory
    public function listFolders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'directory' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $directory = $request->input('directory', '/');
        $directory = ltrim($directory, '/'); // Prevent path traversal

        try {
            if (!$this->storage->exists($directory)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Directory not found'
                ], 404);
            }

            $folders = $this->storage->directories($directory);
            Log::info("Folders listed in directory {$directory} by User ID: {$request->user()->id}");

            return response()->json([
                'status' => 'success',
                'data' => ['folders' => $folders]
            ], 200);
        } catch (\Exception $e) {
            Log::error("Listing folders failed for directory {$directory}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to list folders'.json_encode($e)
            ], 500);
        }
    }

    // List the full file and directory tree structure
    public function listTree(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'directory' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $directory = $request->input('directory', '/');
        $directory = ltrim($directory, '/'); // Prevent path traversal

        try {
            if (!$this->storage->exists($directory)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Directory not found'
                ], 404);
            }

            $fileTree = $this->getDirectoryTree($directory, 0, 10); // Example: Max depth 10
            Log::info("Directory tree listed for {$directory} by User ID: {$request->user()->id}");

            return response()->json([
                'status' => 'success',
                'data' => ['tree' => $fileTree]
            ], 200);
        } catch (\Exception $e) {
            Log::error("Listing tree failed for directory {$directory}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to list directory tree asdf'.json_encode($e->getMessage())
            ], 500);
        }
    }

    private function getDirectoryTree($directory, $currentDepth = 0, $maxDepth = 10)
    {
        if ($currentDepth > $maxDepth) {
            return [];
        }

        // Define folder blacklist
        $folder_blacklist = [
            'node_modules',
            'vendor',
            'logs',
            'backup',
            'vendor',
            'tests'
        ];





        $tree = [];

        // Get all folders and files in the directory
        $allFolders = $this->storage->directories($directory);
        $allFiles = $this->storage->files($directory);

        foreach ($allFolders as $folder) {
            // Skip symbolic links and blacklisted folders or those starting with '.'
            if (is_link($folder) || in_array($folder, $folder_blacklist) || str_starts_with($folder, '.')) {
                continue;
            }

            $tree[] = [
                'type' => 'folder',
                'name' => $folder,
                'contents' => $this->getDirectoryTree($folder, $currentDepth + 1, $maxDepth)
            ];
        }

        foreach ($allFiles as $file) {
            // Skip symbolic links
            if (is_link($file) || str_starts_with($file,'.') ) {
                continue;
            }

            $tree[] = [
                'type' => 'file',
                'name' => $file
            ];
        }

        return $tree;
    }
    // Create a new directory
    public function createDirectory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), ['directory' => 'required|string']);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $directory = $request->input('directory');
        $directory = ltrim($directory, '/'); // Prevent path traversal

        try {
            if ($this->storage->exists($directory)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Directory already exists'
                ], 200);
            }

            $this->storage->makeDirectory($directory);
            Log::info("Directory created: {$directory} by User ID: {$request->user()->id}");

            return response()->json([
                'status' => 'success',
                'message' => 'Directory created successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error("Directory creation failed for {$directory}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Directory creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a directory from the server
    public function deleteDirectory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'directory' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $directory = $request->input('directory');
        $directory = ltrim($directory, '/'); // Prevent path traversal

        try {
            // Laravel's Storage doesn't have an isDirectory method, so check using directories method
            $parentDirectory = dirname($directory);
            $directories = $this->storage->directories($parentDirectory);

            if (!in_array($directory, $directories)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Directory not found'
                ], 404);
            }

            $this->storage->deleteDirectory($directory);
            Log::info("Directory deleted: {$directory} by User ID: {$request->user()->id}");

            return response()->json([
                'status' => 'success',
                'message' => 'Directory deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error("Directory deletion failed for {$directory}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Directory deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Write or overwrite text content to a file
    public function putText(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|string',
            'content' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $file = $request->input('file');
        $content = $request->input('content');
        $file = ltrim($file, '/'); // Prevent path traversal

        try {
            $this->backupFile($file);
            $this->storage->put($file, $content);
            Log::info("Content written to file: {$file} by User ID: {$request->user()->id}");

            return response()->json([
                'status' => 'success',
                'message' => 'Text written to file successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error("Failed to write to file {$file}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to write to file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Append text content to a file
    public function appendText(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|string',
            'content' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $file = $request->input('file');
        $content = $request->input('content');
        $file = ltrim($file, '/'); // Prevent path traversal

        try {
            $this->backupFile($file);
            $this->storage->append($file, $content);
            Log::info("Content appended to file: {$file} by User ID: {$request->user()->id}");

            return response()->json([
                'status' => 'success',
                'message' => 'Text appended to file successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error("Failed to append to file {$file}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to append to file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Send an email
    public function sendEmail(Request $request): JsonResponse
    {


        Log::info('Sending email\n');
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'name' => 'required|string|max:255',
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $validated = $validator->validated();

        try {
            Mail::raw($validated['body'], function ($message) use ($validated) {
                $message->to($validated['email'], $validated['name'])
                    ->subject($validated['subject'])
                    ->from('TheRichBot9000@RichBot9000.com', 'The RichBot 9000');
            });

            Log::info("Email sent to {$validated['email']} ({$validated['name']}) by User ID: {$request->user()->id}");

            return response()->json([
                'status' => 'success',
                'message' => 'Email sent successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error("Failed to send email to {$validated['email']}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Upload file to the server
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), ['file' => 'required|file']);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            $path = $request->file('file')->store('uploads');
            Log::info("File uploaded to {$path} by User ID: {$request->user()->id}");

            return response()->json([
                'status' => 'success',
                'message' => 'File uploaded successfully',
                'data' => ['path' => $path]
            ], 200);
        } catch (\Exception $e) {
            Log::error("File upload failed: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'File upload failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Upload image to the server
    public function uploadImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), ['image' => 'required|image']);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            $path = $request->file('image')->store('images');
            Log::info("Image uploaded to {$path} by User ID: {$request->user()->id}");

            return response()->json([
                'status' => 'success',
                'message' => 'Image uploaded successfully',
                'data' => ['path' => $path]
            ], 200);
        } catch (\Exception $e) {
            Log::error("Image upload failed: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Image upload failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Upload file from a URL
    public function uploadFromUrl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), ['file_url' => 'required|url']);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $fileUrl = $request->input('file_url');
        $fileUrl = filter_var($fileUrl, FILTER_SANITIZE_URL); // Sanitize URL

        try {
            $fileContents = file_get_contents($fileUrl);
            if ($fileContents === false) {
                throw new \Exception("Unable to retrieve file from URL.");
            }

            // Generate a safe file name
            $fileName = basename(parse_url($fileUrl, PHP_URL_PATH));
            $fileName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $fileName); // Replace unsafe characters
            $path = 'uploads/' . $fileName;

            $this->storage->put($path, $fileContents);
            Log::info("File uploaded from URL to {$path} by User ID: {$request->user()->id}");

            return response()->json([
                'status' => 'success',
                'message' => 'File uploaded from URL successfully',
                'data' => ['path' => $path]
            ], 200);
        } catch (\Exception $e) {
            Log::error("Upload from URL failed for {$fileUrl}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'File upload from URL failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Transfer a file to a target path
    public function transfer(Request $request)
    {
        // Step 1: Validate the request
        $validator = Validator::make($request->all(), [
            'file' => 'required|file',
            'target_path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // Step 2: Store the file locally (or to a designated storage system)
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $targetPath = ltrim($request->input('target_path'), '/'); // Prevent path traversal

            $this->backupFile($targetPath . '/' . $fileName);
            $this->storage->put($targetPath . '/' . $fileName, file_get_contents($file));

            Log::info("File transferred to {$targetPath}/{$fileName} by User ID: {$request->user()->id}");

            // Step 4: Return a success message
            return response()->json([
                'status' => 'success',
                'message' => 'File transferred successfully!',
                'data' => ['path' => "{$targetPath}/{$fileName}"]
            ], 200);
        } catch (\Exception $e) {
            // Step 5: Handle failure
            Log::error("File transfer failed: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'File transfer failed!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Backup a file before modification or deletion
    private function backupFile($filePath)
    {
        $filePath = ltrim($filePath, '/'); // Prevent path traversal

        try {
            if ($this->storage->exists($filePath)) {
                // Ensure the backup directory exists
                $backupDirectory = 'backup';
                if (!$this->storage->exists($backupDirectory)) {
                    $this->storage->makeDirectory($backupDirectory);
                }

                // Generate the backup path with a timestamp appended to the filename
                $timestamp = now()->format('Ymd_His');
                $fileName = basename($filePath);
                $backupFilePath = "{$backupDirectory}/{$fileName}_{$timestamp}";

                // Copy the original file to the backup directory
                $this->storage->copy($filePath, $backupFilePath);

                Log::info("Backup created for {$filePath} at {$backupFilePath} by User ID: {$request->user()->id}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to backup file {$filePath}: " . $e->getMessage());
            throw new \Exception("Backup failed for file {$filePath}");
        }
    }
}
