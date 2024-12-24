<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RainbowDashService
{
    protected $baseUrl;

    public function __construct()
    {
        // Base URL for Rainbow Dashboard API
        $this->baseUrl = 'https://dash.rainbowtel.net/api';
    }

    /**
     * Login to Rainbow Dashboard API.
     *
     * @param string $email
     * @param string $password
     * @return array|bool
     */
    public function login(string $email, string $password)
    {
        try {
            $response = Http::post("{$this->baseUrl}/login", [
                'email' => $email,
                'password' => $password,
            ]);

            if ($response->successful()) {
                return $response->json(); // Return the token or response data
            }

            return false; // If login fails, return false
        } catch (\Exception $e) {
            // Log error or handle exception
            \Log::error('RainbowDashService login error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Example method to fetch user data from Rainbow Dashboard.
     *
     * @param string $token
     * @return array|bool
     */
    public function getUserData(string $token)
    {
        try {
            $response = Http::withToken($token)->get("{$this->baseUrl}/user");

            if ($response->successful()) {
                return $response->json(); // Return user data
            }

            return false; // If fetching data fails, return false
        } catch (\Exception $e) {
            // Log error or handle exception
            \Log::error('RainbowDashService getUserData error: ' . $e->getMessage());
            return false;
        }
    }


    public function rainbowAccountSearch(string $token, string $search_field, string $search_value)
    {
        try {
            // Prepare the payload with the search field and value
            $payload = [
                $search_field => $search_value
            ];

            // Make a POST request to the /user endpoint with Bearer token and payload
            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/user", $payload);

            // Check if the response is successful (status code 200-299)
            if ($response->successful()) {
                return $response->json(); // Return the user data as an associative array
            }

            // Optionally, handle different status codes or log them
            \Log::warning('RainbowDashService rainbowAccountSearch failed with status: ' . $response->status());

            return false; // Return false if the request was not successful
        } catch (\Exception $e) {
            // Log the exception message for debugging purposes
            \Log::error('RainbowDashService rainbowAccountSearch error: ' . $e->getMessage());
            return false; // Return false in case of an exception
        }
    }
    /**
     * Search for a customer using a POST request.
     *
     * @param string $token
     * @param string $search_field
     * @param string $search_value
     * @return array|false
     */
    public function customerSearch(string $token, string $search_field, string $search_value)
    {
        // Define allowed search fields to prevent invalid queries
        $allowedFields = ['account','account_number','address']; // Modify based on API specifications

        if (!in_array($search_field, $allowedFields)) {
            Log::warning('Invalid search field provided: ' . $search_field);
            return false;
        }

        try {
            // Prepare the payload with the validated search field and value
            $payload = [
                'search_field'=>$search_field,'search_value' => $search_value
            ];


            Log::error($payload);

            // Make a POST request to the /user/search endpoint with Bearer token and payload
            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/customer_search", $payload);

            Log::error($response);

            // Check if the response is successful (status code 200-299)
            if ($response->successful()) {

                Log::error('successful request');


                return $response->json(); // Return the user data as an associative array
            }

            // Handle specific status codes
            switch ($response->status()) {
                case 400:
                    Log::error('Bad Request: ' . $response->body());
                    break;
                case 401:
                    Log::error('Unauthorized: Invalid token.');
                    break;
                case 404:
                    Log::info('User not found for search criteria 404: ' . json_encode($payload));
                    break;
                default:
                    Log::warning('Unexpected status code ' . $response->status());
                   // Log::warning('Unexpected status code ' . $response->status() . ': ' . $response->body());
                    break;
            }

            return false; // Return false if the request was not successful
        } catch (\Exception $e) {
            // Log the exception message for debugging purposes
            Log::error('RainbowDashService customerSearch error: ' . $e->getMessage());
            return false; // Return false in case of an exception
        }
    }

    /**
     * Verify the customer's CPNI answer.
     *
     * @param string $token
     * @param string $customer_id
     * @param string $question_id
     * @param string $answer
     * @return array|false
     */
    public function verifyCpniAnswer(string $token, string $account, string $question, string $answer)
    {
        try {
            // Prepare the payload with customer ID, question ID, and answer
            $payload = [
                'account' => $account,
                'question' => $question,
                'answer' => $answer
            ];

            // Make a POST request to the /user/verify-cpni endpoint with Bearer token and payload
            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/verify_cpni", $payload);


            Log::error($response);


            // Check if the response is successful
            if ($response->successful()) {
                return $response->json(); // Return the customer data as an associative array
            }

            // Handle specific status codes
            switch ($response->status()) {
                case 400:
                    Log::error('Bad Request: ' . $response->body());
                    break;
                case 401:
                    Log::error('Unauthorized: Invalid token.');
                    break;
                case 403:
                    Log::warning('CPNI Verification Failed: ' . $response->body());
                    break;
                case 404:
                    Log::info('Customer not found: ' . json_encode($payload));
                    break;
                default:
                    Log::warning('Unexpected status code ' . $response->status() . ': ' . $response->body());
                    break;
            }

            return false; // Return false if the request was not successful
        } catch (\Exception $e) {
            // Log the exception message for debugging purposes
            Log::error('RainbowDashService verifyCpniAnswer error: ' . $e->getMessage());
            return false; // Return false in case of an exception
        }
    }


    /**
     * Add other methods for specific API requests as needed.
     */
}
