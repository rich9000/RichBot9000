<?php

namespace App\Services\executors;

use App\Services\RainbowDashboardTicketService;
use Illuminate\Support\Facades\Log;

class RainbowDashboardTicketExecutor
{
    private $ticketService;
    private $token;
    private $session_data;
    private $conversation;

    public function __construct($conversation = null)
    {
        $this->ticketService = new RainbowDashboardTicketService();
        $this->conversation = $conversation;
        //$this->login();
    }

    public function setConversation($conversation)
    {
        $this->conversation = $conversation;
    }

    public function setSessionData($session_data)
    {
        $this->session_data = $session_data;
    }

    public function login()
    {



        $success = $this->ticketService->login('rich@rainbowtel.com', 'richlikestowork');
        
        //dd('login success: ' . json_encode($success));
        
        if ($success) {
            $this->token = true; // Token is handled internally by the service
            return true;
        } else {
            $this->token = false;
            return false;
        }
    }

    /**
     * Get all tickets
     * 
     * @param array $arguments
     * @return array
     */
    public function rainbow_dashboard_get_all_tickets($arguments)
    {
        if(is_string($arguments))
        {
            $arguments = json_decode($arguments, true);
        }

        Log::info('[RainbowDashboardTicketExecutor] get_all_tickets arguments: ' . json_encode($arguments));

        try {
            if (!$this->token) {
                $this->login();
            }

            if (!$this->token) {
                return ['success' => false, 'error' => 'Authentication failed'];
            }

            $results = $this->ticketService->getAllTickets();

            Log::info('[RainbowDashboardTicketExecutor] get_all_tickets results: ' . json_encode($results));

            if ($results) {
                $response = [
                    'success' => true,
                    'message' => 'Tickets retrieved successfully',
                    'tickets' => $results
                ];
                
                Log::info('[RainbowDashboardTicketExecutor] get_all_tickets response: ' . json_encode($response));

                return $response;
            }
           
            return ['success' => false, 'message' => 'No tickets found'];

        } catch (\Exception $e) {
            Log::error('[RainbowDashboardTicketExecutor] get_all_tickets error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get tickets for the currently logged in user
     * 
     * @param array $arguments
     * @return array
     */
    public function rainbow_dashboard_get_my_tickets($arguments)
    {
        if(is_string($arguments))
        {
            $arguments = json_decode($arguments, true);
        }

        Log::info('[RainbowDashboardTicketExecutor] get_my_tickets arguments: ' . json_encode($arguments));

        try {
            if (!$this->token) {
                $this->login();
            }

            if (!$this->token) {
                return ['success' => false, 'error' => 'Authentication failed'];
            }


            $user_id = $this->conversation->path_state['user']['id'];
            if(!$user_id) {


                Log::error('[RainbowDashboardTicketExecutor] get_my_tickets error: User ID is required'.json_encode($this->conversation->path_state));

                return ['success' => false, 'error' => 'User ID is required'];
            }

            $status = $arguments['status'] ?? 'open';
            $results = $this->ticketService->getMyTickets($user_id, $status); 

            Log::info('[RainbowDashboardTicketExecutor] get_my_tickets results: ' . json_encode($results));
            
            if ($results) {
                return [
                    'success' => true,
                    'message' => 'My tickets retrieved successfully',
                    'tickets' => $results
                ];
            }
           
            return ['success' => false, 'message' => 'No tickets found'];

        } catch (\Exception $e) {
            Log::error('[RainbowDashboardTicketExecutor] get_my_tickets error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get tickets for a specific user
     * 
     * @param array $arguments
     * @return array
     */
    public function rainbow_dashboard_get_user_tickets($arguments)
    {


        if(is_string($arguments))
        {
            $arguments = json_decode($arguments, true);
        }

        Log::info('[RainbowDashboardTicketExecutor][FUNCTION CALLING] Starting get user tickets', [
            'arguments' => $arguments
        ]);

        try {
            if (!$this->token) {
                $this->login();
            }

            $user_id = $arguments['user_id'] ?? null;
            $status = $arguments['status'] ?? 'open';

            if (!$user_id) {
                Log::warning('[RainbowDashboardTicketExecutor][FUNCTION CALLING] Missing user_id parameter');
                return ['success' => false, 'error' => 'User ID is required'];
            }

            if (!$this->token) {
                Log::error('[RainbowDashboardTicketExecutor][FUNCTION CALLING] Authentication failed');
                return ['success' => false, 'error' => 'Authentication failed'];
            }

            $results = $this->ticketService->getUserTickets($user_id, $status);

            Log::info('[RainbowDashboardTicketExecutor][FUNCTION CALLING] Get user tickets completed', [
                'user_id' => $user_id,
                'status' => $status,
                'tickets_found' => isset($results['tickets']) ? count($results['tickets']) : 0
            ]);
            
            if ($results) {



                $app_state = $this->conversation->path_state ?? [];
                $app_state['user'] = $results['user'];
                $this->conversation->path_state = $app_state;
                $this->conversation->save();








                return [
                    'success' => true,
                    'message' => 'User tickets retrieved successfully',
                    'results' => $results
                ];
            }
           
            return ['success' => false, 'message' => 'No tickets found for user'];

        } catch (\Exception $e) {
            Log::error('[RainbowDashboardTicketExecutor][FUNCTION CALLING] Get user tickets failed', [
                'error' => $e->getMessage(),
                'user_id' => $user_id ?? 'not provided',
                'status' => $status
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Lookup tickets by username
     * 
     * @param array $arguments
     * @return array
     */
    public function rainbow_dashboard_lookup_tickets_by_user($arguments)
    {
        if(is_string($arguments))
        {
            $arguments = json_decode($arguments, true);
        }

        Log::info('[RainbowDashboardTicketExecutor][FUNCTION CALLING] Starting ticket lookup by user', [
            'arguments' => $arguments
        ]);

        try {
            if (!$this->token) {
                $this->login();
            }

            $user_name = $arguments['user_name'] ?? null;

            if (!$user_name) {
                Log::warning('[RainbowDashboardTicketExecutor][FUNCTION CALLING] Missing username parameter');
                return ['success' => false, 'error' => 'Username is required'];
            }

            if (!$this->token) {
                Log::error('[RainbowDashboardTicketExecutor][FUNCTION CALLING] Authentication failed');
                return ['success' => false, 'error' => 'Authentication failed'];
            }

            $results = $this->ticketService->lookupTicketsByUser($user_name);

            Log::info('[RainbowDashboardTicketExecutor][FUNCTION CALLING] Ticket lookup completed', [
                'user_name' => $user_name,
                'tickets_found' => isset($results['tickets']) ? count($results['tickets']) : 0
            ]);
            
            if ($results) {
                return $results;
            }
           
            return ['success' => true, 'tickets' => null, 'message' => 'No tickets found for username'];

        } catch (\Exception $e) {
            Log::error('[RainbowDashboardTicketExecutor][FUNCTION CALLING] Ticket lookup failed', [
                'error' => $e->getMessage(),
                'user_name' => $user_name ?? 'not provided'
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Lookup user by username
     * 
     * @param array $arguments
     * @return array
     */
    public function rainbow_dashboard_lookup_user($arguments)
    {
        Log::info('[RainbowDashboardTicketExecutor][FUNCTION CALLING] Starting user lookup', [
            'arguments' => $arguments
        ]);

        if(is_string($arguments))
        {
            $arguments = json_decode($arguments, true);
        }

        try {
            if (!$this->token) {
                $this->login();
            }

            $user_name = $arguments['user_name'] ?? null;

            if (!$user_name) {
                Log::warning('[RainbowDashboardTicketExecutor][FUNCTION CALLING] Missing username parameter');
                return ['success' => false, 'error' => 'Username is required'];
            }

            if (!$this->token) {
                Log::error('[RainbowDashboardTicketExecutor][FUNCTION CALLING] Authentication failed');
                return ['success' => false, 'error' => 'Authentication failed'];
            }

            $results = $this->ticketService->lookupUser($user_name);

            Log::info('[RainbowDashboardTicketExecutor][FUNCTION CALLING] User lookup completed', [
                'user_name' => $user_name,
                'success' => $results['success'] ?? false,
                'multiple_users' => $results['multiple_users'] ?? false,
                'user_found' => isset($results['user'])
            ]);
            
            if ($results && isset($results['success']) && $results['success']) {
                // Handle multiple users case
                if (isset($results['multiple_users']) && $results['multiple_users']) {
                    return [
                        'success' => true,
                        'multiple_users' => true,
                        'message' => $results['message'],
                        'users' => $results['users']
                    ];
                }
                
                // Handle single user case
                return [
                    'success' => true,
                    'message' => 'User found successfully',
                    'user' => $results['user'] ?? null
                ];
            }
           
            return ['success' => false, 'message' => 'No user found with that username'];

        } catch (\Exception $e) {
            Log::error('[RainbowDashboardTicketExecutor][FUNCTION CALLING] User lookup failed', [
                'error' => $e->getMessage(),
                'user_name' => $user_name ?? 'not provided'
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get a specific ticket
     * 
     * @param array $arguments
     * @return array
     */
    public function rainbow_dashboard_get_ticket($arguments)
    {
        if(is_string($arguments))
        {
            $arguments = json_decode($arguments, true);
        }

        Log::info('[RainbowDashboardTicketExecutor] get_ticket arguments: ' . json_encode($arguments));

        try {
            if (!$this->token) {
                $this->login();
            }

            $ticket_id = $arguments['ticket_id'] ?? null;

            if (!$ticket_id) {
                return ['success' => false, 'error' => 'Ticket ID is required'];
            }

            if (!$this->token) {
                return ['success' => false, 'error' => 'Authentication failed'];
            }

            $results = $this->ticketService->getTicket($ticket_id);

            Log::info('[RainbowDashboardTicketExecutor] get_ticket results: ' . json_encode($results));
            
            if ($results) {
                return [
                    'success' => true,
                    'message' => 'Ticket retrieved successfully',
                    'ticket' => $results
                ];
            }
           
            return ['success' => false, 'message' => 'Ticket not found'];

        } catch (\Exception $e) {
            Log::error('[RainbowDashboardTicketExecutor] get_ticket error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }


        /** 
     * Get all ticket categories
     *
     * @return array|null
     */
    public function rainbow_dashboard_get_categories()
    {
        
        $results = $this->ticketService->getCategories();
        Log::info('[RainbowDashboardTicketExecutor] get_categories results: ' . json_encode($results));

       


        if ($results) {
            return [
                'success' => true,
                'message' => 'Categories retrieved successfully',
                'categories' => $results['categories']
            ];
        }

        return ['success' => false, 'message' => 'No categories found'];

    }

    /**
     * Create a new ticket
     * 
     * @param array $arguments
     * @return array
     */
    public function rainbow_dashboard_create_ticket($arguments)
    {
        if(is_string($arguments))
        {
            $arguments = json_decode($arguments, true);
        }

        $path_state = $this->conversation->path_state ?? [];
        $user_id = $path_state['user']['id'] ?? null;
        
        $arguments['user_id'] = $user_id;

        Log::info('[RainbowDashboardTicketExecutor] create_ticket arguments: ' . json_encode($arguments));

        try {
            if (!$this->token) {
                $this->login();
            }

            if (!$this->token) {
                return ['success' => false, 'error' => 'Authentication failed'];
            }

            $results = $this->ticketService->createTicket($arguments);

            Log::info('[RainbowDashboardTicketExecutor] create_ticket results: ' . json_encode($results));
            
            if ($results) {
                return [
                    'success' => true,
                    'message' => 'Ticket created successfully',
                    'ticket' => $results['ticket']
                ];
            }
           
            return ['success' => false, 'message' => 'Failed to create ticket'];

        } catch (\Exception $e) {
            Log::error('[RainbowDashboardTicketExecutor] create_ticket error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add a reply to a ticket
     * 
     * @param array $arguments
     * @return array
     */
    public function rainbow_dashboard_add_reply($arguments)
    {
        if(is_string($arguments))
        {
            $arguments = json_decode($arguments, true);
        }

        Log::info('[RainbowDashboardTicketExecutor] add_reply arguments: ' . json_encode($arguments));

        try {
            if (!$this->token) {
                $this->login();
            }

            $ticket_id = $arguments['ticket_id'] ?? null;
            $content = $arguments['content'] ?? null;

            if (!$ticket_id || !$content) {
                return ['success' => false, 'error' => 'Ticket ID and content are required'];
            }

            if (!$this->token) {
                return ['success' => false, 'error' => 'Authentication failed'];
            }

            $results = $this->ticketService->addReply($ticket_id, $content);

            Log::info('[RainbowDashboardTicketExecutor] add_reply results: ' . json_encode($results));
            
            if ($results) {
                return [
                    'success' => true,
                    'message' => 'Reply added successfully',
                    'reply' => $results
                ];
            }
           
            return ['success' => false, 'message' => 'Failed to add reply'];

        } catch (\Exception $e) {
            Log::error('[RainbowDashboardTicketExecutor] add_reply error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get recent tickets with pattern analysis
     *
     * @param array $arguments
     * @return array
     */
    public function rainbow_dashboard_get_recent_tickets($arguments)
    {
        if(is_string($arguments))
        {
            $arguments = json_decode($arguments, true);
        }

        Log::info('[RainbowDashboardTicketExecutor] get_recent_tickets arguments: ' . json_encode($arguments));

        try {
            if (!$this->token) {
                $this->login();
            }

            if (!$this->token) {
                return ['success' => false, 'error' => 'Authentication failed'];
            }

            $result = $this->ticketService->getRecentTickets();

            Log::info('[RainbowDashboardTicketExecutor] get_recent_tickets result: ' . json_encode($result));

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Recent tickets retrieved',
                    'results' => $result
                ];
            }

            return ['success' => false, 'message' => 'Failed to get recent tickets'];

        } catch (\Exception $e) {
            Log::error('[RainbowDashboardTicketExecutor] get_recent_tickets error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update ticket assignment
     *
     * @param array $arguments
     * @return array
     */
    public function rainbow_dashboard_update_assignment($arguments)
    {
        if(is_string($arguments))
        {
            $arguments = json_decode($arguments, true);
        }

        Log::info('[RainbowDashboardTicketExecutor] update_assignment arguments: ' . json_encode($arguments));

        try {
            if (!$this->token) {
                $this->login();
            }

            $ticketId = $arguments['ticket_id'] ?? null;
            $userId = $arguments['user_id'] ?? null;

            if (!$ticketId || !$userId) {
                return ['success' => false, 'error' => 'Ticket ID and User ID are required'];
            }

            if (!$this->token) {
                return ['success' => false, 'error' => 'Authentication failed'];
            }

            $result = $this->ticketService->updateAssignment($ticketId, $userId);

            Log::info('[RainbowDashboardTicketExecutor] update_assignment result: ' . json_encode($result));

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Ticket assignment updated',
                    'ticket' => $result
                ];
            }

            return ['success' => false, 'message' => 'Failed to update ticket assignment'];

        } catch (\Exception $e) {
            Log::error('[RainbowDashboardTicketExecutor] update_assignment error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update a ticket's details
     *
     * @param array $arguments
     * @return array
     */
    public function rainbow_dashboard_update_ticket($arguments)
    {
        if(is_string($arguments))
        {
            $arguments = json_decode($arguments, true);
        }

        Log::info('[RainbowDashboardTicketExecutor] update_ticket arguments: ' . json_encode($arguments));

        try {
            if (!$this->token) {
                $this->login();
            }

            $ticketId = $arguments['ticket_id'] ?? null;
            $ticketData = $arguments['ticket_data'] ?? null;

            if (!$ticketId || !$ticketData) {
                return ['success' => false, 'error' => 'Ticket ID and ticket data are required'];
            }

            if (!$this->token) {
                return ['success' => false, 'error' => 'Authentication failed'];
            }

            $result = $this->ticketService->updateTicket($ticketId, $ticketData);

            Log::info('[RainbowDashboardTicketExecutor] update_ticket result: ' . json_encode($result));

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Ticket updated successfully',
                    'ticket' => $result
                ];
            }

            return ['success' => false, 'message' => 'Failed to update ticket'];

        } catch (\Exception $e) {
            Log::error('[RainbowDashboardTicketExecutor] update_ticket error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Close a ticket
     * 
     * @param array $arguments
     * @return array
     */
    public function rainbow_dashboard_close_ticket($arguments)
    {
        if(is_string($arguments))
        {
            $arguments = json_decode($arguments, true);
        }

        Log::info('[RainbowDashboardTicketExecutor] close_ticket arguments: ' . json_encode($arguments));

        try {
            if (!$this->token) {
                $this->login();
            }

            $ticket_id = $arguments['ticket_id'] ?? null;

            if (!$ticket_id) {
                return ['success' => false, 'error' => 'Ticket ID is required'];
            }

            if (!$this->token) {
                return ['success' => false, 'error' => 'Authentication failed'];
            }

            $results = $this->ticketService->closeTicket($ticket_id);

            Log::info('[RainbowDashboardTicketExecutor] close_ticket results: ' . json_encode($results));
            
            if ($results) {
                return [
                    'success' => true,
                    'message' => 'Ticket closed successfully',
                    'ticket' => $results
                ];
            }
           
            return ['success' => false, 'message' => 'Failed to close ticket'];

        } catch (\Exception $e) {
            Log::error('[RainbowDashboardTicketExecutor] close_ticket error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reopen a ticket
     * 
     * @param array $arguments
     * @return array
     */
    public function rainbow_dashboard_reopen_ticket($arguments)
    {
        if(is_string($arguments))
        {
            $arguments = json_decode($arguments, true);
        }

        Log::info('[RainbowDashboardTicketExecutor] reopen_ticket arguments: ' . json_encode($arguments));

        try {
            if (!$this->token) {
                $this->login();
            }

            $ticket_id = $arguments['ticket_id'] ?? null;

            if (!$ticket_id) {
                return ['success' => false, 'error' => 'Ticket ID is required'];
            }

            if (!$this->token) {
                return ['success' => false, 'error' => 'Authentication failed'];
            }

            $results = $this->ticketService->reopenTicket($ticket_id);

            Log::info('[RainbowDashboardTicketExecutor] reopen_ticket results: ' . json_encode($results));
            
            if ($results) {
                return [
                    'success' => true,
                    'message' => 'Ticket reopened successfully',
                    'ticket' => $results
                ];
            }
           
            return ['success' => false, 'message' => 'Failed to reopen ticket'];

        } catch (\Exception $e) {
            Log::error('[RainbowDashboardTicketExecutor] reopen_ticket error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
} 