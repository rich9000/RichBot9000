<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Executors\RainbowDashboardTicketExecutor;
use Illuminate\Support\Facades\Log;

class CheckTicketDashboard extends Command
{
    protected $signature = 'check:ticket-dashboard';
    protected $description = 'Check the Rainbow Dashboard ticket executor functionality';

    protected $executor;

    public function __construct()
    {
        parent::__construct();
        $this->executor = new RainbowDashboardTicketExecutor();
    }

    public function handle()
    {
        $this->info('Starting Rainbow Dashboard Ticket Executor Check...');
        
        // Test login
        $this->info("\nTesting login...");
        $loginResult = $this->executor->login();
        if (!$loginResult) {
            $this->error('✗ Login failed');
            return 1;
        }
        $this->info('✓ Login successful');

        // Test getting all tickets
        $this->info("\nTesting get_all_tickets...");
        $allTicketsResult = $this->executor->rainbow_dashboard_get_all_tickets([]);
        if (!$allTicketsResult['success']) {
            $this->error('✗ Get all tickets failed: ' . ($allTicketsResult['error'] ?? 'Unknown error'));
            return 1;
        }
        $this->info('✓ Get all tickets successful');
        $this->info('Found ' . count($allTicketsResult['tickets'] ?? []) . ' tickets');

        // Test getting categories
        $this->info("\nTesting get_categories...");
        $categoriesResult = $this->executor->rainbow_dashboard_get_categories();
        if (!$categoriesResult['success']) {
            $this->error('✗ Get categories failed: ' . ($categoriesResult['error'] ?? 'Unknown error'));
            return 1;
        }
        $this->info('✓ Get categories successful');
        $this->info('Found ' . count($categoriesResult['categories'] ?? []) . ' categories');

        // Test getting recent tickets
        $this->info("\nTesting get_recent_tickets...");
        $recentTicketsResult = $this->executor->rainbow_dashboard_get_recent_tickets([]);
        if (!$recentTicketsResult['success']) {
            $this->error('✗ Get recent tickets failed: ' . ($recentTicketsResult['error'] ?? 'Unknown error'));
            return 1;
        }
        $this->info('✓ Get recent tickets successful');
        $this->info('Found ' . count($recentTicketsResult['results']['tickets'] ?? []) . ' recent tickets');

        // Test creating a ticket
        $this->info("\nTesting create_ticket...");
        $ticketData = [
            'title' => 'Test Ticket ' . date('Y-m-d H:i:s'),
            'description' => 'This is a test ticket created by the check command',
            'category_id' => 1,
            'priority' => 'High',
            'user_id' => 2,
            'status' => 'Open'
        ];
        $createResult = $this->executor->rainbow_dashboard_create_ticket($ticketData);
        if (!$createResult['success']) {
            $this->error('✗ Create ticket failed: ' . ($createResult['error'] ?? 'Unknown error'));
            return 1;
        }
        $this->info('✓ Create ticket successful');
        $ticketId = $createResult['ticket']['id'] ?? null;

        if ($ticketId) {
            // Test getting the created ticket
            $this->info("\nTesting get_ticket...");
            $getTicketResult = $this->executor->rainbow_dashboard_get_ticket(['ticket_id' => $ticketId]);
            if (!$getTicketResult['success']) {
                $this->error('✗ Get ticket failed: ' . ($getTicketResult['error'] ?? 'Unknown error'));
                return 1;
            }
            $this->info('✓ Get ticket successful');

            // Test adding a reply
            $this->info("\nTesting add_reply...");
            $replyResult = $this->executor->rainbow_dashboard_add_reply([
                'ticket_id' => $ticketId,
                'content' => 'This is a test reply added by the check command'
            ]);
            if (!$replyResult['success']) {
                $this->error('✗ Add reply failed: ' . ($replyResult['error'] ?? 'Unknown error'));
                return 1;
            }
            $this->info('✓ Add reply successful');

            // Test updating ticket assignment
            $this->info("\nTesting update_assignment...");
            $updateAssignmentResult = $this->executor->rainbow_dashboard_update_assignment([
                'ticket_id' => $ticketId,
                'user_id' => 2
            ]);
            if (!$updateAssignmentResult['success']) {
                $this->error('✗ Update assignment failed: ' . ($updateAssignmentResult['error'] ?? 'Unknown error'));
                return 1;
            }
            $this->info('✓ Update assignment successful');

            // Test updating ticket details
            $this->info("\nTesting update_ticket...");
            $updateTicketResult = $this->executor->rainbow_dashboard_update_ticket([
                'ticket_id' => $ticketId,
                'ticket_data' => [
                    'title' => 'Updated Test Ticket ' . date('Y-m-d H:i:s'),
                    'description' => 'This ticket has been updated by the check command',
                    'priority' => 'Medium'
                ]
            ]);
            if (!$updateTicketResult['success']) {
                $this->error('✗ Update ticket failed: ' . ($updateTicketResult['error'] ?? 'Unknown error'));
                return 1;
            }
            $this->info('✓ Update ticket successful');

            // Test closing the ticket
            $this->info("\nTesting close_ticket...");
            $closeResult = $this->executor->rainbow_dashboard_close_ticket(['ticket_id' => $ticketId]);
            if (!$closeResult['success']) {
                $this->error('✗ Close ticket failed: ' . ($closeResult['error'] ?? 'Unknown error'));
                return 1;
            }
            $this->info('✓ Close ticket successful');

            // Test reopening the ticket
            $this->info("\nTesting reopen_ticket...");
            $reopenResult = $this->executor->rainbow_dashboard_reopen_ticket(['ticket_id' => $ticketId]);
            if (!$reopenResult['success']) {
                $this->error('✗ Reopen ticket failed: ' . ($reopenResult['error'] ?? 'Unknown error'));
                return 1;
            }
            $this->info('✓ Reopen ticket successful');

            // Close the ticket again to clean up
            $this->executor->rainbow_dashboard_close_ticket(['ticket_id' => $ticketId]);
        }

        $this->info("\n✓ All Rainbow Dashboard Ticket Executor checks completed successfully!");
        return 0;
    }
} 