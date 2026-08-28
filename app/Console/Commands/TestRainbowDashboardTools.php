<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Executors\RainbowDashboardTicketExecutor;
use Illuminate\Support\Facades\Log;

class TestRainbowDashboardTools extends Command
{
    protected $signature = 'test:rainbow-dashboard-tools';
    protected $description = 'Test the Rainbow Dashboard ticket tools functionality';

    protected $executor;

    public function __construct()
    {
        parent::__construct();
        $this->executor = new RainbowDashboardTicketExecutor();
    }

    public function handle()
    {
        $this->info('Starting Rainbow Dashboard Tools Test...');
        
        // Test login first
        $this->testLogin();


    
        // Test getting all tickets
        $this->testGetAllTickets();
          

        // Test getting recent tickets
        $this->testGetRecentTickets();
        
        // Test looking up tickets by user
        $this->testLookupTicketsByUser('Rich Gmail');
        $this->testLookupTicketsByUser('Jason Smith');
        
        $this->testGetUserTickets(2);
        
        // Test getting a specific ticket
        $this->testGetTicket(34);
        $this->testGetTicket(26);
        
        $this->testGetCategories();

        // Test creating a ticket
        $id = $this->testCreateTicket();

        $this->testGetTicket($id);
        
        // Test adding a reply
        $this->testAddReply($id);

        // Test updating ticket assignment
        $this->testUpdateAssignment($id, 2);

        // Test updating ticket details
        $this->testUpdateTicket($id);

        // Test closing and reopening the ticket
        $this->testCloseTicket($id);
        $this->testGetTicket($id); // Verify it's closed
        $this->testReopenTicket($id);
        $this->testGetTicket($id); // Verify it's reopened
        $this->testCloseTicket($id);

        $this->info('Rainbow Dashboard Tools Test completed.');
    }

    protected function testLogin()
    {
        $this->info("\nTesting login...");
        $result = $this->executor->login('richcarroll@gmail.com', 'richlikestowork');

       

      
        if ($result) {
            $this->info('✓ Login successful');

            return true;

        } else {
            $this->error('✗ Login failed: ' . ($result['error'] ?? 'Unknown error'));

            return false;
        }
    }

    protected function testGetAllTickets()
    {
        
        $this->info("\nTesting get_all_tickets...");

        $result = $this->executor->rainbow_dashboard_get_all_tickets([]);

        dump($result);
        
        if ($result['success']) {
        
            $this->info('✓ Get all tickets successful');
            $tickets = $result['tickets'] ?? [];
            $this->info('Found ' . count($tickets) . ' tickets');
            
            if (count($tickets) > 0) {
                $this->info("\nFirst ticket details:");
                $this->table(
                    ['ID', 'Title', 'Status', 'Created'],
                    array_map(function($ticket) {
                        return [
                            $ticket['id'] ?? 'N/A',
                            $ticket['title'] ?? 'N/A',
                            $ticket['status'] ?? 'N/A',
                            $ticket['created_at'] ?? 'N/A'
                        ];
                    }, array_slice($tickets, 0, 5))
                );
            }
        } else {
            $this->error('✗ Get all tickets failed: ' . ($result['error'] ?? 'Unknown error'));
            if (isset($result['debug'])) {
                $this->error('Debug info: ' . json_encode($result['debug']));
            }
        }
    }

    protected function testGetUserTickets($userId)
    {
        $this->info("\nTesting get_user_tickets...");
        $result = $this->executor->rainbow_dashboard_get_user_tickets([
            'user_id' => $userId,
            'status' => 'open'
        ]);

     
        
        if ($result['success']) {
            $this->info('✓ Get user tickets successful');
            $tickets = $result['results']['tickets'] ?? [];
            $this->info('Found ' . count($tickets) . ' tickets for user');
            
            if (count($tickets) > 0) {
                $this->info("\nUser tickets:");
                $this->table(
                    ['ID', 'Title', 'Status', 'Created'],
                    array_map(function($ticket) {
                        return [
                            $ticket['id'] ?? 'N/A',
                            $ticket['title'] ?? 'N/A',
                            $ticket['status'] ?? 'N/A',
                            $ticket['created_at'] ?? 'N/A'
                        ];
                    }, array_slice($tickets, 0, 5))
                );
            }
        } else {
            $this->error('✗ Get user tickets failed: ' . ($result['error'] ?? 'Unknown error'));
            if (isset($result['debug'])) {
                $this->error('Debug info: ' . json_encode($result['debug']));
            }
        }
    }

