<?php

namespace App\Console\Commands;

use App\Services\OpenAIAssistant;
use App\Models\AssistantFunction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\Tool;

class LoadFunctions extends Command
{
    // The name and signature of the console command.
    protected $signature = 'xx:load_functions';

    // The console command description.
    protected $description = 'Loads all the functions from text.';

    // Create a new command instance.
    public function __construct()
    {
        parent::__construct();
    }

    // Execute the console command.
    public function handle()
    {




// Array of tools to add
        $toolsToAdd = [
            [
                'name' => 'get_new_ticket',
                'description' => 'Get an unparsed ticket.',
                'strict' => false,
                'parameters' => [
                ],
            ],

                [
                    'name' => 'create_ticket',
                    'description' => 'Create a new ticket entry from a JSON array',
                    'strict' => true,
                    'parameters' => [

                        [
                            'name' => 'order_number',
                            'type' => 'string',
                            'description' => 'Order number related to the ticket',
                            'required' => false,
                        ],
                        [
                            'name' => 'start_date',
                            'type' => 'string',
                            'description' => 'Start date of the ticket (format: YYYY-MM-DD)',
                            'required' => false,
                        ],
                        [
                            'name' => 'complete_date',
                            'type' => 'string',
                            'description' => 'Completion date of the ticket (format: YYYY-MM-DD)',
                            'required' => false,
                        ],
                        [
                            'name' => 'status',
                            'type' => 'string',
                            'description' => 'Current status of the ticket (e.g., In Progress, Completed)',
                            'required' => false,
                        ],
                        [
                            'name' => 'account_number',
                            'type' => 'string',
                            'description' => 'Customer account number',
                            'required' => false,
                        ],
                        [
                            'name' => 'customer_name',
                            'type' => 'string',
                            'description' => 'Name of the customer',
                            'required' => false,
                        ],
                        [
                            'name' => 'service_address',
                            'type' => 'string',
                            'description' => 'Service address related to the ticket',
                            'required' => false,
                        ],
                        [
                            'name' => 'contact_number',
                            'type' => 'string',
                            'description' => 'Primary contact number for the customer',
                            'required' => false,
                        ],
                        [
                            'name' => 'email',
                            'type' => 'string',
                            'description' => 'Email address associated with the customer',
                            'required' => false,
                        ],
                        [
                            'name' => 'product_type',
                            'type' => 'string',
                            'description' => 'Type of product/service (e.g., Fiber Internet)',
                            'required' => false,
                        ],
                        [
                            'name' => 'service_type',
                            'type' => 'string',
                            'description' => 'Service type (e.g., CLEC, Residential)',
                            'required' => false,
                        ],
                        [
                            'name' => 'connect_date',
                            'type' => 'string',
                            'description' => 'Connection date for the service (format: YYYY-MM-DD)',
                            'required' => false,
                        ],
                        [
                            'name' => 'disconnect_date',
                            'type' => 'string',
                            'description' => 'Disconnection date for the service (format: YYYY-MM-DD)',
                            'required' => false,
                        ],
                        [
                            'name' => 'equipment',
                            'type' => 'string',
                            'description' => 'Details of equipment used or modified',
                            'required' => false,
                        ],
                        [
                            'name' => 'technician_name',
                            'type' => 'string',
                            'description' => 'Name of the technician handling the ticket',
                            'required' => false,
                        ],
                        [
                            'name' => 'install_notes',
                            'type' => 'string',
                            'description' => 'Notes about installation or maintenance',
                            'required' => false,
                        ],
                        [
                            'name' => 'drop_type',
                            'type' => 'string',
                            'description' => 'Type of physical connection drop (e.g., Aerial Drop)',
                            'required' => false,
                        ],
                        [
                            'name' => 'issues_reported',
                            'type' => 'string',
                            'description' => 'Issues reported by the customer or technician',
                            'required' => false,
                        ],
                        [
                            'name' => 'resolution_notes',
                            'type' => 'string',
                            'description' => 'Notes about how the issue was resolved',
                            'required' => false,
                        ],
                        [
                            'name' => 'billing_amount',
                            'type' => 'number',
                            'description' => 'Total billing amount related to the ticket',
                            'required' => false,
                        ],
                        [
                            'name' => 'promotions',
                            'type' => 'string',
                            'description' => 'Promotions applied to the account',
                            'required' => false,
                        ],
                        [
                            'name' => 'monthly_charge',
                            'type' => 'number',
                            'description' => 'Monthly charge related to the service',
                            'required' => false,
                        ],
                        [
                            'name' => 'fractional_charge',
                            'type' => 'number',
                            'description' => 'Fractional charge related to mid-month changes',
                            'required' => false,
                        ],
                        [
                            'name' => 'prorated_charge',
                            'type' => 'number',
                            'description' => 'Prorated charge for changes within the billing cycle',
                            'required' => false,
                        ],
                        [
                            'name' => 'warnings',
                            'type' => 'string',
                            'description' => 'Warnings or errors logged during ticket processing',
                            'required' => false,
                        ],
                        [
                            'name' => 'comments',
                            'type' => 'string',
                            'description' => 'General comments about the ticket',
                            'required' => false,
                        ],
                        [
                            'name' => 'latitude',
                            'type' => 'number',
                            'description' => 'Latitude of the service location',
                            'required' => false,
                        ],
                        [
                            'name' => 'longitude',
                            'type' => 'number',
                            'description' => 'Longitude of the service location',
                            'required' => false,
                        ],
                        [
                            'name' => 'ssid',
                            'type' => 'string',
                            'description' => 'Wi-Fi SSID for the service',
                            'required' => false,
                        ],
                        [
                            'name' => 'password',
                            'type' => 'string',
                            'description' => 'Wi-Fi password for the service',
                            'required' => false,
                        ],
                    ],
                ]
            ,
            [
                'name' => 'upload_file',
                'description' => 'Upload a file to the server',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'fileContent',
                        'type' => 'string',
                        'description' => 'Base64 encoded content of the file',
                        'required' => true,
                    ],
                    [
                        'name' => 'destination',
                        'type' => 'string',
                        'description' => 'Destination path on the server',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'create_folder',
                'description' => 'Create a new folder on the server',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'path',
                        'type' => 'string',
                        'description' => 'The path where the folder will be created',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'delete_folder',
                'description' => 'Delete a folder from the server',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'path',
                        'type' => 'string',
                        'description' => 'The path of the folder to delete',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'list_directory_contents',
                'description' => 'List contents of a directory',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'path',
                        'type' => 'string',
                        'description' => 'The path of the directory to list',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'search_files',
                'description' => 'Search for files in a directory',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'query',
                        'type' => 'string',
                        'description' => 'Search query',
                        'required' => true,
                    ],
                    [
                        'name' => 'directory',
                        'type' => 'string',
                        'description' => 'Directory to search in',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'send_bulk_email',
                'description' => 'Send an email to multiple recipients',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'recipients',
                        'type' => 'array',
                        'description' => 'List of email addresses',
                        'required' => true,
                    ],
                    [
                        'name' => 'subject',
                        'type' => 'string',
                        'description' => 'Email subject',
                        'required' => true,
                    ],
                    [
                        'name' => 'body',
                        'type' => 'string',
                        'description' => 'Email body',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'send_notification',
                'description' => 'Send a notification to users',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'user_ids',
                        'type' => 'array',
                        'description' => 'List of user IDs to notify',
                        'required' => true,
                    ],
                    [
                        'name' => 'message',
                        'type' => 'string',
                        'description' => 'Notification message',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'manage_task',
                'description' => 'Manage tasks (create, update, delete)',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'action',
                        'type' => 'string',
                        'description' => 'Action to perform (create, update, delete)',
                        'required' => true,
                    ],
                    // Additional parameters based on action
                ],
            ],
            [
                'name' => 'search_tasks',
                'description' => 'Search tasks with optional filters',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'project_id',
                        'type' => 'integer',
                        'description' => 'Filter by project ID',
                        'required' => false,
                    ],
                    [
                        'name' => 'assigned_user_id',
                        'type' => 'integer',
                        'description' => 'Filter by assigned user ID',
                        'required' => false,
                    ],
                    [
                        'name' => 'query',
                        'type' => 'string',
                        'description' => 'Search term for task title',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'manage_project',
                'description' => 'Manage projects (create, update, delete)',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'action',
                        'type' => 'string',
                        'description' => 'Action to perform (create, update, delete)',
                        'required' => true,
                    ],
                    // Additional parameters based on action
                ],
            ],
            [
                'name' => 'manage_user',
                'description' => 'Manage users (create, update, delete)',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'action',
                        'type' => 'string',
                        'description' => 'Action to perform (create, update, delete)',
                        'required' => true,
                    ],
                    // Additional parameters based on action
                ],
            ],
            [
                'name' => 'manage_appointment',
                'description' => 'Manage appointments (create, update, delete)',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'action',
                        'type' => 'string',
                        'description' => 'Action to perform (create, update, delete)',
                        'required' => true,
                    ],
                    // Additional parameters based on action
                ],
            ],
            [
                'name' => 'search_appointments',
                'description' => 'Search appointments with optional filters',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'user_id',
                        'type' => 'integer',
                        'description' => 'Filter by user ID',
                        'required' => false,
                    ],
                    [
                        'name' => 'query',
                        'type' => 'string',
                        'description' => 'Search term for appointment title',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'invite_users_to_appointment',
                'description' => 'Invite users to an appointment',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'appointment_id',
                        'type' => 'integer',
                        'description' => 'ID of the appointment',
                        'required' => true,
                    ],
                    [
                        'name' => 'user_ids',
                        'type' => 'array',
                        'description' => 'List of user IDs to invite',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'list_user_appointments',
                'description' => 'List all appointments for a user',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'user_id',
                        'type' => 'integer',
                        'description' => 'User ID to list appointments for',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'log_event',
                'description' => 'Log an event in the system',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'event_type',
                        'type' => 'string',
                        'description' => 'Type of the event',
                        'required' => true,
                    ],
                    [
                        'name' => 'details',
                        'type' => 'string',
                        'description' => 'Event details',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'get_system_logs',
                'description' => 'Retrieve system logs',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'log_type',
                        'type' => 'string',
                        'description' => 'Type of logs to retrieve',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'set_permissions',
                'description' => 'Set permissions for a user or role',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'target_id',
                        'type' => 'integer',
                        'description' => 'User or role ID',
                        'required' => true,
                    ],
                    [
                        'name' => 'permissions',
                        'type' => 'array',
                        'description' => 'List of permissions to set',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'get_permissions',
                'description' => 'Get permissions for a user or role',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'target_id',
                        'type' => 'integer',
                        'description' => 'User or role ID',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'export_data',
                'description' => 'Export data from the system',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'data_type',
                        'type' => 'string',
                        'description' => 'Type of data to export',
                        'required' => true,
                    ],
                    [
                        'name' => 'format',
                        'type' => 'string',
                        'description' => 'Export format (e.g., CSV, JSON)',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'import_data',
                'description' => 'Import data into the system',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'data_type',
                        'type' => 'string',
                        'description' => 'Type of data to import',
                        'required' => true,
                    ],
                    [
                        'name' => 'fileContent',
                        'type' => 'string',
                        'description' => 'Base64 encoded content of the import file',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'report_error',
                'description' => 'Report an error or issue',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'error_message',
                        'type' => 'string',
                        'description' => 'Error message',
                        'required' => true,
                    ],
                    [
                        'name' => 'context',
                        'type' => 'string',
                        'description' => 'Context of the error',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'respond_to_user',
                'description' => 'Send a response to a user query',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'user_id',
                        'type' => 'integer',
                        'description' => 'ID of the user',
                        'required' => true,
                    ],
                    [
                        'name' => 'message',
                        'type' => 'string',
                        'description' => 'Response message',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'user_auth',
                'description' => 'Authenticate a user',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'username',
                        'type' => 'string',
                        'description' => 'Username',
                        'required' => true,
                    ],
                    [
                        'name' => 'password',
                        'type' => 'string',
                        'description' => 'Password',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'diagnostic_tool',
                'description' => 'Run system diagnostics',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'test_type',
                        'type' => 'string',
                        'description' => 'Type of diagnostic test',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'calendar_tool',
                'description' => 'Interact with the calendar system',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'action',
                        'type' => 'string',
                        'description' => 'Action to perform (view, add, update, delete)',
                        'required' => true,
                    ],
                    // Additional parameters based on action
                ],
            ],
            [
                'name' => 'knowledge_base',
                'description' => 'Access the knowledge base',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'query',
                        'type' => 'string',
                        'description' => 'Search query',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'data_fetch',
                'description' => 'Fetch data from external sources',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'source',
                        'type' => 'string',
                        'description' => 'Data source identifier',
                        'required' => true,
                    ],
                    [
                        'name' => 'parameters',
                        'type' => 'array',
                        'description' => 'Parameters for the data fetch',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'prompt_enhancer',
                'description' => 'Enhance a prompt for better results',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'prompt',
                        'type' => 'string',
                        'description' => 'Original prompt',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'customer_search',
                'description' => 'Search for customer records',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'customer_id',
                        'type' => 'integer',
                        'description' => 'Customer ID',
                        'required' => false,
                    ],
                    [
                        'name' => 'name',
                        'type' => 'string',
                        'description' => 'Customer name',
                        'required' => false,
                    ],
                    // Additional search parameters
                ],
            ],
            [
                'name' => 'customer_verify_cpni',
                'description' => 'Verify customer CPNI',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'customer_id',
                        'type' => 'integer',
                        'description' => 'Customer ID',
                        'required' => true,
                    ],
                    [
                        'name' => 'verification_data',
                        'type' => 'array',
                        'description' => 'Data required for verification',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'customer_question',
                'description' => 'Answer a customer question',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'question',
                        'type' => 'string',
                        'description' => 'Customer question',
                        'required' => true,
                    ],
                    [
                        'name' => 'customer_id',
                        'type' => 'integer',
                        'description' => 'Customer ID',
                        'required' => false,
                    ],
                ],
            ],
        ];

