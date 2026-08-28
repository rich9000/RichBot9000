<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class ScreenOutputController extends Controller
{
    public function index()
    {
        // Trigger screen capture
        Artisan::call('screen:capture');
        
        // Get output from cache
        $output = Cache::get('screen_output_richbot_assistants', []);
        
        return view('screen-output', [
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
                
                echo "data: " . json_encode(['output' => $output]) . "\n\n";
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