    protected function testLookupTicketsByUser($userName)
    {
        $this->info("\n Testing lookup_tickets_by_user... " . $userName);
        $result = $this->executor->rainbow_dashboard_lookup_tickets_by_user([
            'user_name' => $userName
        ]);

        //dd($result);
        
        if ($result['success']) {
            $this->info('✓ Lookup tickets by user successful');


           // dd($result);
            $tickets = $result['results']['tickets'] ?? [];
            $this->info('Found ' . count($tickets) . ' tickets for user');
            
            if (count($tickets) > 0) {
                $this->info("\nUser tickets:");
                $this->table(
                    ['ID', 'Title', 'Status', 'Created'],
                    array_map(function($ticket) {
                        return [
                            $ticket['id'] ?? 'N/A',
                            $ticket['title'] ?? $ticket['name'] ?? 'N/A',
                            $ticket['status'] ?? 'N/A',
                            $ticket['created_at'] ?? 'N/A'
                        ];
                    }, array_slice($tickets, 0, 5))
                );
            }
        } else {
            $this->error('✗ Lookup tickets by user failed: ' . ($result['error'] ?? 'Unknown error'));
            if (isset($result['debug'])) {
                $this->error('Debug info: ' . json_encode($result['debug']));
            }
        }
    }

    protected function testGetCategories()
    {
        $this->info("\nTesting get_categories...");
        $result = $this->executor->rainbow_dashboard_get_categories([]);
        
        dump($result);


        
    }


