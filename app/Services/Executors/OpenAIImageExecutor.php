<?php

namespace App\Services\Executors;

use App\Services\OpenAIImageService;
use Illuminate\Support\Facades\Log;

class OpenAIImageExecutor
{
    private $imageService;
    private $token;

    public function __construct()
    {
        $this->imageService = new OpenAIImageService();
    }

    /**
     * Generate an image from a prompt
     * 
     * @param array $arguments
     * @return array
     */
    public function openai_generate_image($arguments)
    {
        Log::info('[OpenAIImageExecutor] generate_image arguments: ' . json_encode($arguments));

        try {
            $prompt = $arguments['prompt'] ?? null;
            if (!$prompt) {
                return ['success' => false, 'error' => 'Prompt is required'];
            }

            $options = array_filter([
                'model' => $arguments['model'] ?? null,
                'n' => $arguments['n'] ?? null,
                'size' => $arguments['size'] ?? null,
               // 'quality' => $arguments['quality'] ?? null,
                //'style' => $arguments['style'] ?? null,
                //'response_format' => $arguments['response_format'] ?? null,
            ]);

            dump($options);

            $result = $this->imageService->generateImage($prompt, $options);

            dump($result);
           

            // If save_path is provided, save the image
            if (isset($arguments['save_path'])) {
                $path = $arguments['save_path'];
                $data = $result['data'][0] ?? [];

                if (isset($data['url'])) {


                    dump($data['url']);
                    $savedPath = $this->imageService->saveImageFromUrl($data['url'], $path);
                } elseif (isset($data['b64_json'])) {
                    $savedPath = $this->imageService->saveImageFromBase64($data['b64_json'], $path);
                } else {
                    $savedPath = null;
                }
                $result['saved_path'] = $savedPath;
            }

            return [
                'success' => true,
                'message' => 'Image generated successfully',
                'data' => $result
            ];
        } catch (\Exception $e) {
            Log::error('[OpenAIImageExecutor] generate_image error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Edit an existing image
     * 
     * @param array $arguments
     * @return array
     */
    public function openai_edit_image($arguments)
    {
        Log::info('[OpenAIImageExecutor] edit_image arguments: ' . json_encode($arguments));

        try {
            $imagePath = $arguments['image_path'] ?? null;
            $prompt = $arguments['prompt'] ?? null;

            if (!$imagePath || !$prompt) {
                return ['success' => false, 'error' => 'Image path and prompt are required'];
            }

            $options = array_filter([
                'model' => $arguments['model'] ?? null,
                'n' => $arguments['n'] ?? null,
                'size' => $arguments['size'] ?? null,
                'response_format' => $arguments['response_format'] ?? null,
            ]);

            // Add mask if provided
            if (isset($arguments['mask_path'])) {
                $options['mask'] = $arguments['mask_path'];
            }

            $result = $this->imageService->editImage($imagePath, $prompt, $options);

            // If save_path is provided, save the image
            if (isset($arguments['save_path'])) {
                $path = $arguments['save_path'];
                $data = $result['data'][0] ?? [];

                if (isset($data['url'])) {
                    $savedPath = $this->imageService->saveImageFromUrl($data['url'], $path);
                } elseif (isset($data['b64_json'])) {
                    $savedPath = $this->imageService->saveImageFromBase64($data['b64_json'], $path);
                } else {
                    $savedPath = null;
                }
                $result['saved_path'] = $savedPath;
            }

            return [
                'success' => true,
                'message' => 'Image edited successfully',
                'data' => $result
            ];
        } catch (\Exception $e) {
            Log::error('[OpenAIImageExecutor] edit_image error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a variation of an existing image
     * 
     * @param array $arguments
     * @return array
     */
    public function openai_create_variation($arguments)
    {
        Log::info('[OpenAIImageExecutor] create_variation arguments: ' . json_encode($arguments));

        try {
            $imagePath = $arguments['image_path'] ?? null;
            if (!$imagePath) {
                return ['success' => false, 'error' => 'Image path is required'];
            }

            $options = array_filter([
                'model' => $arguments['model'] ?? null,
                'n' => $arguments['n'] ?? null,
                'size' => $arguments['size'] ?? null,
                'response_format' => $arguments['response_format'] ?? null,
            ]);

            $result = $this->imageService->createVariation($imagePath, $options);

            // If save_path is provided, save the image
            if (isset($arguments['save_path'])) {
                $path = $arguments['save_path'];
                $data = $result['data'][0] ?? [];

                if (isset($data['url'])) {
                    $savedPath = $this->imageService->saveImageFromUrl($data['url'], $path);
                } elseif (isset($data['b64_json'])) {
                    $savedPath = $this->imageService->saveImageFromBase64($data['b64_json'], $path);
                } else {
                    $savedPath = null;
                }
                $result['saved_path'] = $savedPath;
            }

            return [
                'success' => true,
                'message' => 'Image variation created successfully',
                'data' => $result
            ];
        } catch (\Exception $e) {
            Log::error('[OpenAIImageExecutor] create_variation error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
} 