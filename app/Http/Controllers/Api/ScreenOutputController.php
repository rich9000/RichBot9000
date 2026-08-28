<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\View;

class ScreenOutputController extends Controller
{
    public function getOutput()
    {
        // Trigger screen capture
        Artisan::call('screen:capture');
        
        // Get output from cache
        $output = Cache::get('screen_output_richbot_assistants', []);
        
        return response()->json([
            'success' => true,
            'output' => $output
        ]);
    }
    
    public function stream()
    {
        return response()->stream(function() {
            while(true) {
                // Trigger screen capture
                Artisan::call('screen:capture');
                
                // Get output from cache
                $output = Cache::get('screen_output_richbot_assistants', []);
                
                echo "data: " . json_encode([
                    'success' => true,
                    'output' => $output
                ]) . "\n\n";
                ob_flush();
                flush();
                
                // Wait 2 seconds before next update
                sleep(2);
            }
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
        ]);
    }
} 