    protected function testGetTicket($ticketId)
    {
        $this->info("\nTesting get_ticket...");
        $result = $this->executor->rainbow_dashboard_get_ticket([
            'ticket_id' => $ticketId
        ]);
        
        if ($result['success']) {
            $this->info('✓ Get ticket successful');
            $ticket = $result['ticket'] ?? [];
            
            $this->info("\nTicket details:");
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $ticket['id'] ?? 'N/A'],
                    ['Title', $ticket['title'] ?? 'N/A'],
                    ['Status', $ticket['status'] ?? 'N/A'],
                    ['Created', $ticket['created_at'] ?? 'N/A'],
                    ['Description', $ticket['description'] ?? 'N/A'],
                    ['Category', $ticket['category'] ?? 'N/A']
                ]
            );
        } else {
            $this->error('✗ Get ticket failed: ' . ($result['error'] ?? 'Unknown error'));
            if (isset($result['debug'])) {
                $this->error('Debug info: ' . json_encode($result['debug']));
            }
        }
    }

    protected function testCreateTicket()
    {
        $this->info("\nTesting create_ticket...");


        $ticket = [
            'title' => 'Test Ticket ' . date('Y-m-d H:i:s'),
            'description' => 'This is a test ticket created by the test command',
            'category_id' => 1,
            'priority' => 'High',
            'user_id' => 2,
            'status' => 'Open',
            
        ];
        
            $result = $this->executor->rainbow_dashboard_create_ticket($ticket);
        
        
        if ($result['success']) {
            $this->info('✓ Create ticket successful');
            $this->info('Created ticket ID: ' . ($result['ticket_id'] ?? 'Unknown'));
            return $result['ticket']['id'] ?? null;
        } else {
            $this->error('✗ Create ticket failed: ' . json_encode($result));
            return null;
        }
    }

    protected function testAddReply($ticketId)
    {
        $this->info("\nTesting add_reply...");        
        
        if (!$ticketId) {
            $this->error('✗ Cannot test add_reply without a valid ticket ID');
            return;
        }
        
        $result = $this->executor->rainbow_dashboard_add_reply([
            'ticket_id' => $ticketId,
            'content' => 'This is a test reply added by the test command'
        ]);

        dump($result);
        
        if ($result['success']) {
            $this->info('✓ Add reply successful');
        } else {
            $this->error('✗ Add reply failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    protected function testCloseTicket($ticketId)
    {
        $this->info("\nTesting close_ticket...");
        $result = $this->executor->rainbow_dashboard_close_ticket([
            'ticket_id' => $ticketId
        ]);
        
        if ($result['success']) {
            $this->info('✓ Close ticket successful');
            $ticket = $result['ticket'] ?? [];
            $this->info("\nTicket status after closing:");
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $ticket['id'] ?? 'N/A'],
                    ['Status', $ticket['status'] ?? 'N/A'],
                    ['Closed At', $ticket['closed_at'] ?? 'N/A'],
                    ['Closed By', $ticket['closed_by'] ?? 'N/A']
                ]
            );
        } else {
            $this->error('✗ Close ticket failed: ' . json_encode($result));
        }
    }

    protected function testReopenTicket($ticketId)
    {
        $this->info("\nTesting reopen_ticket...");
        $result = $this->executor->rainbow_dashboard_reopen_ticket([
            'ticket_id' => $ticketId
        ]);
        
        if ($result['success']) {
            $this->info('✓ Reopen ticket successful');
            $ticket = $result['ticket'] ?? [];
            $this->info("\nTicket status after reopening:");
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $ticket['id'] ?? 'N/A'],
                    ['Status', $ticket['status'] ?? 'N/A'],
                    ['Reopened At', $ticket['reopened_at'] ?? 'N/A'],
                    ['Reopened By', $ticket['reopened_by'] ?? 'N/A']
                ]
            );
        } else {
            $this->error('✗ Reopen ticket failed: ' . json_encode($result));
        }
    }

    protected function testGetRecentTickets()
    {
        $this->info("\nTesting get_recent_tickets...");
        $result = $this->executor->rainbow_dashboard_get_recent_tickets([]);
        
        if ($result['success']) {
            $this->info('✓ Get recent tickets successful');
            $tickets = $result['results']['tickets'] ?? [];
            $this->info('Found ' . count($tickets) . ' recent tickets');
            
            if (count($tickets) > 0) {
                $this->info("\nRecent tickets:");
                $this->table(
                    ['ID', 'Title', 'Status', 'Created'],
                    array_map(function($ticket) {
                        return [
                            $ticket['id'] ?? 'N/A',
                            $ticket['title'] ?? $ticket['name'] ?? 'N/A',
                            $ticket['status'] ?? 'N/A',
                            $ticket['created_at'] ?? 'N/A'
                        ];
                    }, array_slice($tickets, 0, 5))
                );
            }
        } else {
            $this->error('✗ Get recent tickets failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    protected function testUpdateAssignment($ticketId, $userId)
    {
        $this->info("\nTesting update_assignment...");
        $result = $this->executor->rainbow_dashboard_update_assignment([
            'ticket_id' => $ticketId,
            'user_id' => $userId
        ]);
        
        if ($result['success']) {
            $this->info('✓ Update assignment successful');
            $this->info('Ticket assigned to user: ' . ($result['ticket']['assigned_to'] ?? 'Unknown'));
        } else {
            $this->error('✗ Update assignment failed: ' . json_encode($result));
        }
    }

    protected function testUpdateTicket($ticketId)
    {
        $this->info("\nTesting update_ticket...");
        $result = $this->executor->rainbow_dashboard_update_ticket([
            'ticket_id' => $ticketId,
            'ticket_data' => [
                'title' => 'Updated Test Ticket ' . date('Y-m-d H:i:s'),
                'description' => 'This ticket has been updated by the test command',
                'category_id' => 1,
                'priority' => 'Medium'
            ]
        ]);
        
        if ($result['success']) {
            $this->info('✓ Update ticket successful');
            $this->info("\nUpdated ticket details:");
            $this->table(
                ['Field', 'Value'],
                [
                    ['Title', $result['ticket']['title'] ?? 'N/A'],
                    ['Description', $result['ticket']['description'] ?? 'N/A'],
                    ['Priority', $result['ticket']['priority'] ?? 'N/A'],
                    ['Category', $result['ticket']['category'] ?? 'N/A']
                ]
            );
        } else {
            $this->error('✗ Update ticket failed: ' . json_encode($result));
        }
    }
} 