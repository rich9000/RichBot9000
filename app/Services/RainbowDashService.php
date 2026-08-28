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

    public function lookupEmail(string $token, string $email)
    {
        $response = Http::withToken($token)->get("{$this->baseUrl}/lookup_email", ['email' => $email]);
        return $response->json();
    }

    public function lookupPhoneNumber(string $token, string $phone_number)
    {

        $phone_number = preg_replace('/^\+?1?(\d{3})(\d{3})(\d{4})$/', '$1-$2-$3', $phone_number);
        $response = Http::withToken($token)->post("{$this->baseUrl}/lookup_phone_number", ['phone_number' => $phone_number]);
        

        Log::info('Lookup phone number', [$phone_number,$response]);

        return $response->json();
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


        Log::error('[RainbowDashService][TOOL] customerSearch search_field: ' . $search_field);
        Log::error('[RainbowDashService][TOOL] customerSearch search_value: ' . $search_value);

        // Define allowed search fields to prevent invalid queries
        $allowedFields = ['account','account_number','address','phone_number']; // Modify based on API specifications

        if (!in_array($search_field, $allowedFields)) {
            Log::warning('Invalid search field provided: ' . $search_field);
            return false;
        }

        try {
            // Prepare the payload with the validated search field and value
            $payload = [
                'search_field'=>$search_field,'search_value' => $search_value
            ];


            Log::error('[RainbowDashService] customerSearch payload: ' . json_encode($payload));

            // Make a POST request to the /user/search endpoint with Bearer token and payload
            $response = Http::withToken($token)->timeout(60)
                ->post("{$this->baseUrl}/customer_search", $payload);

            Log::error('[RainbowDashService] customerSearch response: ' . json_encode($response));

            // Check if the response is successful (status code 200-299)
            if ($response->successful()) {

                Log::error('successful request');


                return $response->json(); // Return the user data as an associative array
            }

            // Handle specific status codes
            switch ($response->status()) {
                case 400:
                    Log::error('[RainbowDashService] customerSearch 400: ' . $response->body());
                    break;
                case 401:
                    Log::error('[RainbowDashService] customerSearch 401: ' . $response->body());
                    break;
                case 404:
                    Log::info('[RainbowDashService] customerSearch 404: ' . json_encode($payload));
                    break;
                default:
                    Log::warning('[RainbowDashService] customerSearch unexpected status code ' . $response->status());
                   // Log::warning('Unexpected status code ' . $response->status() . ': ' . $response->body());
                    break;
            }

            return false; // Return false if the request was not successful
        } catch (\Exception $e) {
            // Log the exception message for debugging purposes
            Log::error('[RainbowDashService] customerSearch error: ' . $e->getMessage());
            return false; // Return false in case of an exception
        }
    }


    public function getCpniQuestions(string $token, string $account)
    {
        Log::info('[RainbowDashService] getCpniQuestions account: ' . $account);

        $url = "{$this->baseUrl}/get_cpni_questions";
        $payload = ['account' => $account];

        $response = Http::withToken($token)
            ->post($url, $payload);

        Log::info('[RainbowDashService] getCpniQuestions response: ' . json_encode($response->json()));

        return $response->json();
    }

    /**
     * Verify the customer's CPNI answer.
     *
     * @param string $token
     * @param string $account_number
     * @param string $question
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
     * Lookup a helpdesk user by user_name via Rainbow Dash API.
     *
     * @param string $token
     * @param string $user_name
     * @return array|false
     */
    public function helpdesk_user_lookup(string $token, string $user_name)
    {
        try {
            Log::info('[RainbowDashService] helpdesk_user_lookup request', ['user_name' => $user_name]);
            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->post("{$this->baseUrl}/helpdesk_user_lookup", ['user_name' => $user_name]);
            Log::info('[RainbowDashService] helpdesk_user_lookup response', ['response' => $response->json()]);
            if ($response->successful()) {
                return $response->json();
            }
            Log::warning('[RainbowDashService] helpdesk_user_lookup failed', ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Exception $e) {
            Log::error('[RainbowDashService] helpdesk_user_lookup error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add other methods for specific API requests as needed.
     */
}