// Loop through each tool and add it to the database
        foreach ($toolsToAdd as $toolData) {
            $tool = Tool::updateOrCreate(
                ['name' => $toolData['name']],
                [
                    'description' => $toolData['description'],
                    'strict' => $toolData['strict'],
                ]
            );

            // Remove existing parameters to avoid duplicates
            $tool->parameters()->delete();

            // Add parameters
            foreach ($toolData['parameters'] as $paramData) {
                $tool->parameters()->create([
                    'name' => $paramData['name'],
                    'type' => $paramData['type'],
                    'description' => $paramData['description'] ?? null,
                    'required' => $paramData['required'],
                ]);
            }

            echo "Tool '{$tool->name}' has been added/updated successfully.\n";
        }

        echo "All missing tools have been added to the database.\n";


        exit;



        foreach ($this->tools() as $tool){
            echo "Tool: {$tool['name']} : ";
            echo "{$tool['description']}\n";


        }


        foreach ($this->tools() as $toolData) {
            // Insert tool into the tools table
            $tool = Tool::create([
                'name' => $toolData['name'],
                'description' => $toolData['description'],
                'strict' => $toolData['strict'],
            ]);

            // Insert parameters associated with the tool
            foreach ($toolData['parameters']['properties'] as $paramName => $paramData) {

                $requiredParams = $toolData['parameters']['required'] ?? [];

                $tool->parameters()->create([
                    'name' => $paramName,
                    'type' => $paramData['type'],
                    'description' => $paramData['description'] ?? null,
                    'required' => in_array($paramName, $requiredParams),
                ]);
            }
        }



    }



    /**
     * Recursively sort an associative array by its keys.
     *
     * @param array &$array The array to sort.
     * @return void
     */
    private function tools()
    {

        $tools = [
            [
                "name" => "download_file",
                "description" => "Download a file from the server.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "filePath" => [
                            "type" => "string",
                            "description" => "The path of the file to download."
                        ]
                    ],
                    "required" => ["filePath"]
                ]
            ],
            [
                "name" => "upload_file",
                "description" => "Upload a file to the server.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "filePath" => [
                            "type" => "string",
                            "description" => "The path where the file will be stored."
                        ],
                        "fileContent" => [
                            "type" => "string",
                            "description" => "The content of the file to upload."
                        ]
                    ],
                    "required" => ["filePath", "fileContent"]
                ]
            ],
            [
                "name" => "delete_file",
                "description" => "Delete a specified file from the server.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "filePath" => [
                            "type" => "string",
                            "description" => "The path of the file to delete."
                        ]
                    ],
                    "required" => ["filePath"]
                ]
            ],
            [
                "name" => "move_file",
                "description" => "Move or rename a file.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "sourcePath" => [
                            "type" => "string",
                            "description" => "The current path of the file."
                        ],
                        "destinationPath" => [
                            "type" => "string",
                            "description" => "The new path of the file."
                        ]
                    ],
                    "required" => ["sourcePath", "destinationPath"]
                ]
            ],
            [
                "name" => "copy_file",
                "description" => "Copy a file to a new location.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "sourcePath" => [
                            "type" => "string",
                            "description" => "The path of the file to copy."
                        ],
                        "destinationPath" => [
                            "type" => "string",
                            "description" => "The path where the file will be copied."
                        ]
                    ],
                    "required" => ["sourcePath", "destinationPath"]
                ]
            ],
            [
                "name" => "read_file",
                "description" => "Read and return the content of a specified file.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "filePath" => [
                            "type" => "string",
                            "description" => "The path of the file to read."
                        ]
                    ],
                    "required" => ["filePath"]
                ]
            ],
            [
                "name" => "append_text",
                "description" => "Append text to an existing file without overwriting its content.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "filePath" => [
                            "type" => "string",
                            "description" => "The path of the file to append text to."
                        ],
                        "content" => [
                            "type" => "string",
                            "description" => "The text content to append."
                        ]
                    ],
                    "required" => ["filePath", "content"]
                ]
            ],
            [
                "name" => "put_text",
                "description" => "Save text content to a specified file, replacing existing content.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "filePath" => [
                            "type" => "string",
                            "description" => "The path of the file to save content to."
                        ],
                        "content" => [
                            "type" => "string",
                            "description" => "The content to save to the file."
                        ]
                    ],
                    "required" => ["filePath", "content"]
                ]
            ],
            [
                "name" => "create_folder",
                "description" => "Create a new directory on the server.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "directoryPath" => [
                            "type" => "string",
                            "description" => "The path of the new directory."
                        ]
                    ],
                    "required" => ["directoryPath"]
                ]
            ],
            [
                "name" => "delete_folder",
                "description" => "Delete a specified directory from the server.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "directoryPath" => [
                            "type" => "string",
                            "description" => "The path of the directory to delete."
                        ]
                    ],
                    "required" => ["directoryPath"]
                ]
            ],
            [
                "name" => "list_directory_contents",
                "description" => "List the contents of a directory, including files and/or folders.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "directory" => [
                            "type" => "string",
                            "description" => "The directory to list contents from."
                        ],
                        "include_files" => [
                            "type" => "boolean",
                            "description" => "Whether to include files in the listing.",
                            "default" => true
                        ],
                        "include_folders" => [
                            "type" => "boolean",
                            "description" => "Whether to include folders in the listing.",
                            "default" => true
                        ]
                    ],
                    "required" => ["directory"]
                ]
            ],
            [
                "name" => "search_files",
                "description" => "Search for files matching a pattern.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "directory" => [
                            "type" => "string",
                            "description" => "The directory to search in."
                        ],
                        "pattern" => [
                            "type" => "string",
                            "description" => "The search pattern (e.g., '*.txt')."
                        ]
                    ],
                    "required" => ["directory", "pattern"]
                ]
            ],
            [
                "name" => "send_email",
                "description" => "Send an email to a specified address.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "to" => [
                            "type" => "string",
                            "description" => "The email address to send to."
                        ],
                        "subject" => [
                            "type" => "string",
                            "description" => "The subject of the email."
                        ],
                        "body" => [
                            "type" => "string",
                            "description" => "The body of the email."
                        ]
                    ],
                    "required" => ["to", "subject", "body"]
                ]
            ],
            [
                "name" => "send_bulk_email",
                "description" => "Send an email to multiple recipients.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "to" => [
                            "type" => "array",
                            "description" => "Email addresses to send to.",
                            "items" => [
                                "type" => "string"
                            ]
                        ],
                        "subject" => [
                            "type" => "string",
                            "description" => "The subject of the email."
                        ],
                        "body" => [
                            "type" => "string",
                            "description" => "The body of the email."
                        ]
                    ],
                    "required" => ["to", "subject", "body"]
                ]
            ],
            [
                "name" => "send_sms",
                "description" => "Send an SMS message to a specified phone number.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "to" => [
                            "type" => "string",
                            "description" => "Phone number to send message to."
                        ],
                        "body" => [
                            "type" => "string",
                            "description" => "The body content of the SMS message."
                        ]
                    ],
                    "required" => ["to", "body"]
                ]
            ],
            [
                "name" => "send_notification",
                "description" => "Send a system notification to a user.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "user_id" => [
                            "type" => "integer",
                            "description" => "The ID of the user to notify."
                        ],
                        "message" => [
                            "type" => "string",
                            "description" => "The notification message."
                        ]
                    ],
                    "required" => ["user_id", "message"]
                ]
            ],
            [
                "name" => "generate_image",
                "description" => "Generates a DALL·E image based on a text prompt and stores it on the server.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "prompt" => [
                            "type" => "string",
                            "description" => "The text prompt describing the image to generate."
                        ],
                        "size" => [
                            "type" => "string",
                            "description" => "The size of the image to generate.",
                            "enum" => ["256x256", "512x512", "1024x1024"]
                        ],
                        "target_path" => [
                            "type" => "string",
                            "description" => "The full path including the file name and extension where the image will be stored on the server."
                        ]
                    ],
                    "required" => ["prompt", "size", "target_path"]
                ]
            ],
            [
                "name" => "update_display",
                "description" => "Updates the dynamic HTML display with new content.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "display_name" => [
                            "type" => "string",
                            "description" => "The title or identifier for the display."
                        ],
                        "html_content" => [
                            "type" => "string",
                            "description" => "The HTML content that will be rendered on the display."
                        ],
                        "timestamp" => [
                            "type" => "string",
                            "description" => "The time when the content was updated in date-time format."
                        ],
                        "status" => [
                            "type" => "string",
                            "description" => "The status of the content.",
                            "enum" => ["active", "inactive", "failed"]
                        ]
                    ],
                    "required" => ["display_name", "html_content"]
                ]
            ],
            [
                "name" => "manage_task",
                "description" => "Create, update, or delete a task.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "action" => [
                            "type" => "string",
                            "description" => "The action to perform on the task.",
                            "enum" => ["create", "update", "delete"]
                        ],
                        "id" => [
                            "type" => "integer",
                            "description" => "The ID of the task (required for update and delete actions)."
                        ],
                        "title" => [
                            "type" => "string",
                            "description" => "The title of the task."
                        ],
                        "description" => [
                            "type" => "string",
                            "description" => "A detailed description of the task."
                        ],
                        "project_id" => [
                            "type" => "integer",
                            "description" => "The ID of the project the task belongs to."
                        ],
                        "order" => [
                            "type" => "integer",
                            "description" => "The position of the task within the project."
                        ]
                    ],
                    "required" => ["action"]
                ]
            ],
            [
                "name" => "list_tasks",
                "description" => "List tasks with optional filters.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "project_id" => [
                            "type" => "integer",
                            "description" => "Filter tasks by project ID."
                        ],
                        "assigned_user_id" => [
                            "type" => "integer",
                            "description" => "Filter tasks assigned to a specific user."
                        ],
                        "creator_user_id" => [
                            "type" => "integer",
                            "description" => "Filter tasks created by a specific user."
                        ],
                        "page" => [
                            "type" => "integer",
                            "description" => "The page number for pagination."
                        ],
                        "per_page" => [
                            "type" => "integer",
                            "description" => "Number of tasks per page for pagination."
                        ]
                    ]
                ]
            ],
            [
                "name" => "assign_user_to_task",
                "description" => "Assign a user to a task.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "task_id" => [
                            "type" => "integer",
                            "description" => "The ID of the task."
                        ],
                        "user_id" => [
                            "type" => "integer",
                            "description" => "The ID of the user to assign."
                        ]
                    ],
                    "required" => ["task_id", "user_id"]
                ]
            ],
            [
                "name" => "unassign_user_from_task",
                "description" => "Remove a user assignment from a task.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "task_id" => [
                            "type" => "integer",
                            "description" => "The ID of the task."
                        ],
                        "user_id" => [
                            "type" => "integer",
                            "description" => "The ID of the user to unassign."
                        ]
                    ],
                    "required" => ["task_id", "user_id"]
                ]
            ],
            [
                "name" => "reorder_tasks_in_project",
                "description" => "Change the order of tasks within a project.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "project_id" => [
                            "type" => "integer",
                            "description" => "The ID of the project."
                        ],
                        "task_orders" => [
                            "type" => "array",
                            "description" => "An array of task IDs in the desired order.",
                            "items" => [
                                "type" => "integer"
                            ]
                        ]
                    ],
                    "required" => ["project_id", "task_orders"]
                ]
            ],
            [
                "name" => "search_tasks",
                "description" => "Search for tasks based on criteria.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "query" => [
                            "type" => "string",
                            "description" => "The search query."
                        ],
                        "project_id" => [
                            "type" => "integer",
                            "description" => "Filter by project ID."
                        ],
                        "assigned_user_id" => [
                            "type" => "integer",
                            "description" => "Filter by assigned user."
                        ],
                        "page" => [
                            "type" => "integer",
                            "description" => "The page number for pagination."
                        ],
                        "per_page" => [
                            "type" => "integer",
                            "description" => "Number of tasks per page for pagination."
                        ]
                    ],
                    "required" => ["query"]
                ]
            ],
            [
                "name" => "manage_project",
                "description" => "Create, update, or delete a project.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "action" => [
                            "type" => "string",
                            "description" => "The action to perform on the project.",
                            "enum" => ["create", "update", "delete"]
                        ],
                        "id" => [
                            "type" => "integer",
                            "description" => "The ID of the project (required for update and delete actions)."
                        ],
                        "name" => [
                            "type" => "string",
                            "description" => "The name of the project."
                        ],
                        "description" => [
                            "type" => "string",
                            "description" => "A detailed description of the project."
                        ]
                    ],
                    "required" => ["action"]
                ]
            ],
            [
                "name" => "list_projects",
                "description" => "List all projects with optional pagination.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "page" => [
                            "type" => "integer",
                            "description" => "The page number for pagination."
                        ],
                        "per_page" => [
                            "type" => "integer",
                            "description" => "Number of projects per page for pagination."
                        ]
                    ]
                ]
            ],
            [
                "name" => "manage_user",
                "description" => "Create, update, or delete a user account.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "action" => [
                            "type" => "string",
                            "description" => "The action to perform on the user account.",
                            "enum" => ["create", "update", "delete"]
                        ],
                        "id" => [
                            "type" => "integer",
                            "description" => "The ID of the user (required for update and delete actions)."
                        ],
                        "username" => [
                            "type" => "string",
                            "description" => "The username for the user."
                        ],
                        "email" => [
                            "type" => "string",
                            "description" => "The email address of the user."
                        ],
                        "password" => [
                            "type" => "string",
                            "description" => "The password for the user account."
                        ],
                        "role" => [
                            "type" => "string",
                            "description" => "The role of the user (e.g., 'admin', 'member')."
                        ]
                    ],
                    "required" => ["action"]
                ]
            ],
            [
                "name" => "list_users",
                "description" => "List users with optional filters.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "role" => [
                            "type" => "string",
                            "description" => "Filter users by role (e.g., 'admin', 'member')."
                        ],
                        "status" => [
                            "type" => "string",
                            "description" => "Filter users by status (e.g., 'active', 'inactive')."
                        ],
                        "search" => [
                            "type" => "string",
                            "description" => "Search term to filter users by name or email."
                        ],
                        "page" => [
                            "type" => "integer",
                            "description" => "The page number for pagination."
                        ],
                        "per_page" => [
                            "type" => "integer",
                            "description" => "Number of users per page for pagination."
                        ]
                    ]
                ]
            ],
            [
                "name" => "manage_appointment",
                "description" => "Create, view, update, or delete an appointment.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "action" => [
                            "type" => "string",
                            "description" => "The action to perform on the appointment.",
                            "enum" => ["create", "view", "update", "delete"]
                        ],
                        "id" => [
                            "type" => "integer",
                            "description" => "The ID of the appointment (required for view, update, delete)."
                        ],
                        "title" => [
                            "type" => "string",
                            "description" => "The title of the appointment."
                        ],
                        "description" => [
                            "type" => "string",
                            "description" => "A detailed description of the appointment."
                        ],
                        "start_time" => [
                            "type" => "string",
                            "description" => "The start time of the appointment in ISO 8601 format."
                        ],
                        "end_time" => [
                            "type" => "string",
                            "description" => "The end time of the appointment in ISO 8601 format."
                        ],
                        "all_day" => [
                            "type" => "boolean",
                            "description" => "Indicates if the appointment is an all-day event."
                        ]
                    ],
                    "required" => ["action"]
                ]
            ],
            [
                "name" => "list_appointments",
                "description" => "Retrieve a list of all appointments.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "page" => [
                            "type" => "integer",
                            "description" => "The page number for pagination."
                        ],
                        "per_page" => [
                            "type" => "integer",
                            "description" => "Number of appointments per page for pagination."
                        ]
                    ]
                ]
            ],
            [
                "name" => "search_appointments",
                "description" => "Search for appointments based on criteria.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "query" => [
                            "type" => "string",
                            "description" => "The search query."
                        ],
                        "start_date" => [
                            "type" => "string",
                            "description" => "Filter appointments starting from this date in ISO 8601 format."
                        ],
                        "end_date" => [
                            "type" => "string",
                            "description" => "Filter appointments ending by this date in ISO 8601 format."
                        ],
                        "page" => [
                            "type" => "integer",
                            "description" => "The page number for pagination."
                        ],
                        "per_page" => [
                            "type" => "integer",
                            "description" => "Number of appointments per page for pagination."
                        ]
                    ],
                    "required" => ["query"]
                ]
            ],
            [
                "name" => "invite_users_to_appointment",
                "description" => "Send invitations to users for an appointment.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "appointment_id" => [
                            "type" => "integer",
                            "description" => "The ID of the appointment."
                        ],
                        "user_ids" => [
                            "type" => "array",
                            "description" => "An array of user IDs to invite.",
                            "items" => [
                                "type" => "integer"
                            ]
                        ]
                    ],
                    "required" => ["appointment_id", "user_ids"]
                ]
            ],
            [
                "name" => "list_user_appointments",
                "description" => "List appointments for a specific user.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "user_id" => [
                            "type" => "integer",
                            "description" => "The ID of the user."
                        ],
                        "page" => [
                            "type" => "integer",
                            "description" => "The page number for pagination."
                        ],
                        "per_page" => [
                            "type" => "integer",
                            "description" => "Number of appointments per page for pagination."
                        ]
                    ],
                    "required" => ["user_id"]
                ]
            ],
            [
                "name" => "log_event",
                "description" => "Log an event or action taken by the AI.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "event_type" => [
                            "type" => "string",
                            "description" => "The type of event."
                        ],
                        "details" => [
                            "type" => "string",
                            "description" => "Additional details about the event."
                        ]
                    ],
                    "required" => ["event_type"]
                ]
            ],
            [
                "name" => "get_system_logs",
                "description" => "Retrieve system logs for monitoring.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "start_time" => [
                            "type" => "string",
                            "description" => "Start time in ISO 8601 format."
                        ],
                        "end_time" => [
                            "type" => "string",
                            "description" => "End time in ISO 8601 format."
                        ],
                        "level" => [
                            "type" => "string",
                            "description" => "Log level (e.g., 'error', 'warning', 'info')."
                        ],
                        "page" => [
                            "type" => "integer",
                            "description" => "The page number for pagination."
                        ],
                        "per_page" => [
                            "type" => "integer",
                            "description" => "Number of log entries per page for pagination."
                        ]
                    ]
                ]
            ],
            [
                "name" => "set_permissions",
                "description" => "Change permissions of a file or directory.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "path" => [
                            "type" => "string",
                            "description" => "The path of the file or directory."
                        ],
                        "permissions" => [
                            "type" => "string",
                            "description" => "The permissions to set (e.g., '755')."
                        ]
                    ],
                    "required" => ["path", "permissions"]
                ]
            ],
            [
                "name" => "get_permissions",
                "description" => "Get the current permissions of a file or directory.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "path" => [
                            "type" => "string",
                            "description" => "The path of the file or directory."
                        ]
                    ],
                    "required" => ["path"]
                ]
            ],
            [
                "name" => "export_data",
                "description" => "Export data in a specified format.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "data_type" => [
                            "type" => "string",
                            "description" => "The type of data to export (e.g., 'tasks', 'projects')."
                        ],
                        "format" => [
                            "type" => "string",
                            "description" => "The export format ('csv', 'json').",
                            "enum" => ["csv", "json"]
                        ],
                        "filters" => [
                            "type" => "object",
                            "description" => "Filters to apply to the data.",
                            "additionalProperties" => true
                        ]
                    ],
                    "required" => ["data_type", "format"]
                ]
            ],
            [
                "name" => "import_data",
                "description" => "Import data from a file.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "data_type" => [
                            "type" => "string",
                            "description" => "The type of data to import."
                        ],
                        "filePath" => [
                            "type" => "string",
                            "description" => "The path to the data file."
                        ]
                    ],
                    "required" => ["data_type", "filePath"]
                ]
            ],
            [
                "name" => "report_error",
                "description" => "Report an error with details.",
                "strict" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "error_code" => [
                            "type" => "string",
                            "description" => "The error code."
                        ],
                        "message" => [
                            "type" => "string",
                            "description" => "Error message."
                        ],
                        "details" => [
                            "type" => "string",
                            "description" => "Additional error details."
                        ]
                    ],
                    "required" => ["error_code", "message"]
                ]
            ]
        ];


        // Use json_encode to convert the array to JSON format
        // $json_output = json_encode($tools, JSON_PRETTY_PRINT);
        // echo $json_output;

    return $tools;



    }
}
