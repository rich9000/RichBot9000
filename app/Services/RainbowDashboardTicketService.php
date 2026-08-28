<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RainbowDashboardTicketService
{
    protected $baseUrl;
    protected $token;
    protected $tokenExpiry;

    public function __construct($baseUrl = null)
    {
        if($baseUrl){
            $this->baseUrl = $baseUrl;
        } else {
            $this->baseUrl = 'https://dash.rainbowtel.net';
        }

        //$this->baseUrl = $baseUrl ?? config('services.ticket_api.url');
    }

    /**
     * Authenticate with the ticket system and get a token
     *
     * @param string $email
     * @param string $password
     * @return bool
     */
    public function login($email, $password)
    {
        try {
            Log::info('[RainbowDashboardTicketService][FUNCTION CALLING] Starting login attempt', [
                'email' => $email
            ]);

            $response = Http::post($this->baseUrl . '/api/auth/login', [
                'email' => $email,
                'password' => $password
            ]);

            Log::info('[RainbowDashboardTicketService][FUNCTION CALLING] Login response received', [
                'status' => $response->status()
            ]);

            if ($response->successful()) {
                Log::info('[RainbowDashboardTicketService][FUNCTION CALLING] Login successful');
                $data = $response->json();

                $this->token = $data['token'];

                // Cache the token for 24 hours
                Cache::put('ticket_api_token', $this->token, now()->addHours(24));
                Log::info('[RainbowDashboardTicketService][FUNCTION CALLING] Token cached successfully');

                return true;
            }

            Log::warning('[RainbowDashboardTicketService][FUNCTION CALLING] Login failed', [
                'status' => $response->status()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('[RainbowDashboardTicketService][FUNCTION CALLING] Login exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get the authentication token
     *
     * @return string|null
     */
    protected function getToken()
    {
        if (!$this->token) {
            $this->token = Cache::get('ticket_api_token');
        }
        return $this->token;
    }

    /**
     * Make an HTTP request to the ticket API
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array|null
     */
    protected function makeRequest($method, $endpoint, $data = [])
    {
        try {
            Log::info('[RainbowDashboardTicketService][FUNCTION CALLING] Making API request', [
                'method' => $method,
                'endpoint' => $endpoint,
                'data' => $data
            ]);

            // Get token from cache if not set
            if (!$this->token) {
                $this->token = Cache::get('ticket_api_token');
                Log::info('[RainbowDashboardTicketService][FUNCTION CALLING] Retrieved token from cache', [
                    'token_present' => !empty($this->token)
                ]);
            }

            if (!$this->token) {
                Log::error('[RainbowDashboardTicketService][FUNCTION CALLING] No token available for request');
                return null;
            }

            $url = $this->baseUrl . '/api/' . $endpoint;
            Log::info('[RainbowDashboardTicketService][FUNCTION CALLING] Making HTTP request', [
                'url' => $url,
                'method' => $method
            ]);

            //make sure it wants json
            $response = Http::withToken($this->token)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->timeout(30)                
                ->$method($url, $data);

            Log::info('[RainbowDashboardTicketService][FUNCTION CALLING] Response received', [
                'status' => $response->status()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('[RainbowDashboardTicketService][FUNCTION CALLING] Request successful');
                return $data;
            }

            Log::error('[RainbowDashboardTicketService][FUNCTION CALLING] Request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if($response->status() === 300) {
                $data = $response->json();
                Log::info('[RainbowDashboardTicketService][FUNCTION CALLING] Request Somewhat successful');
                return $data;

            }


            // If we get a 401, clear the token as it might be expired
            if ($response->status() === 401) {
                Log::warning('[RainbowDashboardTicketService][FUNCTION CALLING] Token expired, clearing cache');
                Cache::forget('ticket_api_token');
                $this->token = null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('[RainbowDashboardTicketService][FUNCTION CALLING] Request error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get all tickets
     *
     * @return array|null
     */
    public function getAllTickets()
    {
        Log::info('[RainbowDashboardTicketService] getAllTickets');
        return $this->makeRequest('get', 'tickets');
    }

    /**
     * Get tickets for a specific user
     *
     * @param int $userId
     * @param string $status
     * @return array|null
     */
    public function getUserTickets($userId, $status = 'open')
    {
        return $this->makeRequest('get', "tickets/user/{$userId}/{$status}");
    }

    /**
     * Look up tickets by username
     *
     * @param string $userName
     * @return array|null
     */
    public function lookupTicketsByUser($userName)
    {
        Log::info('[RainbowDashboardTicketService][FUNCTION CALLING] Starting ticket lookup by user', [
            'userName' => $userName
        ]);



        $response = $this->makeRequest('get', "tickets/lookup/{$userName}");

        if ($response === null) {
            Log::error('[RainbowDashboardTicketService][FUNCTION CALLING] Ticket lookup failed', [
                'userName' => $userName,
                'reason' => 'No response from API'
            ]);
            return null;
        }

        Log::info('[RainbowDashboardTicketService][FUNCTION CALLING] Ticket lookup completed', [
            'userName' => $userName,
            'tickets_found' => isset($response['tickets']) ? count($response['tickets']) : 0
        ]);

        return $response;
    }

    /**
     * Get recent tickets with pattern analysis
     *
     * @return array|null
     */
    public function getRecentTickets()
    {
        return $this->makeRequest('get', 'tickets/recent');
    }

    /**
     * Create a new ticket
     *
     * @param array $ticketData
     * @return array|null
     */
    public function createTicket($ticketData)
    {
        return $this->makeRequest('post', 'tickets', $ticketData);
    }

    /**
     * Get a specific ticket
     *
     * @param int $ticketId
     * @return array|null
     */
    public function getTicket($ticketId)
    {
        return $this->makeRequest('get', "tickets/{$ticketId}");
    }

    /**
     * Add a reply to a ticket
     *
     * @param int $ticketId
     * @param string $content
     * @return array|null
     */
    public function addReply($ticketId, $content)
    {
        return $this->makeRequest('post', "tickets/{$ticketId}/reply", [
            'content' => $content
        ]);
    }

    /**
     * Close a ticket
     *
     * @param int $ticketId
     * @return array|null
     */
    public function closeTicket($ticketId)
    {
        return $this->makeRequest('post', "tickets/{$ticketId}/close");
    }

    /**
     * Reopen a ticket
     *
     * @param int $ticketId
     * @return array|null
     */
    public function reopenTicket($ticketId)
    {
        return $this->makeRequest('post', "tickets/{$ticketId}/reopen");
    }

    /**
     * Update ticket assignment
     *
     * @param int $ticketId
     * @param int $userId
     * @return array|null
     */
    public function updateAssignment($ticketId, $userId)
    {
        return $this->makeRequest('post', "tickets/{$ticketId}/assign", [
            'assigned_to' => $userId
        ]);
    }

    /**
     * Get all ticket categories
     *
     * @return array|null
     */
    public function getCategories()
    {
        return $this->makeRequest('get', 'tickets/categories');
    }

    /**
     * Update a ticket's details
     * 
     *
     * @param int $ticketId
     * @param array $ticketData
     * @return array|null
     * @throws \Exception if user is not authorized
     */
    public function updateTicket($ticketId, $ticketData)
    {
        return $this->makeRequest('put', "tickets/{$ticketId}", $ticketData);
    }

    /**
     * Look up a user by username
     *
     * @param string $userName
     * @return array|null
     */
    public function lookupUser($userName)
    {
        Log::info('[RainbowDashboardTicketService][FUNCTION CALLING] Starting user lookup', [
            'userName' => $userName
        ]);

        $response = $this->makeRequest('get', "tickets/lookup/{$userName}");

        Log::info('[RainbowDashboardTicketService][FUNCTION CALLING] User lookup response', [
            'response' => $response
        ]);
        
        if ($response === null) {
            Log::error('[RainbowDashboardTicketService][FUNCTION CALLING] User lookup failed', [
                'userName' => $userName,
                'reason' => 'No response from API'
            ]);
            return null;
        }

        // Handle multiple users found case
        if (isset($response['users']) && is_array($response['users'])) {
            Log::info('[RainbowDashboardTicketService][FUNCTION CALLING] Multiple users found', [
                'userName' => $userName,
                'count' => count($response['users'])
            ]);
            return [
                'success' => true,
                'multiple_users' => true,
                'users' => $response['users'],
                'message' => 'Multiple users found. Please select a specific user.'
            ];
        }

        Log::info('[RainbowDashboardTicketService][FUNCTION CALLING] User lookup completed', [
            'userName' => $userName,
            'user_found' => isset($response['user'])
        ]);

        return $response;
    }

    /**
     * Get tickets for the currently logged in user
     *
     * @param string $status
     * @return array|null
     */
    public function getMyTickets($user_id, $status = 'open')
    {
        Log::info('[RainbowDashboardTicketService][FUNCTION CALLING] Getting my tickets', [
            'status' => $status
        ]);

        return $this->makeRequest('get', "tickets/user/{$user_id}/{$status}");
    }
} 