<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\RemoteRichbot;
use App\Models\Command;
use App\Models\Media;
use App\Models\Event;

class RemoteRichbotController extends Controller
{



    public function show($id)
    {
        $richbot = RemoteRichbot::with([
            'media' => function ($query) {
                return $query->orderBy('created_at', 'desc')->limit(10);
            },
            'events',
            'commands',
            'mediaTriggers.events',
        ])->findOrFail($id);

        return response()->json($richbot);
    }

    public function sendCommand(Request $request, $id)
    {
        $richbot = RemoteRichbot::findOrFail($id);

        $validated = $request->validate([
            'command' => 'required|string',
            'parameters' => 'nullable|array',

        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['richbot_id'] = $richbot->id;


        // Save the command to be processed by the Richbot device
        $richbot->commands()->create($validated);

        return response()->json(['message' => 'Command sent successfully.']);
    }

    public function updateCommand(Request $request, Command $command)
    {
        //$richbot = RemoteRichbot::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string',

        ]);


        $command->update($validated);

        return response()->json(['message' => 'Command sent successfully.']);
    }






    // Authentication method
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $token = $request->user()->createToken('auth_token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => Auth::user(),
            ]);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    // Handle device registration
    public function registerDevice(Request $request)
    {
        $validated = $request->validate([
            'remote_richbot_id' => 'required|unique:remote_richbots',
            'name' => 'required|string',
            'location' => 'nullable|string',
        ]);

        $device = RemoteRichbot::create([
            'remote_richbot_id' => $validated['remote_richbot_id'],
            'name' => $validated['name'],
            'location' => $validated['location'],
            'status' => 'offline',
        ]);

        return response()->json([
            'message' => 'Device registered successfully',
            'device' => $device,
        ], 201);
    }

    // Poll for commands
    public function pollCommands($remote_richbot_id)
    {
        $device = RemoteRichbot::where('remote_richbot_id', $remote_richbot_id)->first();

        if (!$device) {

            $device = RemoteRichbot::create([
                'remote_richbot_id' => $remote_richbot_id,
                'name' => 'The Richbot 9000',
                'location' => 'Probably Your Moms House',
                'status' => 'new',
            ]);

            //return response()->json(['message' => 'Device not found','status'=>'unknown_id','commands' =>[] ]);

        }

        $commands = Command::where('remote_richbot_id', $device->id)
            ->where('status', 'pending')
            ->get();

        return response()->json(['commands' => $commands,'status'=>'success']);
    }

    // Upload media
    public function uploadMedia(Request $request, $remote_richbot_id)
    {
        $device = RemoteRichbot::where('remote_richbot_id', $remote_richbot_id)->first();

        if (!$device) {
            return response()->json(['message' => 'Device not found','status'=>'fail'], 404);
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('public/media/images');

            $path = str_replace('public/', '', $path);

            $image = Media::create([
                'richbot_id' => $device->id,
                'type' => 'image',
                'user_id' => $request->user()->id,
                'file_path' => $path,
            ]);

            $triggers = $device->mediaTriggers()->where('type', $image->type)->get();
            foreach ($triggers as $trigger) {
                $triggerMet = false;


                //todo:fix this

                if ($triggerMet) {
                   // $this->performTriggerAction($trigger, $media);

                    // Log the event
                    MediaTriggerEvent::create([
                        'media_trigger_id' => $trigger->id,
                        'media_id' => $media->id,
                        'action_taken' => $trigger->action,
                        'details' => null,
                    ]);
                }
            }

        }

        if ($request->hasFile('audio')) {
            $path = $request->file('audio')->store('public/media/audio');
            $path = str_replace('public/', '', $path);

            $audio = Media::create([
                'richbot_id' => $device->id,
                'user_id' => $request->user()->id,
                'type' => 'audio',
                'file_path' => $path,
            ]);
        }

        return response()->json(['message' => 'Media uploaded successfully'], 201);
    }

    // Receive events from device
    public function receiveEvent(Request $request, $remote_richbot_id)
    {
        $device = RemoteRichbot::where('remote_richbot_id', $remote_richbot_id)->first();

        if (!$device) {
            return response()->json(['message' => 'Device not found'], 404);
        }

        $validated = $request->validate([
            'event_type' => 'required|string',
            'details' => 'nullable|array',
        ]);

        Event::create([
            'remote_richbot_id' => $device->id,
            'event_type' => $validated['event_type'],
            'details' => $validated['details'],
        ]);

        return response()->json(['message' => 'Event recorded successfully'], 201);
    }










    /**
     * Analyze the image using Ollama LLava LLM or another service.
     *
     * @param string $imageData
     * @param string $model
     * @param string $prompt
     * @param array $options
     * @param bool $stream
     * @return array
     */
    private function analyzeImage($imageData, $model, $prompt, $options, $stream)
    {
        // Example: Integrate with Ollama LLava LLM API
        // Replace with actual API integration as needed

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.ollama.token'), // Ensure you set this in .env and config/services.php
                'Content-Type'  => 'application/json',
            ])->post(config('services.ollama.endpoint'), [
                'model'      => $model,
                'prompt'     => $prompt,
                'image_data' => base64_encode($imageData), // Depending on Ollama's API requirements
                'options'    => $options,
                'stream'     => $stream,
            ]);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Ollama API Error:', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return [];
            }
        } catch (\Exception $e) {
            Log::error('Exception during image analysis:', [
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }

}
