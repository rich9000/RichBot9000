<?php

namespace App\Http\Controllers;

use App\Services\OpenAIAssistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use FFMpeg;
use Symfony\Component\Process\Process;

class AudioController extends Controller
{
    public string $audio_folder = '/var/www/html/projman/storage/app/public/';
    protected $openAIService;

  //  public function __construct(OpenAIService $openAIService)
    public function __construct()
    {
       // $this->openAIService = $openAIService;
    }

    public function uploadAudioStream(Request $request)
    {

        $user = $request->user();

        $file_owner = str_replace('@','_',$user->email);

        //dd($user);

        if (!$request->hasFile('audio')) {
            return response()->json(['status' => 'error', 'message' => 'No audio file uploaded'], 400);
        }

        $file = $request->file('audio');



        $mimeType = $file->getMimeType();
        $extension = $file->getClientOriginalExtension();

        if (!in_array($mimeType, ['audio/wav', 'audio/ogg', 'audio/mpeg' , 'application/octet-stream'])) {
            return response()->json(['status' => 'error', 'message' => 'Invalid MIME type: ' . $mimeType], 400);
        }

        //echo "$mimeType $extension\n";


        // Validate the request to ensure it contains an audio file
       // $request->validate([
      //      'audio' => 'required|file|mimes:wav,mp3,ogg|max:20480', // max 20MB
       // ]);

        // Store the audio file in the 'public/uploads' directory
        $filePath = $request->file('audio')->store('realtime_audio', 'public');


        $fullPath = "/var/www/html/richbot9000.com/public/storage/$filePath";

        $time = time();
        $stored_path = "/var/www/html/richbot9000.com/storage/app/public/realtime_audio/$file_owner.$time.wav";



        $command = [
            'ffmpeg',
            '-f', 's16le',  // Format: PCM signed 16-bit little-endian
            '-ar', '16000', // Sample rate: 44.1kHz
            '-ac', '1',     // Channels: 2 (stereo)
            '-i', $fullPath,
            $stored_path
        ];

        // Run the command using Symfony Process
        $process = new Process($command);
        $process->run();

        Storage::disk('public')->delete($filePath);

        return response()->json([
            'status' => 'success',
            'message' => 'Audio uploaded successfully',
            'file_path' => $stored_path
        ]);
    }


    public function uploadAudio(Request $request)
    {
        if ($request->hasFile('audio-file')) {
            $file = $request->file('audio-file');
            $path = $file->store('audio', 'public');
            $filePath = storage_path('app/public/' . $path);
            $mp3path = $this->convertOggToMp3($filePath);
            $transcription = $this->transcribeAudio($mp3path);

            return response()->json(['transcription' => $transcription]);
        }

        return response()->json(['error' => 'No audio file uploaded'], 400);
    }

