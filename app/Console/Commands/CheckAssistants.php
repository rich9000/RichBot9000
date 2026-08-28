<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Assistant;
use App\Models\ToolGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckAssistants extends Command
{
    protected $signature = 'assistants:check';
    protected $description = 'Check if all required assistants are properly registered in database';

    public function handle()
    {
        $this->info('Starting assistant check...');

        $missingAssistants = [];
        $existingAssistants = [];

        // Get all assistants from database
        $dbAssistants = Assistant::all()->keyBy('name');
        $this->info("Found {$dbAssistants->count()} assistants in database");

        // Check tool group assistants
        $toolGroups = ToolGroup::all();
        foreach ($toolGroups as $group) {
            $assistantName = "{$group->name} Assistant";
            if (!$dbAssistants->has($assistantName)) {
                $missingAssistants[] = [
                    'name' => $assistantName,
                    'type' => 'Tool Group Assistant',
                    'group' => $group->name
                ];
            } else {
                $existingAssistants[] = [
                    'name' => $assistantName,
                    'type' => 'Tool Group Assistant',
                    'group' => $group->name,
                    'is_public' => $dbAssistants[$assistantName]->is_public
                ];
            }
        }

        // Check Rich Carroll's Personal Assistant
        $richCarroll = User::where('email', 'rich@richbot9000.com')->first();
        if ($richCarroll) {
            $assistantName = "Rich Carroll's Personal Assistant";
            if (!$dbAssistants->has($assistantName)) {
                $missingAssistants[] = [
                    'name' => $assistantName,
                    'type' => 'Personal Assistant',
                    'user' => 'Rich Carroll'
                ];
            } else {
                $existingAssistants[] = [
                    'name' => $assistantName,
                    'type' => 'Personal Assistant',
                    'user' => 'Rich Carroll',
                    'is_public' => $dbAssistants[$assistantName]->is_public
                ];
            }
        }

        // Check System Assistant
        $assistantName = "System Assistant";
        if (!$dbAssistants->has($assistantName)) {
            $missingAssistants[] = [
                'name' => $assistantName,
                'type' => 'System Assistant'
            ];
        } else {
            $existingAssistants[] = [
                'name' => $assistantName,
                'type' => 'System Assistant',
                'is_public' => $dbAssistants[$assistantName]->is_public
            ];
        }

        // Check Gatekeeper Assistants
        $gatekeeperAssistants = [
            'Rainbow Customer Gatekeeper',
            'Rainbow Ticket Dashboard Gatekeeper'
        ];

        foreach ($gatekeeperAssistants as $assistantName) {
            if (!$dbAssistants->has($assistantName)) {
                $missingAssistants[] = [
                    'name' => $assistantName,
                    'type' => 'Gatekeeper Assistant'
                ];
            } else {
                $existingAssistants[] = [
                    'name' => $assistantName,
                    'type' => 'Gatekeeper Assistant',
                    'is_public' => $dbAssistants[$assistantName]->is_public
                ];
            }
        }

        // Display results
        $this->newLine();
        $this->info('=== Assistant Check Results ===');
        
        if (!empty($existingAssistants)) {
            $this->info("\nExisting Assistants:");
            foreach ($existingAssistants as $assistant) {
                $this->line("✓ {$assistant['name']} ({$assistant['type']})");
                if (isset($assistant['group'])) {
                    $this->line("   Group: {$assistant['group']}");
                }
                if (isset($assistant['user'])) {
                    $this->line("   User: {$assistant['user']}");
                }
                $this->line("   Public: " . ($assistant['is_public'] ? 'Yes' : 'No'));
            }
        }

        if (!empty($missingAssistants)) {
            $this->error("\nMissing Assistants:");
            foreach ($missingAssistants as $assistant) {
                $this->line("✗ {$assistant['name']} ({$assistant['type']})");
                if (isset($assistant['group'])) {
                    $this->line("   Group: {$assistant['group']}");
                }
                if (isset($assistant['user'])) {
                    $this->line("   User: {$assistant['user']}");
                }
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("Total assistants in database: " . $dbAssistants->count());
        $this->info("Required assistants found: " . count($existingAssistants));
        $this->info("Missing assistants: " . count($missingAssistants));

        if (!empty($missingAssistants)) {
            return 1; // Return error code if there are missing assistants
        }

        return 0;
    }
} 