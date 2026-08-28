<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tool;
use App\Services\ToolExecutor;
use App\Services\CodingExecutor;
use App\Services\Executors\RainbowDashboardTicketExecutor;
use App\Services\Executors\RainbowExecutor;
use App\Services\Executors\OpenAIImageExecutor;
use App\Services\Executors\WebsocketControlExecutor;
use App\Services\Executors\RichBotExecutor;
use App\Services\Executors\RainbowKnowledgeBaseExecutor;
use App\Services\Executors\SurveyExecutor;
use ReflectionClass;
use ReflectionMethod;

class CheckTools extends Command
{
    protected $signature = 'tools:check';
    protected $description = 'Check if all tools are properly implemented in executors and registered in database';

    public function handle()
    {
        $this->info('Starting tool implementation check...');

        // Initialize executors
        $executors = [
            new ToolExecutor(null),
            new CodingExecutor(null),
            new SurveyExecutor(null),
            new RainbowDashboardTicketExecutor(null),
            new RainbowExecutor(null),
            new OpenAIImageExecutor(null),
            new WebsocketControlExecutor(null),
            new RichBotExecutor(null),
            new RainbowKnowledgeBaseExecutor(),
        ];

        $missingImplementations = [];
        $implementedTools = [];
        $parameterMismatches = [];
        $missingDatabaseEntries = [];

        // First, get all tools from database
        $dbTools = Tool::all()->keyBy('name');
        $this->info("Found {$dbTools->count()} tools in database");

        // Check each executor
        foreach ($executors as $executor) {
            $executorClass = get_class($executor);
            $this->info("\nChecking {$executorClass}...");

            $reflection = new ReflectionClass($executorClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if ($method->class === $executorClass) {
                    $methodName = $method->getName();
                    $implementedTools[] = $methodName;

                    // Check if tool exists in database
                    if (!$dbTools->has($methodName)) {
                        $missingDatabaseEntries[] = $methodName;
                        continue;
                    }

                    // Check parameters
                    $dbTool = $dbTools->get($methodName);
                    $dbParams = $dbTool->parameters->keyBy('name');
                    $methodParams = $method->getParameters();

                    foreach ($methodParams as $param) {
                        $paramName = $param->getName();
                        if (!$dbParams->has($paramName)) {
                            $parameterMismatches[] = [
                                'tool' => $methodName,
                                'parameter' => $paramName,
                                'issue' => 'Parameter exists in implementation but not in database'
                            ];
                        }
                    }

                    foreach ($dbParams as $param) {
                        if (!$param->required) continue;
                        $found = false;
                        foreach ($methodParams as $methodParam) {
                            if ($methodParam->getName() === $param->name) {
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $parameterMismatches[] = [
                                'tool' => $methodName,
                                'parameter' => $param->name,
                                'issue' => 'Required parameter in database but not in implementation'
                            ];
                        }
                    }
                }
            }
        }

        // Check for missing implementations
        foreach ($dbTools as $tool) {
            if (!in_array($tool->name, $implementedTools)) {
                $missingImplementations[] = $tool->name;
            }
        }

        // Display results
        if (!empty($missingImplementations)) {
            $this->error("\nMissing implementations:");
            foreach ($missingImplementations as $tool) {
                $this->line("- {$tool}");
            }
        }

        if (!empty($parameterMismatches)) {
            $this->error("\nParameter mismatches:");
            foreach ($parameterMismatches as $mismatch) {
                $this->line("- {$mismatch['tool']}: {$mismatch['parameter']} - {$mismatch['issue']}");
            }
        }

        if (!empty($missingDatabaseEntries)) {
            $this->error("\nTools implemented but not in database:");
            foreach ($missingDatabaseEntries as $tool) {
                $this->line("- {$tool}");
            }
        }

        if (empty($missingImplementations) && empty($parameterMismatches) && empty($missingDatabaseEntries)) {
            $this->info("\nAll tools are properly implemented and registered!");
        }
    }

    protected function checkTools()
    {
        $tools = $this->getTools();
        $missingTools = [];
        $invalidTools = [];

        foreach ($tools as $tool) {
            $toolName = $tool['name'];
            $toolExists = Tool::where('name', $toolName)->exists();

            if (!$toolExists) {
                $missingTools[] = $toolName;
                continue;
            }

            $dbTool = Tool::where('name', $toolName)->first();
            $parameters = $dbTool->parameters;

            // Check if all required parameters are present
            foreach ($tool['parameters'] as $param) {
                if ($param['required'] && !in_array($param['name'], array_column($parameters, 'name'))) {
                    $invalidTools[] = [
                        'name' => $toolName,
                        'issue' => "Missing required parameter: {$param['name']}"
                    ];
                }
            }

            // Check if all parameters have correct types
            foreach ($parameters as $param) {
                $toolParam = collect($tool['parameters'])->firstWhere('name', $param['name']);
                if ($toolParam && $toolParam['type'] !== $param['type']) {
                    $invalidTools[] = [
                        'name' => $toolName,
                        'issue' => "Parameter {$param['name']} has incorrect type. Expected {$toolParam['type']}, got {$param['type']}"
                    ];
                }
            }
        }

        if (!empty($missingTools)) {
            $this->error('Missing tools:');
            foreach ($missingTools as $tool) {
                $this->line("- {$tool}");
            }
        }

        if (!empty($invalidTools)) {
            $this->error('Invalid tools:');
            foreach ($invalidTools as $tool) {
                $this->line("- {$tool['name']}: {$tool['issue']}");
            }
        }

        if (empty($missingTools) && empty($invalidTools)) {
            $this->info('All tools are valid and up to date.');
        }

        return empty($missingTools) && empty($invalidTools);
    }

    protected function getTools()
    {
        return [
            // ... existing code ...

            // Survey Tools
            [
                'name' => 'survey_list',
                'description' => 'List all surveys for the authenticated user',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'status',
                        'type' => 'string',
                        'description' => 'Filter surveys by status (all, draft, active, archived)',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'survey_get',
                'description' => 'Get a specific survey\'s details',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'survey_id',
                        'type' => 'integer',
                        'description' => 'ID of the survey to retrieve',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'survey_question_create',
                'description' => 'Add a question to a survey',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'survey_id',
                        'type' => 'integer',
                        'description' => 'ID of the survey to add the question to',
                        'required' => true,
                    ],
                    [
                        'name' => 'question_text',
                        'type' => 'string',
                        'description' => 'The text of the question',
                        'required' => true,
                    ],
                    [
                        'name' => 'question_type',
                        'type' => 'string',
                        'description' => 'Type of question (text, paragraph, single_choice, multiple_choice, rating, date)',
                        'required' => true,
                    ],
                    [
                        'name' => 'options',
                        'type' => 'string',
                        'description' => 'Options for choice questions (JSON or CSV string)',
                        'required' => false,
                    ],
                    [
                        'name' => 'required',
                        'type' => 'boolean',
                        'description' => 'Whether the question is required',
                        'required' => false,
                    ],
                    [
                        'name' => 'order',
                        'type' => 'integer',
                        'description' => 'Order of the question in the survey',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'survey_question_update',
                'description' => 'Update a survey question',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'question_id',
                        'type' => 'integer',
                        'description' => 'ID of the question to update',
                        'required' => true,
                    ],
                    [
                        'name' => 'question_text',
                        'type' => 'string',
                        'description' => 'The text of the question',
                        'required' => true,
                    ],
                    [
                        'name' => 'question_type',
                        'type' => 'string',
                        'description' => 'Type of question (text, paragraph, single_choice, multiple_choice, rating, date)',
                        'required' => true,
                    ],
                    [
                        'name' => 'options',
                        'type' => 'string',
                        'description' => 'Options for choice questions (JSON or CSV string)',
                        'required' => false,
                    ],
                    [
                        'name' => 'required',
                        'type' => 'boolean',
                        'description' => 'Whether the question is required',
                        'required' => false,
                    ],
                    [
                        'name' => 'order',
                        'type' => 'integer',
                        'description' => 'Order of the question in the survey',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'survey_question_delete',
                'description' => 'Delete a survey question',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'question_id',
                        'type' => 'integer',
                        'description' => 'ID of the question to delete',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'survey_question_order_update',
                'description' => 'Update question order in a survey',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'survey_id',
                        'type' => 'integer',
                        'description' => 'ID of the survey',
                        'required' => true,
                    ],
                    [
                        'name' => 'questions',
                        'type' => 'string',
                        'description' => 'JSON string containing question IDs and their new order',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'survey_campaign_list',
                'description' => 'List campaigns for a survey',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'survey_id',
                        'type' => 'integer',
                        'description' => 'ID of the survey',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'survey_campaign_create',
                'description' => 'Create a new campaign',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'survey_id',
                        'type' => 'integer',
                        'description' => 'ID of the survey',
                        'required' => true,
                    ],
                    [
                        'name' => 'name',
                        'type' => 'string',
                        'description' => 'Name of the campaign',
                        'required' => true,
                    ],
                    [
                        'name' => 'description',
                        'type' => 'string',
                        'description' => 'Description of the campaign',
                        'required' => false,
                    ],
                    [
                        'name' => 'start_date',
                        'type' => 'date',
                        'description' => 'Start date of the campaign',
                        'required' => false,
                    ],
                    [
                        'name' => 'end_date',
                        'type' => 'date',
                        'description' => 'End date of the campaign',
                        'required' => false,
                    ],
                    [
                        'name' => 'status',
                        'type' => 'string',
                        'description' => 'Status of the campaign (pending, active, completed)',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'survey_campaign_get',
                'description' => 'Get campaign details',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'campaign_id',
                        'type' => 'integer',
                        'description' => 'ID of the campaign',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'survey_campaign_update',
                'description' => 'Update a campaign',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'campaign_id',
                        'type' => 'integer',
                        'description' => 'ID of the campaign',
                        'required' => true,
                    ],
                    [
                        'name' => 'name',
                        'type' => 'string',
                        'description' => 'Name of the campaign',
                        'required' => true,
                    ],
                    [
                        'name' => 'description',
                        'type' => 'string',
                        'description' => 'Description of the campaign',
                        'required' => false,
                    ],
                    [
                        'name' => 'start_date',
                        'type' => 'date',
                        'description' => 'Start date of the campaign',
                        'required' => false,
                    ],
                    [
                        'name' => 'end_date',
                        'type' => 'date',
                        'description' => 'End date of the campaign',
                        'required' => false,
                    ],
                    [
                        'name' => 'status',
                        'type' => 'string',
                        'description' => 'Status of the campaign (pending, active, completed)',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'survey_campaign_delete',
                'description' => 'Delete a campaign',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'campaign_id',
                        'type' => 'integer',
                        'description' => 'ID of the campaign',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'survey_campaign_contacts_get',
                'description' => 'Get campaign contacts',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'campaign_id',
                        'type' => 'integer',
                        'description' => 'ID of the campaign',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'survey_campaign_contacts_add',
                'description' => 'Add contacts to a campaign',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'campaign_id',
                        'type' => 'integer',
                        'description' => 'ID of the campaign',
                        'required' => true,
                    ],
                    [
                        'name' => 'contact_ids',
                        'type' => 'string',
                        'description' => 'Comma-separated list of contact IDs or JSON array',
                        'required' => false,
                    ],
                    [
                        'name' => 'email',
                        'type' => 'string',
                        'description' => 'Email address of contact to add',
                        'required' => false,
                    ],
                    [
                        'name' => 'phone',
                        'type' => 'string',
                        'description' => 'Phone number of contact to add',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'survey_campaign_contact_remove',
                'description' => 'Remove a contact from a campaign',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'campaign_id',
                        'type' => 'integer',
                        'description' => 'ID of the campaign',
                        'required' => true,
                    ],
                    [
                        'name' => 'contact_id',
                        'type' => 'integer',
                        'description' => 'ID of the contact to remove',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'survey_campaign_survey_get',
                'description' => 'Get the survey associated with a campaign',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'campaign_id',
                        'type' => 'integer',
                        'description' => 'ID of the campaign',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'survey_start',
                'description' => 'Start taking a survey for a campaign',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'campaign_id',
                        'type' => 'integer',
                        'description' => 'ID of the campaign',
                        'required' => true,
                    ],
                    [
                        'name' => 'contact_id',
                        'type' => 'integer',
                        'description' => 'ID of the contact taking the survey',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'survey_question_next',
                'description' => 'Get the next question in the survey',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'campaign_id',
                        'type' => 'integer',
                        'description' => 'ID of the campaign',
                        'required' => true,
                    ],
                    [
                        'name' => 'contact_id',
                        'type' => 'integer',
                        'description' => 'ID of the contact taking the survey',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'survey_answer_submit',
                'description' => 'Submit an answer to a survey question',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'campaign_id',
                        'type' => 'integer',
                        'description' => 'ID of the campaign',
                        'required' => true,
                    ],
                    [
                        'name' => 'contact_id',
                        'type' => 'integer',
                        'description' => 'ID of the contact taking the survey',
                        'required' => true,
                    ],
                    [
                        'name' => 'question_id',
                        'type' => 'integer',
                        'description' => 'ID of the question being answered',
                        'required' => true,
                    ],
                    [
                        'name' => 'answer',
                        'type' => 'string',
                        'description' => 'The answer to the question (text, JSON, or CSV)',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'survey_progress_get',
                'description' => 'Get the progress of a survey for a contact',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'campaign_id',
                        'type' => 'integer',
                        'description' => 'ID of the campaign',
                        'required' => true,
                    ],
                    [
                        'name' => 'contact_id',
                        'type' => 'integer',
                        'description' => 'ID of the contact taking the survey',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'survey_complete',
                'description' => 'Mark a survey as completed for a contact',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'campaign_id',
                        'type' => 'integer',
                        'description' => 'ID of the campaign',
                        'required' => true,
                    ],
                    [
                        'name' => 'contact_id',
                        'type' => 'integer',
                        'description' => 'ID of the contact taking the survey',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'survey_results_get',
                'description' => 'Get the results of a survey for a campaign',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'campaign_id',
                        'type' => 'integer',
                        'description' => 'ID of the campaign',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'rainbow_dashboard_get_all_tickets',
                'description' => 'Get all tickets from the helpdesk system',
                'strict' => true,
                'parameters' => [],
            ],
            [
                'name' => 'rainbow_dashboard_get_my_tickets',
                'description' => 'Get tickets for the currently logged in user',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'status',
                        'type' => 'string',
                        'description' => 'The status of tickets to retrieve (default: open)',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'rainbow_dashboard_get_user_tickets',
                'description' => 'Get tickets for a specific user',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'user_id',
                        'type' => 'integer',
                        'description' => 'ID of the user',
                        'required' => true,
                    ],
                    [
                        'name' => 'status',
                        'type' => 'string',
                        'description' => 'The status of tickets to retrieve (default: open)',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'websocket_control_get_transcription_status',
                'description' => 'Get the current status of call transcription',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'call_id',
                        'type' => 'string',
                        'description' => 'The ID of the call',
                        'required' => true
                    ],
                    [
                        'name' => 'transcription_id',
                        'type' => 'string',
                        'description' => 'The ID of the transcription',
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'websocket_control_add_monitor',
                'description' => 'Add a monitor to the call',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'call_id',
                        'type' => 'string',
                        'description' => 'The ID of the call to monitor',
                        'required' => true
                    ],
                    [
                        'name' => 'save_audio',
                        'type' => 'boolean',
                        'description' => 'Whether to save the audio',
                        'required' => false
                    ],
                    [
                        'name' => 'transcribe',
                        'type' => 'boolean',
                        'description' => 'Whether to transcribe the audio',
                        'required' => false
                    ]
                ]
            ],

            // ... existing code ...
        ];
    }
} 