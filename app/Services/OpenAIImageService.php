<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OpenAIImageService
{
    private $client;
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('OPENAI_API_KEY');
        $this->baseUrl = 'https://api.openai.com/v1';
    }

    private function filterOptionsByModel($model, $options) {
        $allowed = [
            'dall-e-2' => ['prompt', 'model', 'n', 'size', 'response_format'],
            'dall-e-3' => ['prompt', 'model', 'n', 'size', 'quality', 'style', 'response_format'],
            'gpt-image-1' => [
                'prompt', 'model', 'n', 'size', 'quality', 'background', 'moderation',
                'output_compression', 'output_format'
            ],
        ];
        $filtered = [];
        foreach ($allowed[$model] ?? [] as $key) {
            if (isset($options[$key])) {
                $filtered[$key] = $options[$key];
            }
        }
        return $filtered;
    }

    /**
     * Generate an image from a prompt
     * 
     * @param string $prompt The text description of the desired image
     * @param array $options Additional options for image generation
     * @return array The generated image data
     */
    public function generateImage(string $prompt, array $options = [])
    {
        $model = $options['model'] ?? 'dall-e-2';
        $options['prompt'] = $prompt;

        // Set model-specific defaults if not set
        if ($model === 'dall-e-2') {
            $options['quality'] = $options['quality'] ?? 'standard';
            $options['response_format'] = $options['response_format'] ?? 'url';
            $options['size'] = $options['size'] ?? '1024x1024';
        } elseif ($model === 'dall-e-3') {
            $options['quality'] = $options['quality'] ?? 'standard';
            $options['response_format'] = $options['response_format'] ?? 'url';
            $options['size'] = $options['size'] ?? '1024x1024';
            $options['style'] = $options['style'] ?? 'vivid';
            $options['n'] = 1; // Only n=1 supported
        } elseif ($model === 'gpt-image-1') {
            $options['quality'] = $options['quality'] ?? 'auto';
            $options['size'] = $options['size'] ?? 'auto';
            // Do not set response_format
        }

        $payload = $this->filterOptionsByModel($model, $options);

        try {
            $response = $this->client->post("{$this->baseUrl}/images/generations", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception("OpenAI API returned unexpected HTTP code {$response->getStatusCode()}");
            }

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error("Image generation request failed: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Edit an existing image
     * 
     * @param string $imagePath Path to the image file to edit
     * @param string $prompt The text description of the desired edit
     * @param array $options Additional options for image editing
     * @return array The edited image data
     */
    public function editImage(string $imagePath, string $prompt, array $options = [])
    {
        $model = $options['model'] ?? 'dall-e-2';
        $options['prompt'] = $prompt;
        $options['image'] = fopen($imagePath, 'r');

        // Set model-specific defaults if not set
        if ($model === 'dall-e-2') {
            $options['quality'] = $options['quality'] ?? 'standard';
            $options['response_format'] = $options['response_format'] ?? 'url';
            $options['size'] = $options['size'] ?? '1024x1024';
        } elseif ($model === 'dall-e-3') {
            $options['quality'] = $options['quality'] ?? 'standard';
            $options['response_format'] = $options['response_format'] ?? 'url';
            $options['size'] = $options['size'] ?? '1024x1024';
            $options['style'] = $options['style'] ?? 'vivid';
            $options['n'] = 1; // Only n=1 supported
        } elseif ($model === 'gpt-image-1') {
            $options['quality'] = $options['quality'] ?? 'auto';
            $options['size'] = $options['size'] ?? 'auto';
            // Do not set response_format
        }

        $payload = $this->filterOptionsByModel($model, $options);

        try {
            $multipart = [];
            foreach ($payload as $key => $value) {
                if ($key === 'image' || $key === 'mask') {
                    $multipart[] = [
                        'name' => $key,
                        'contents' => $value
                    ];
                } else {
                    $multipart[] = [
                        'name' => $key,
                        'contents' => (string)$value
                    ];
                }
            }
            $response = $this->client->post("{$this->baseUrl}/images/edits", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'multipart' => $multipart
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception("OpenAI API returned unexpected HTTP code {$response->getStatusCode()}");
            }

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error("Image edit request failed: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Create a variation of an existing image
     * 
     * @param string $imagePath Path to the image file to create variation from
     * @param array $options Additional options for image variation
     * @return array The variation image data
     */
    public function createVariation(string $imagePath, array $options = [])
    {
        $model = $options['model'] ?? 'dall-e-2';
        $options['image'] = fopen($imagePath, 'r');

        // Set model-specific defaults if not set
        if ($model === 'dall-e-2') {
            $options['quality'] = $options['quality'] ?? 'standard';
            $options['response_format'] = $options['response_format'] ?? 'url';
            $options['size'] = $options['size'] ?? '1024x1024';
        } elseif ($model === 'dall-e-3') {
            $options['quality'] = $options['quality'] ?? 'standard';
            $options['response_format'] = $options['response_format'] ?? 'url';
            $options['size'] = $options['size'] ?? '1024x1024';
            $options['style'] = $options['style'] ?? 'vivid';
            $options['n'] = 1; // Only n=1 supported
        } elseif ($model === 'gpt-image-1') {
            $options['quality'] = $options['quality'] ?? 'auto';
            $options['size'] = $options['size'] ?? 'auto';
            // Do not set response_format
        }

        $payload = $this->filterOptionsByModel($model, $options);

        try {
            $multipart = [];
            foreach ($payload as $key => $value) {
                if ($key === 'image' || $key === 'mask') {
                    $multipart[] = [
                        'name' => $key,
                        'contents' => $value
                    ];
                } else {
                    $multipart[] = [
                        'name' => $key,
                        'contents' => (string)$value
                    ];
                }
            }
            $response = $this->client->post("{$this->baseUrl}/images/variations", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'multipart' => $multipart
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception("OpenAI API returned unexpected HTTP code {$response->getStatusCode()}");
            }

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error("Image variation request failed: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Save an image from URL to storage
     * 
     * @param string $imageUrl The URL of the image to save
     * @param string $path The storage path to save the image to
     * @return string The path where the image was saved
     */
    public function saveImageFromUrl(string $imageUrl, string $path): string
    {
        try {
            $imageContent = file_get_contents($imageUrl);
            if ($imageContent === false) {
                throw new \Exception("Failed to download image from URL");
            }

            Storage::put($path, $imageContent);

            dump($path);
            dump($imageContent);
            exit;

            return $path;
        } catch (\Exception $e) {
            Log::error("Failed to save image: {$e->getMessage()}");
            throw $e;
        }
    }

    public function saveImageFromBase64(string $b64, string $path): string
    {
        try {
            $imageContent = base64_decode($b64);
            if ($imageContent === false) {
                throw new \Exception("Failed to decode base64 image data");
            }
            Storage::put($path, $imageContent);
            return $path;
        } catch (\Exception $e) {
            Log::error("Failed to save base64 image: {$e->getMessage()}");
            throw $e;
        }
    }
} 