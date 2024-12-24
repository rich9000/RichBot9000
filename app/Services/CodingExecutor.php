<?php

namespace App\Services;

use App\Models\TicketSummary;
use App\Services\OpenAIAssistant;
use App\Services\TroubleTicketService;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client;
use App\Models\User;
use App\Models\Task;
use App\Models\Project;
use App\Models\Appointment;
use App\Models\Display;
use Illuminate\Support\Facades\Log;


class CodingExecutor
{
    public int $auth_user_id = 1;
    private $disk = false;

    public function __construct()
    {
        $this->auth_user_id = 1;
        $this->disk = Storage::disk('richbot_sandbox');

    }




    // Read the contents of a specified file
    public function read_file($arguments)
    {
        $filePath = $arguments['filePath'] ?? null;

        if (!$filePath) {
            return ['success' => false, 'error' => 'Missing required parameter: filePath'];
        }

        if ($this->disk->exists($filePath)) {
            $content = $this->disk->get($filePath);
            return ['success' => true, 'data' => $content];
        }

        return ['success' => false, 'error' => 'File not found'];
    }

    // Suggest changes to a file (placeholder for logic)
    public function suggest_file_change($arguments)
    {
        $filePath = $arguments['filePath'] ?? null;
        $newContent = $arguments['newContent'] ?? null;

        if (!$filePath || !$newContent) {
            return ['success' => false, 'error' => 'Missing required parameters: filePath, newContent'];
        }

        if ($this->disk->exists($filePath)) {
            $originalContent = $this->disk->get($filePath);
            // Placeholder logic for file change suggestion
            return [
                'success' => true,
                'data' => [
                    'originalContent' => $originalContent,
                    'suggestedContent' => $newContent,
                ]
            ];
        }

        return ['success' => false, 'error' => 'File not found'];
    }

    // Suggest an artisan command (placeholder logic)
    public function suggest_artisan_command($arguments)
    {
        $description = $arguments['description'] ?? 'Run a Laravel command';

        // Placeholder for command suggestion logic
        return [
            'success' => true,
            'data' => "Suggested Artisan Command: php artisan ${description}"
        ];
    }

    // Suggest SQL (placeholder for logic)
    public function suggest_sql($arguments)
    {
        $queryDescription = $arguments['queryDescription'] ?? null;

        if (!$queryDescription) {
            return ['success' => false, 'error' => 'Missing required parameter: queryDescription'];
        }

        // Placeholder for SQL generation logic
        $suggestedQuery = "SELECT * FROM table WHERE description LIKE '%${queryDescription}%';";

        return ['success' => true, 'data' => $suggestedQuery];
    }

    // Ask a question (placeholder for logic)
    public function ask_question($arguments)
    {
        $question = $arguments['question'] ?? null;

        if (!$question) {
            return ['success' => false, 'error' => 'Missing required parameter: question'];
        }


        Log::info('Question asked: '.$question);

        // Placeholder for asking a question
        return ['success' => true, 'data' => "Question asked: $question"];
    }




    // List all files in a specified directory
    public function list_files($arguments)
    {
        $directory = $arguments['directory'] ?? '/';

        if ($this->disk->exists($directory)) {
            $files = $this->disk->files($directory);
            return ['success' => true, 'data' => $files];
        }

        return ['success' => false, 'error' => 'Directory not found'];
    }

    // List all folders in a specified directory
    public function list_folders($arguments)
    {
        $directory = $arguments['directory'] ?? null;

        if (!$directory) {
            return ['success' => false, 'error' => 'Missing required parameter: directory'];
        }

        if ($this->disk->exists($directory)) {
            $folders = $this->disk->directories($directory);
            return ['success' => true, 'data' => $folders];
        }

        return ['success' => false, 'error' => 'Directory not found'];
    }

    // Create a new directory
    public function create_directory($arguments)
    {
        $directory = $arguments['directory'] ?? null;

        if (!$directory) {
            return ['success' => false, 'error' => 'Missing required parameter: directory'];
        }

        if (!$this->disk->exists($directory)) {
            $this->disk->makeDirectory($directory);
            return ['success' => true, 'message' => 'Directory created successfully'];
        }

        return ['success' => false, 'error' => 'Directory already exists'];
    }

    // Delete a directory
    public function delete_directory($arguments)
    {
        $directory = $arguments['directory'] ?? null;

        if (!$directory) {
            return ['success' => false, 'error' => 'Missing required parameter: directory'];
        }

        if ($this->disk->exists($directory)) {
            $this->disk->deleteDirectory($directory);
            return ['success' => true, 'message' => 'Directory deleted successfully'];
        }

        return ['success' => false, 'error' => 'Directory not found'];
    }

    // Save text content to a specified file, replacing existing content
    public function put_text($arguments)
    {
        $filePath = $arguments['filePath'] ?? null;
        $content = $arguments['content'] ?? null;

        if (!$filePath || !$content) {
            return ['success' => false, 'error' => 'Missing required parameters: filePath, content'];
        }

        try {
            $this->disk->put($filePath, $content);
            return ['success' => true, 'message' => 'Content saved successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to save content: ' . $e->getMessage()];
        }
    }

    // 5. Search for files in a directory
    public function search_files($arguments)
    {
        $query = $arguments['query'] ?? null;
        $directory = $arguments['directory'] ?? '/';

        if (!$query) {
            return ['success' => false, 'error' => 'Missing required parameter: query'];
        }

        if ($this->disk->exists($directory)) {
            $allFiles = $this->disk->allFiles($directory);
            $matchedFiles = array_filter($allFiles, function ($file) use ($query) {
                return stripos($file, $query) !== false;
            });

            return ['success' => true, 'data' => array_values($matchedFiles)];
        }

        return ['success' => false, 'error' => 'Directory not found'];
    }



}