    private function transcribeAudio($path)
    {
        $client = new Client();

        $response = $client->post('https://api.openai.com/v1/audio/transcriptions', [
            'headers' => [
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            ],
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($this->audio_folder . $path, 'r'),
                    'filename' => 'audio.mp3',
                ],
                [
                    'name'     => 'model',
                    'contents' => 'whisper-1',
                ],
            ]
        ]);

        $result = json_decode($response->getBody(), true);
        return $result['text'] ?? 'Transcription failed';
    }


    public function index()
    {
        return view('audio.record-audio');
    }

    public function dashboard()
    {
        return view('audio.content._dashboard');
    }
    public function upload(Request $request)
    {

        Log::info('Upload request received', $request->all());

        if (!$request->hasFile('audio')) {
            Log::error('No audio file found in the request.');
            return response()->json(['error' => 'No audio file found in the request.'], 400);
        }

        $file = $request->file('audio');
        Log::info('File information', [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        $validated = $request->validate([
            'audio' => 'required|file'
        ]);

        //Log::info("Audio chunk appended to $filename");
        // Generate a unique filename based on the session or user id
        $filename = $this->audio_folder.'audio_chunks/'.auth()->user()->email.'_'.time().'_audio_.'.$file->getClientOriginalExtension();
        $handle = fopen($filename, 'ab'); // 'ab' mode opens the file for writing in binary mode and places the file pointer at the end of the file
        fwrite($handle, file_get_contents($file->getRealPath()));
        fclose($handle);

        Log::info("Audio chunk appended to $filename");

        $path = $request->file('audio')->store('audio', 'public');
        $filePath = storage_path('app/public/' . $path);

        //echo "$filePath\n";

        $mp3path = $this->convertOggToMp3($filePath);

        $client = new Client();
        try {
            $response = $client->post('https://api.openai.com/v1/audio/transcriptions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                ],
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => fopen($this->audio_folder . $mp3path, 'r'),
                        'filename' => 'audio.mp3',
                    ],
                    [
                        'name'     => 'model',
                        'contents' => 'whisper-1',
                    ],
                ],
            ]);

            $responseBody = json_decode($response->getBody(), true);
            //dd($responseBody);
            Log::info('Audio file processed successfully', ['responseBody' => $responseBody]);


            return response()->json([
                'transcription' => $responseBody['text'],
                'response' => $responseBody['text'],
                //  'audio' => $audioUrl,  // URL to the saved audio file
            ]);

            // Use the OpenAI service to convert text to speech
            $responseText = $this->openAIService->askQuestion($responseBody['text']);



            exit;
            // Optionally, get the audio response if needed
            $audioUrl = $this->openAIService->textToSpeech($responseText);


        } catch (\Exception $e) {
            Log::error('Error processing audio file', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to process audio file. Please try again later.'], 500);
        }

        //dd($_FILES);
        //$path = $file->storeAs('audio_chunks', $filename, 'public');
        //$path = $file->storeAs('audio_chunks', $filename, 'public');

        return response()->json(['message' => 'Audio chunk uploaded successfully', 'path' => $filename], 200);
    }

    public function uploadOld(Request $request)
    {
        Log::info('Upload request received', $request->all());

        if (!$request->hasFile('audio')) {
            Log::error('No audio file found in the request.');
            return response()->json(['error' => 'No audio file found in the request.'], 400);
        }

        $file = $request->file('audio');
        Log::info('File information', [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        $validated = $request->validate([
            'audio' => 'required|file|mimes:webm,ogg'
        ]);

        $path = $request->file('audio')->store('audio', 'public');
        $filePath = storage_path('app/public/' . $path);

        echo "$filePath\n";

        $mp3path = $this->convertOggToMp3($filePath);

        $client = new Client();

        try {
            $response = $client->post('https://api.openai.com/v1/audio/transcriptions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                ],
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => fopen($this->audio_folder . $mp3path, 'r'),
                        'filename' => 'audio.mp3',
                    ],
                    [
                        'name'     => 'model',
                        'contents' => 'whisper-1',
                    ],
                ],
            ]);

            $responseBody = json_decode($response->getBody(), true);
            dd($responseBody);
            Log::info('Audio file processed successfully', ['responseBody' => $responseBody]);

            // Use the OpenAI service to convert text to speech
            $responseText = $this->openAIService->askQuestion($responseBody['text']);





            exit;
            // Optionally, get the audio response if needed
            $audioUrl = $this->openAIService->textToSpeech($responseText);

            return response()->json([
                'transcription' => $responseBody['text'],
                'response' => $responseText,
                'audio' => $audioUrl,  // URL to the saved audio file
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing audio file', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to process audio file. Please try again later.'], 500);
        }
    }

    private function convertOggToMp3($filePath)
    {
        $ffmpeg = FFMpeg\FFMpeg::create();
        $audio = $ffmpeg->open($filePath);
        $mp3Path = preg_replace('/\.[^.]+$/', '.mp3', $filePath);

        $audio->save(new FFMpeg\Format\Audio\Mp3(), $mp3Path);

        return str_replace(storage_path('app/public/'), '', $mp3Path);
    }
    private function convertOggToWav($filePath)
    {


        $wavPath = preg_replace('/\.[^.]+$/', '.wav', $filePath);

        $command = [
            'ffmpeg',
            '-f', 's16le',  // Format: PCM signed 16-bit little-endian
            '-ar', '16000', // Sample rate: 44.1kHz
            '-ac', '1',     // Channels: 2 (stereo)
            '-i', $filePath,
            $wavPath
        ];

        // Run the command using Symfony Process
        $process = new Process($command);
        $process->run();


        return true;





        $ffmpeg = FFMpeg\FFMpeg::create();
        $audio = $ffmpeg->open($filePath);
        $wavPath = preg_replace('/\.[^.]+$/', '.wav', $filePath);

        $audio->save(new FFMpeg\Format\Audio\Wav(), $wavPath);

        return str_replace(storage_path('app/public/'), '', $wavPath);
    }


}
