<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
class BambooHRProxyController extends Controller
{
    private string $baseUrl = 'https://api.bamboohr.com/api/gateway.php/rainbowtel/';

    public function proxyRequest(Request $request, $endpoint)
    {
        $client = new Client();
        //$apikey = auth()->user()->bamboo_hr_api_key;
        $apikey = $request->input('apikey', $request->user()->bamboo_hr_api_key);
        $method = strtoupper($request->method()); // GET, POST, etc.
        $fullUrl = $this->baseUrl . $endpoint;

        // Prepare options for the request
        $options = [
            'auth' => [$apikey, 'x'],
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];



        // Include query parameters or form data depending on the request method
        if ($method === 'GET') {
            $options['query'] = Arr::except($request->query(), ['apikey']); // Append query parameters minus the api key
        } elseif ($method === 'POST') {
            $options['json'] = Arr::except($request->all(), ['apikey']); // Send JSON body for POST
        }

        try {
            // Make the request to BambooHR
            $response = $client->request($method, $fullUrl, $options);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            // Return the response from BambooHR to the client
            return response()->json($body, $statusCode);

        } catch (GuzzleException $e) {
            // Handle any errors
            return response()->json([
                'error' => 'Failed to communicate with BambooHR API',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
