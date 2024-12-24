<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OllamaApiClient;
use Exception;
use App\Services\StableDiffusionClient;


class OllamaStatusController extends Controller
{
    protected $ollamaClient;
    protected $stableDiffusionClient;

    public function __construct(OllamaApiClient $ollamaClient)
    {

        $this->ollamaClient = $ollamaClient;
        $this->stableDiffusionClient = new StableDiffusionClient('http://192.168.0.104:7860'); // Adjust the URL if necessary
    }





    public function deleteModel(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $modelName = $validated['name'];
            $response = $this->ollamaClient->deleteModel($modelName);

            return response()->json([
                'success' => true,
                'message' => "Model '{$modelName}' deleted successfully.",
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    public function pullModel(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $modelName = $validated['name'];

            $this->ollamaClient->pullModel($modelName);

            return response()->json([
                'success' => true,
                'message' => "Model '{$modelName}' pulled successfully.",
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Get the current status of the Ollama instance, including all models.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus()
    {
        try {
            // Get the list of running models
            $runningModelsResponse = $this->ollamaClient->listRunningModels();
            $runningModels = $runningModelsResponse['models'] ?? [];

            // Get the list of all local models
            $localModelsResponse = $this->ollamaClient->listLocalModels();
            $localModels = $localModelsResponse['models'] ?? [];

            // You can also include other information if available

            return response()->json([
                'success'       => true,
                'runningModels' => $runningModels,
                'localModels'   => $localModels,
                // Include other info as needed
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Create a new model.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createModel(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'parent'    => 'required|string|max:255',
            'system'    => 'nullable|string',
            'template'  => 'nullable|string',
            'parameters'=> 'nullable|array',
        ]);

        try {
            $modelfile = "FROM {$validated['parent']}\n";
            if (isset($validated['system'])) {
                $modelfile .= "SYSTEM {$validated['system']}\n";
            }
            if (isset($validated['template'])) {
                $modelfile .= "TEMPLATE \"\"\"{$validated['template']}\"\"\"\n";
            }
            if (isset($validated['parameters'])) {
                foreach ($validated['parameters'] as $key => $value) {
                    $modelfile .= "PARAMETER {$key} {$value}\n";
                }
            }

            $response = $this->ollamaClient->createModel($validated['name'], $modelfile);

            return response()->json([
                'success' => true,
                'data'    => $response,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run a chat completion.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function runChat(Request $request)
    {
        $validated = $request->validate([
            'model'    => 'required|string|max:255',
            'messages' => 'required|array',
            'tools'    => 'nullable|array',
            'options'  => 'nullable|array',
            'stream'   => 'nullable|boolean',
        ]);

        try {
            $chatMessages = $validated['messages'];

            $response = $this->ollamaClient->generateChatCompletion(
                $validated['model'],
                $chatMessages,
                $validated['tools'] ?? null,
                $validated['options'] ?? null,
                $validated['stream'] ?? null
            );

            return response()->json([
                'success' => true,
                'data'    => $response,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run a completion.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function runCompletion(Request $request)
    {
        $validated = $request->validate([
            'model'  => 'required|string|max:255',
            'prompt' => 'required|string',
            'options'=> 'nullable|array',
            'stream' => 'nullable|boolean',
        ]);

        try {
            $response = $this->ollamaClient->generateCompletion(
                $validated['model'],
                $validated['prompt'],
                null, // suffix
                null, // images
                $validated['options'] ?? null,
                null, // system
                null, // template
                null, // context
                $validated['stream'] ?? null
            );

            return response()->json([
                'success' => true,
                'data'    => $response,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function imageVision(Request $request)
    {
        $validated = $request->validate([
            'model'      => 'required|string',
            'prompt'     => 'required|string',
            'image_data' => 'required|string', // Pure Base64-encoded image data
            'options'    => 'nullable|array',
            'stream'     => 'nullable|boolean',
        ]);

        try {
            $model      = $validated['model'] ?? 'llava';
            $prompt     = $validated['prompt'];
            $imageData  = $validated['image_data']; // Base64-encoded image data
            $options    = $validated['options'] ?? null;
            $stream     = $validated['stream'] ?? false;

            // Remove Data URL prefix if present
            if (preg_match('/^data:image\/\w+;base64,/', $imageData)) {
                $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
            }

            // Validate the Base64 string after removing the prefix
            if (!preg_match('/^[A-Za-z0-9+\/=]+$/', $imageData)) {
                throw new Exception('Invalid Base64 image data.');
            }

            // Optionally, decode to ensure it's valid
            $decodedImage = base64_decode($imageData, true);
            if ($decodedImage === false) {
                throw new Exception('Failed to decode Base64 image data.');
            }

            // Prepare images array with the pure Base64 string
            $images = [$imageData];

            // Call the Ollama API Client's generateCompletion method
            $response = $this->ollamaClient->generateCompletion(
                $model,
                $prompt,
                null,         // suffix
                $images,      // images array
                $options,
                null,         // system
                null,         // template
                null,         // context
                $stream
            );

            // Assuming the Ollama API returns the generated image as a Base64 string under 'image'
            if (isset($response['image'])) {
                $base64Image = $response['image'];
                return response()->json([
                    'success' => true,
                    'data'    => [
                        'response' => $base64Image,
                    ],
                ]);
            }

            // If the response already contains a Base64 string without 'image' key
            return response()->json([
                'success' => true,
                'data'    => $response,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Generate an image from a prompt.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateImage(Request $request)
    {
        $validated = $request->validate([
            'model'  => 'required|string|max:255',
            'prompt' => 'required|string',
            'options'=> 'nullable|array',
            'stream' => 'nullable|boolean',
        ]);

        try {



            $response = $this->ollamaClient->generateImage(
                $validated['model'],
                $validated['prompt'],
                $validated['options'] ?? null,
                $validated['stream'] ?? null
            );

            return response()->json([
                'success' => true,
                'data'    => $response,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate embeddings.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateEmbeddings(Request $request)
    {
        $validated = $request->validate([
            'model'      => 'required|string|max:255',
            'input'      => 'required|array',
            'truncate'   => 'nullable|boolean',
            'options'    => 'nullable|array',
            'keep_alive' => 'nullable|string',
        ]);

        try {
            $response = $this->ollamaClient->generateEmbeddings(
                $validated['model'],
                $validated['input'],
                $validated['truncate'] ?? null,
                $validated['options'] ?? null,
                $validated['keep_alive'] ?? null
            );

            return response()->json([
                'success'    => true,
                'embeddings' => $response,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    public function generateImageStableDiffusion(Request $request)
    {
        $validated = $request->validate([
            'model'      => 'required|string|max:255', // Not strictly necessary for Stable Diffusion unless you have multiple models
            'prompt'     => 'required|string',
            'options'    => 'nullable|array',
            'stream'     => 'nullable|boolean',
        ]);

        try {
            $model   = $validated['model']; // Can be used to select different Stable Diffusion models if supported
            $prompt  = $validated['prompt'];
            $options = $validated['options'] ?? null;
            $stream  = $validated['stream'] ?? false;

            // Log the request for debugging
            \Log::info('Generating image via Stable Diffusion:', [
                'model'   => $model,
                'prompt'  => $prompt,
                'options' => $options,
                'stream'  => $stream,
            ]);

            // Call the Stable Diffusion Client's generateImage method
            $response = $this->stableDiffusionClient->generateImage(
                $prompt,
                $options,
                $stream ? function($chunk) {
                    // Handle streaming response if needed
                } : null
            );

            // Log the API response for debugging
            \Log::info('Stable Diffusion API Response:', ['response' => $response]);

            // Assuming the API returns the image as a Base64 string or binary data
            // Adjust based on your Stable Diffusion API's response
            if (isset($response['images']) && is_array($response['images']) && count($response['images']) > 0) {
                // Example assumes the image is returned as a Base64 string
                $base64Image = $response['images'][0]; // Adjust based on actual response structure
                return response()->json([
                    'success' => true,
                    'data'    => [
                        'response' => $base64Image,
                    ],
                ]);
            }

            // If the response contains binary data or different structure, handle accordingly
            return response()->json([
                'success' => true,
                'data'    => $response,
            ]);
        } catch (Exception $e) {
            // Log the error for debugging
            \Log::error('Error in generateImageStableDiffusion:', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 400); // 400 for client-side errors
        }
    }

}
