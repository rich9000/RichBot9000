<?php

namespace App\Http\Controllers;

use App\Services\OpenAIImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OpenAIImageController extends Controller
{
    protected $imageService;

    public function __construct(OpenAIImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Generate an image from a prompt
     */
    public function generateImage(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:4000',
            'model' => 'nullable|string|in:dall-e-2,dall-e-3,gpt-image-1',
            'n' => 'nullable|integer|min:1|max:10',
            'size' => 'nullable|string|in:256x256,512x512,1024x1024,1024x1792,1792x1024',
            'quality' => 'nullable|string|in:standard,hd',
            'style' => 'nullable|string|in:vivid,natural',
            'response_format' => 'nullable|string|in:url,b64_json',
            'save_path' => 'nullable|string',
        ]);

        try {
            $options = array_filter([
                'model' => $validated['model'] ?? null,
                'n' => $validated['n'] ?? null,
                'size' => $validated['size'] ?? null,
                'quality' => $validated['quality'] ?? null,
                'style' => $validated['style'] ?? null,
                'response_format' => $validated['response_format'] ?? null,
            ]);

            $result = $this->imageService->generateImage($validated['prompt'], $options);

            // If save_path is provided, save the image
            if (isset($validated['save_path'])) {
                $path = $validated['save_path'];
                if (!Str::endsWith($path, ['.png', '.jpg', '.jpeg', '.webp'])) {
                    $path .= '.png';
                }
                $savedPath = $this->imageService->saveImageFromUrl($result['data'][0]['url'], $path);
                $result['saved_path'] = $savedPath;
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error("Image generation failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Edit an existing image
     */
    public function editImage(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|file|image|max:4096', // 4MB max
            'prompt' => 'required|string|max:4000',
            'mask' => 'nullable|file|image|max:4096',
            'n' => 'nullable|integer|min:1|max:10',
            'size' => 'nullable|string|in:256x256,512x512,1024x1024',
            'response_format' => 'nullable|string|in:url,b64_json',
            'save_path' => 'nullable|string',
        ]);

        try {
            // Store the uploaded image temporarily
            $imagePath = $validated['image']->store('temp');
            $fullPath = Storage::path($imagePath);

            $options = array_filter([
                'n' => $validated['n'] ?? null,
                'size' => $validated['size'] ?? null,
                'response_format' => $validated['response_format'] ?? null,
            ]);

            // Add mask if provided
            if (isset($validated['mask'])) {
                $maskPath = $validated['mask']->store('temp');
                $options['mask'] = fopen(Storage::path($maskPath), 'r');
            }

            $result = $this->imageService->editImage($fullPath, $validated['prompt'], $options);

            // Clean up temporary files
            Storage::delete($imagePath);
            if (isset($maskPath)) {
                Storage::delete($maskPath);
            }

            // If save_path is provided, save the image
            if (isset($validated['save_path'])) {
                $path = $validated['save_path'];
                if (!Str::endsWith($path, ['.png', '.jpg', '.jpeg', '.webp'])) {
                    $path .= '.png';
                }
                $savedPath = $this->imageService->saveImageFromUrl($result['data'][0]['url'], $path);
                $result['saved_path'] = $savedPath;
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error("Image edit failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a variation of an existing image
     */
    public function createVariation(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|file|image|max:4096', // 4MB max
            'n' => 'nullable|integer|min:1|max:10',
            'size' => 'nullable|string|in:256x256,512x512,1024x1024',
            'response_format' => 'nullable|string|in:url,b64_json',
            'save_path' => 'nullable|string',
        ]);

        try {
            // Store the uploaded image temporarily
            $imagePath = $validated['image']->store('temp');
            $fullPath = Storage::path($imagePath);

            $options = array_filter([
                'n' => $validated['n'] ?? null,
                'size' => $validated['size'] ?? null,
                'response_format' => $validated['response_format'] ?? null,
            ]);

            $result = $this->imageService->createVariation($fullPath, $options);

            // Clean up temporary file
            Storage::delete($imagePath);

            // If save_path is provided, save the image
            if (isset($validated['save_path'])) {
                $path = $validated['save_path'];
                if (!Str::endsWith($path, ['.png', '.jpg', '.jpeg', '.webp'])) {
                    $path .= '.png';
                }
                $savedPath = $this->imageService->saveImageFromUrl($result['data'][0]['url'], $path);
                $result['saved_path'] = $savedPath;
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error("Image variation failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 