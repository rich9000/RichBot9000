<?php

namespace App\Services;

use Exception;

class StableDiffusionClient
{
    protected $baseUrl;
    protected $curlOptions;

    /**
     * Constructor for StableDiffusionClient.
     *
     * @param string $baseUrl The base URL of the Stable Diffusion API.
     */
    public function __construct($baseUrl = 'http://192.168.0.104::7860')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ];
    }

    /**
     * Sends an HTTP request to the Stable Diffusion API.
     *
     * @param string        $method         The HTTP method (GET, POST, etc.).
     * @param string        $endpoint       The API endpoint.
     * @param array|null    $data           The data to send in the request body.
     * @param array         $headers        Additional HTTP headers.
     * @param callable|null $streamCallback A callback function to handle streaming responses.
     *
     * @return mixed The decoded JSON response or true if streaming.
     *
     * @throws Exception If an error occurs during the request.
     */
    private function sendRequest($method, $endpoint, $data = null, $headers = [], $streamCallback = null)
    {
        $url = $this->baseUrl . $endpoint;
        $ch  = curl_init($url);

        $options = $this->curlOptions;
        $options[CURLOPT_CUSTOMREQUEST] = $method;

        if (!empty($headers)) {
            $options[CURLOPT_HTTPHEADER] = array_merge($this->curlOptions[CURLOPT_HTTPHEADER], $headers);
        }

        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            if ($data !== null) {
                $jsonData = json_encode($data);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Failed to encode JSON data: ' . json_last_error_msg());
                }
                $options[CURLOPT_POSTFIELDS] = $jsonData;
            }
        }

        if ($streamCallback !== null) {
            $options[CURLOPT_WRITEFUNCTION] = function ($ch, $chunk) use ($streamCallback) {
                $streamCallback($chunk);
                return strlen($chunk);
            };
        }

        curl_setopt_array($ch, $options);

        $response    = curl_exec($ch);
        $error       = curl_error($ch);
        $errno       = curl_errno($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($errno) {
            throw new Exception("cURL error ({$errno}): {$error}");
        }

        if ($statusCode >= 400) {
            // Include raw response in the exception for better debugging
            throw new Exception("HTTP error ({$statusCode}): {$response}");
        }

        if ($streamCallback !== null) {
            // Streaming responses are handled via the callback.
            return true;
        } else {
            $decodedResponse = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Include raw response in the exception for better debugging
                throw new Exception('Failed to parse JSON response: ' . json_last_error_msg() . '. Raw response: ' . $response);
            }
            return $decodedResponse;
        }
    }

    /**
     * Generate an image from a prompt using Stable Diffusion.
     *
     * @param string        $prompt         The prompt to generate an image for.
     * @param array|null    $options        Additional parameters (e.g., resolution).
     * @param callable|null $streamCallback A callback function to handle streaming responses.
     *
     * @return mixed The API response.
     *
     * @throws Exception If an error occurs during the request.
     */
    public function generateImage(
        $prompt,
        array $options = null,
        callable $streamCallback = null
    ) {

        \Log::info('Generating image via Stable Diffusion Client Generate Image:', [

            'prompt'  => $prompt,
            'options' => $options,
            'stream'  => $streamCallback,
        ]);





        $params = [
            'prompt' => $prompt,
        ];

        if ($options !== null) {
            $params = array_merge($params, $options);
        }

        // The Stable Diffusion API endpoint for image generation
        $endpoint = '/sdapi/v1/txt2img';

        return $this->sendRequest('POST', $endpoint, $params, [], $streamCallback);
    }

    // You can add more methods to interact with other Stable Diffusion endpoints as needed
}
