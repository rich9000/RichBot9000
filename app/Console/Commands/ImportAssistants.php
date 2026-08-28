<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Assistant;
use App\Models\ToolGroup;
use App\Models\Tool;
use App\Models\User;
use App\Services\AssistantExecutor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportAssistants extends Command
{
    protected $signature = 'assistants:import';
    protected $description = 'Import or update assistants for each tool group';

    public function handle()
    {
        // Check if user has permission
       // if (!Gate::allows('manage-assistants')) {
       //     $this->error('You do not have permission to manage assistants.');
       //     return 1;
      //  }

        $this->info('Starting assistant import...');

        // Get or create Rich Carroll's user account
        $richCarroll = User::firstOrCreate(
            ['email' => 'rich@richbot9000.com'],
            [
                'name' => 'Rich Carroll',
                'password' => bcrypt(\Illuminate\Support\Str::random(32))
            ]
        );

        // Import tool group assistants
        $toolGroups = ToolGroup::with('tools')->get();
        foreach ($toolGroups as $group) {
            $this->importToolGroupAssistant($group);
        }

        // Import Rich Carroll's Personal Assistant
        $this->importPersonalAssistant($richCarroll);

        // Import System Assistant
        $this->importSystemAssistant();

        $this->info('Assistant import completed successfully.');
        return 0;
    }

    protected function importToolGroupAssistant($group)
    {
        $assistant = Assistant::updateOrCreate(
            ['name' => "{$group->name} Assistant"],
            [
                'description' => "Assistant for managing {$group->name} tools",
                'system_message' => "You are the {$group->name} Assistant. Your role is to help users work with {$group->name} tools effectively.",
                'is_public' => true
            ]
        );

        // Associate tools with assistant
        $assistant->tools()->sync($group->tools->pluck('id'));

        $this->info("✓ {$group->name} Assistant imported/updated");
    }

    protected function importPersonalAssistant($user)
    {
        $assistant = Assistant::updateOrCreate(
            ['name' => "Rich Carroll's Personal Assistant"],
            [
                'user_id' => $user->id,
                'description' => "Personal assistant for Rich Carroll with access to all tools",
                'system_message' => "You are Rich Carroll's personal assistant. You have access to all tools and can help with any task.",
                'is_public' => false
            ]
        );

        // Associate all tools with personal assistant
        $assistant->tools()->sync(Tool::all()->pluck('id'));

        $this->info("✓ Rich Carroll's Personal Assistant imported/updated");
    }

    protected function importSystemAssistant()
    {
        $assistant = Assistant::updateOrCreate(
            ['name' => "System Assistant"],
            [
                'user_id' => 2,
                'description' => "System-level assistant with full access to all tools and administrative capabilities",
                'system_message' => "You are the System Assistant. You have full access to all tools and administrative capabilities. Your role is to help maintain and optimize the system.",
                'is_public' => false
            ]
        );

        // Associate all tools with system assistant
        $assistant->tools()->sync(Tool::all()->pluck('id'));

        $this->info("✓ System Assistant imported/updated");
    }

    protected function createGatekeeperAssistants(bool $force)
    {
        $user = DB::table('users')->first();
        
        if (!$user) {
            $this->error('No users found in the database. Please create a user first.');
            return;
        }

        // Rainbow Customer Gatekeeper
        $customerGatekeeper = $this->createGatekeeperAssistant(
            'Rainbow Customer Gatekeeper',
            'You are a specialized AI assistant responsible for managing and verifying customer access to Rainbow customer data. ' .
            'Your primary responsibilities include:' . "\n" .
            '- Verifying user permissions before allowing access to customer data' . "\n" .
            '- Ensuring CPNI compliance when accessing customer information' . "\n" .
            '- Managing customer search and lookup operations' . "\n" .
            '- Enforcing data privacy and security protocols' . "\n\n" .
            'You have access to the following tools:' . "\n" .
            '- rainbow_customer_search: Search for customers in the Rainbow system' . "\n" .
            '- rainbow_get_cpni_questions: Get CPNI verification questions for a customer' . "\n" .
            '- rainbow_verify_cpni: Verify CPNI for a customer' . "\n\n" .
            'Always verify user permissions and CPNI compliance before proceeding with any customer data operations.',
            ['rainbow_customer_search', 'rainbow_get_cpni_questions', 'rainbow_verify_cpni'],
            $user->id,
            $force
        );

        // Rainbow Ticket Dashboard Gatekeeper
        $ticketGatekeeper = $this->createGatekeeperAssistant(
            'Rainbow Ticket Dashboard Gatekeeper',
            'You are a specialized AI assistant responsible for managing access to the Rainbow Ticket Dashboard. ' .
            'Your primary responsibilities include:' . "\n" .
            '- Verifying user permissions before allowing access to ticket data' . "\n" .
            '- Managing ticket creation and updates' . "\n" .
            '- Handling ticket assignments and status changes' . "\n" .
            '- Ensuring proper ticket workflow and escalation procedures' . "\n\n" .
            'You have access to the following tools:' . "\n" .
            '- rainbow_dashboard_get_all_tickets: Get all tickets from the helpdesk system' . "\n" .
            '- rainbow_dashboard_get_user_tickets: Get tickets for a specific user' . "\n" .
            '- rainbow_dashboard_lookup_tickets_by_user: Lookup tickets by username' . "\n" .
            '- rainbow_dashboard_get_ticket: Get a specific ticket by ID' . "\n" .
            '- rainbow_dashboard_create_ticket: Create a new ticket' . "\n" .
            '- rainbow_dashboard_add_reply: Add a reply to an existing ticket' . "\n" .
            '- rainbow_dashboard_close_ticket: Close an existing ticket' . "\n" .
            '- rainbow_dashboard_reopen_ticket: Reopen a closed ticket' . "\n" .
            '- rainbow_dashboard_get_recent_tickets: Get recent tickets with pattern analysis' . "\n" .
            '- rainbow_dashboard_update_assignment: Update ticket assignment' . "\n" .
            '- rainbow_dashboard_update_ticket: Update ticket details' . "\n\n" .
            'Always verify user permissions and follow proper ticket management procedures.',
            [
                'rainbow_dashboard_get_all_tickets',
                'rainbow_dashboard_get_user_tickets',
                'rainbow_dashboard_lookup_tickets_by_user',
                'rainbow_dashboard_get_ticket',
                'rainbow_dashboard_create_ticket',
                'rainbow_dashboard_add_reply',
                'rainbow_dashboard_close_ticket',
                'rainbow_dashboard_reopen_ticket',
                'rainbow_dashboard_get_recent_tickets',
                'rainbow_dashboard_update_assignment',
                'rainbow_dashboard_update_ticket'
            ],
            $user->id,
            $force
        );

        if ($customerGatekeeper) {
            $this->info("Successfully created/updated Rainbow Customer Gatekeeper");
        }
        if ($ticketGatekeeper) {
            $this->info("Successfully created/updated Rainbow Ticket Dashboard Gatekeeper");
        }
    }

    protected function createGatekeeperAssistant(string $name, string $systemMessage, array $toolNames, int $userId, bool $force)
    {
        $existingAssistant = Assistant::where('name', $name)->first();

        if ($existingAssistant && !$force) {
            $this->info("Assistant {$name} already exists. Skipping...");
            return $existingAssistant;
        }

        $assistantData = [
            'name' => $name,
            'system_message' => $systemMessage,
            'user_id' => $userId,
            'type' => 'gatekeeper',
            'interactive' => true,
            'model_id' => 1, // Default model ID, adjust as needed
        ];

        if ($existingAssistant) {
            $existingAssistant->update($assistantData);
            $assistant = $existingAssistant;
        } else {
            $assistant = Assistant::create($assistantData);
        }

        // Get tool IDs and sync them
        $toolIds = Tool::whereIn('name', $toolNames)->pluck('id');
        $assistant->tools()->sync($toolIds);

        return $assistant;
    }
} 