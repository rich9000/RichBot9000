<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CaptureScreenOutput extends Command
{
    protected $signature = 'screen:capture {session?}';
    protected $description = 'Capture output from screen sessions';

    public function handle()
    {
        $session = $this->argument('session') ?? 'richbot_assistants';
        
        // Create a temporary file for the screen output
        $tempFile = storage_path("logs/screen_{$session}_" . time() . '.log');
        
        // Use screen's hardcopy feature to capture output
        $command = "screen -S {$session} -X hardcopy {$tempFile}";
        exec($command);
        
        if (file_exists($tempFile)) {
            // Read the last 1000 lines
            $output = $this->tail($tempFile, 1000);
            
            // Store in cache for 5 seconds
            Cache::put("screen_output_{$session}", $output, 5);
            
            // Clean up temp file
            unlink($tempFile);
            
            $this->info("Captured output from screen session: {$session}");
        } else {
            $this->error("Failed to capture screen output");
        }
    }
    
    private function tail($file, $lines = 1000)
    {
        $output = [];
        $handle = fopen($file, "r");
        if ($handle) {
            $linecount = 0;
            $position = 0;
            $chunk = "";
            
            // Get file size
            $size = filesize($file);
            
            // Start from end of file
            fseek($handle, $size);
            
            // Read backwards
            while ($linecount < $lines && $position < $size) {
                $position = ftell($handle);
                $chunk = fread($handle, min(4096, $size - $position));
                $output = array_merge(explode("\n", $chunk), $output);
                $linecount = count($output);
                fseek($handle, $position - strlen($chunk));
            }
            
            fclose($handle);
        }
        
        return array_slice($output, -$lines);
    }
} 