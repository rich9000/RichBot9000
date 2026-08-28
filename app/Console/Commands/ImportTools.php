<?php

namespace App\Console\Commands;

use App\Models\Tool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\Executors\RainbowOutageExecutor;
use App\Services\Executors\WeatherExecutor;
use App\Services\Executors\ContactExecutor;
use App\Services\Executors\BaseToolsExecutor;

class ImportTools extends Command
{
    protected $signature = 'tools:import {--force : Force update existing tools} {--add-only : Only add new tools without modifying existing ones}';
    protected $description = 'Import tool functions into the database';

    public function handle()
    {
        $force = $this->option('force');
        $addOnly = $this->option('add-only');
        $createdTools = [];

        // First process executor tools
        $executors = [
            'outage' => new RainbowOutageExecutor(),
            'weather' => new WeatherExecutor(),
            'contact' => new ContactExecutor(),
            'base_tools' => new BaseToolsExecutor(),
        ];

        foreach($executors as $executor) {
            if(method_exists($executor, 'getMethodSchema')) {
                $tools = $executor->getMethodSchema();
                if (is_array($tools)) {
                    foreach($tools as $toolData) {
                        if (isset($toolData['name'])) {
                            $tool = $this->importTool($toolData, $force, $addOnly);
                            if ($tool) {
                                $createdTools[$tool->name] = $tool;
                            }
                        }
                    }

                    $groupData = array();
                    $className = (new \ReflectionClass($executor))->getShortName();
                    $groupData['name'] = $className;
                    $groupData['description'] = 'Methods from ' . $className;
                    $groupData['tools'] = $createdTools;

                    $this->importToolGroup($groupData, $createdTools, $force);
                }
            }
        }

        // Then process static tools
        $tools = $this->getTools();
        $toolGroups = $this->getToolGroups();

        foreach ($tools as $toolData) {
            if (isset($toolData['name'])) {
                $tool = $this->importTool($toolData, $force, $addOnly);
                if ($tool) {
                    $createdTools[$tool->name] = $tool;
                }
            }
        }

        // Create groups and associate tools
        foreach ($toolGroups as $groupData) {
            if (isset($groupData['name'])) {
                $this->importToolGroup($groupData, $createdTools, $force);
            }
        }

        $this->info('Tool and group import completed successfully.');
    }

    public function getTools()
    {
        return [
            // File System Tools
            [
                'name' => 'read_file',
                'description' => 'Read contents of a file',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'file_path',
                        'type' => 'string',
                        'description' => 'Path to the file to read',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'write_file',
                'description' => 'Write content to a file',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'file_path',
                        'type' => 'string',
                        'description' => 'Path where to write the file',
                        'required' => true,
                    ],
                    [
                        'name' => 'content',
                        'type' => 'string',
                        'description' => 'Content to write to the file',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'delete_file',
                'description' => 'Delete a file',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'file_path',
                        'type' => 'string',
                        'description' => 'Path to the file to delete',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'list_files',
                'description' => 'List files in a directory',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'directory',
                        'type' => 'string',
                        'description' => 'Directory path to list files from',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'create_directory',
                'description' => 'Create a new directory',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'directory_path',
                        'type' => 'string',
                        'description' => 'Path where to create the directory',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'delete_directory',
                'description' => 'Delete a directory',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'directory_path',
                        'type' => 'string',
                        'description' => 'Path to the directory to delete',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'move_file',
                'description' => 'Move a file from one location to another',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'source_path',
                        'type' => 'string',
                        'description' => 'Source file path',
                        'required' => true,
                    ],
                    [
                        'name' => 'destination_path',
                        'type' => 'string',
                        'description' => 'Destination file path',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'copy_file',
                'description' => 'Copy a file from one location to another',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'source_path',
                        'type' => 'string',
                        'description' => 'Source file path',
                        'required' => true,
                    ],
                    [
                        'name' => 'destination_path',
                        'type' => 'string',
                        'description' => 'Destination file path',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'search_files',
                'description' => 'Search for files matching a pattern',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'directory',
                        'type' => 'string',
                        'description' => 'Directory to search in',
                        'required' => true,
                    ],
                    [
                        'name' => 'pattern',
                        'type' => 'string',
                        'description' => 'Search pattern',
                        'required' => true,
                    ],
                ],
            ],
            // Weather Tools
            [
                'name' => 'weather_get_by_coordinates',
                'description' => 'Get weather data for specific coordinates',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'latitude',
                        'type' => 'number',
                        'description' => 'Latitude coordinate',
                        'required' => true,
                    ],
                    [
                        'name' => 'longitude',
                        'type' => 'number',
                        'description' => 'Longitude coordinate',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'weather_get_by_ip',
                'description' => 'Get weather data for an IP address',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'ip',
                        'type' => 'string',
                        'description' => 'IP address to get weather for',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'weather_alerts_get',
                'description' => 'Get active weather alerts',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'latitude',
                        'type' => 'number',
                        'description' => 'Latitude coordinate',
                        'required' => false,
                    ],
                    [
                        'name' => 'longitude',
                        'type' => 'number',
                        'description' => 'Longitude coordinate',
                        'required' => false,
                    ],
                    [
                        'name' => 'state',
                        'type' => 'string',
                        'description' => 'State code (e.g., CA)',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'weather_hourly_get',
                'description' => 'Get hourly forecast data',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'latitude',
                        'type' => 'number',
                        'description' => 'Latitude coordinate',
                        'required' => true,
                    ],
                    [
                        'name' => 'longitude',
                        'type' => 'number',
                        'description' => 'Longitude coordinate',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'weather_grid_data_get',
                'description' => 'Get raw forecast grid data',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'latitude',
                        'type' => 'number',
                        'description' => 'Latitude coordinate',
                        'required' => true,
                    ],
                    [
                        'name' => 'longitude',
                        'type' => 'number',
                        'description' => 'Longitude coordinate',
                        'required' => true,
                    ],
                ],
            ],
            // Communication Tools
            [
                'name' => 'send_email',
                'description' => 'Send an email',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'to',
                        'type' => 'string',
                        'description' => 'Recipient email address',
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
                'name' => 'send_sms',
                'description' => 'Send an SMS message',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'to',
                        'type' => 'string',
                        'description' => 'Recipient phone number',
                        'required' => true,
                    ],
                    [
                        'name' => 'message',
                        'type' => 'string',
                        'description' => 'SMS message content',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'send_bulk_email',
                'description' => 'Send bulk emails to multiple recipients',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'recipients',
                        'type' => 'array',
                        'description' => 'Array of recipient email addresses',
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
            // Task Management Tools
            [
                'name' => 'create_task',
                'description' => 'Create a new task',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'title',
                        'type' => 'string',
                        'description' => 'Task title',
                        'required' => true,
                    ],
                    [
                        'name' => 'description',
                        'type' => 'string',
                        'description' => 'Task description',
                        'required' => true,
                    ],
                    [
                        'name' => 'due_date',
                        'type' => 'string',
                        'description' => 'Task due date',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'update_task',
                'description' => 'Update an existing task',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'task_id',
                        'type' => 'integer',
                        'description' => 'ID of the task to update',
                        'required' => true,
                    ],
                    [
                        'name' => 'task_data',
                        'type' => 'object',
                        'description' => 'Updated task data',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'delete_task',
                'description' => 'Delete a task',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'task_id',
                        'type' => 'integer',
                        'description' => 'ID of the task to delete',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'list_tasks',
                'description' => 'List all tasks',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'filters',
                        'type' => 'object',
                        'description' => 'Optional filters for the task list',
                        'required' => false,
                    ],
                ],
            ],
            // Project Management Tools
            [
                'name' => 'create_project',
                'description' => 'Create a new project',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'name',
                        'type' => 'string',
                        'description' => 'Project name',
                        'required' => true,
                    ],
                    [
                        'name' => 'description',
                        'type' => 'string',
                        'description' => 'Project description',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'update_project',
                'description' => 'Update an existing project',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'project_id',
                        'type' => 'integer',
                        'description' => 'ID of the project to update',
                        'required' => true,
                    ],
                    [
                        'name' => 'project_data',
                        'type' => 'object',
                        'description' => 'Updated project data',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'delete_project',
                'description' => 'Delete a project',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'project_id',
                        'type' => 'integer',
                        'description' => 'ID of the project to delete',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'list_projects',
                'description' => 'List all projects',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'filters',
                        'type' => 'object',
                        'description' => 'Optional filters for the project list',
                        'required' => false,
                    ],
                ],
            ],
            // Appointment Tools
            [
                'name' => 'create_appointment',
                'description' => 'Create a new appointment',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'title',
                        'type' => 'string',
                        'description' => 'Appointment title',
                        'required' => true,
                    ],
                    [
                        'name' => 'start_time',
                        'type' => 'string',
                        'description' => 'Appointment start time',
                        'required' => true,
                    ],
                    [
                        'name' => 'end_time',
                        'type' => 'string',
                        'description' => 'Appointment end time',
                        'required' => true,
                    ],
                    [
                        'name' => 'attendees',
                        'type' => 'array',
                        'description' => 'List of attendee IDs',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'update_appointment',
                'description' => 'Update an existing appointment',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'appointment_id',
                        'type' => 'integer',
                        'description' => 'ID of the appointment to update',
                        'required' => true,
                    ],
                    [
                        'name' => 'appointment_data',
                        'type' => 'object',
                        'description' => 'Updated appointment data',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'delete_appointment',
                'description' => 'Delete an appointment',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'appointment_id',
                        'type' => 'integer',
                        'description' => 'ID of the appointment to delete',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'list_appointments',
                'description' => 'List all appointments',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'filters',
                        'type' => 'object',
                        'description' => 'Optional filters for the appointment list',
                        'required' => false,
                    ],
                ],
            ],
            // User Management Tools
            [
                'name' => 'create_user',
                'description' => 'Create a new user',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'user_data',
                        'type' => 'object',
                        'description' => 'User data including name, email, etc.',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'update_user',
                'description' => 'Update an existing user',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'user_id',
                        'type' => 'integer',
                        'description' => 'ID of the user to update',
                        'required' => true,
                    ],
                    [
                        'name' => 'user_data',
                        'type' => 'object',
                        'description' => 'Updated user data',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'delete_user',
                'description' => 'Delete a user',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'user_id',
                        'type' => 'integer',
                        'description' => 'ID of the user to delete',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'list_users',
                'description' => 'List all users',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'filters',
                        'type' => 'object',
                        'description' => 'Optional filters for the user list',
                        'required' => false,
                    ],
                ],
            ],
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
                'description' => 'Start taking a survey',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'survey_id',
                        'type' => 'integer',
                        'description' => 'ID of the survey to start',
                        'required' => true,
                    ],
                    [
                        'name' => 'campaign_id',
                        'type' => 'integer',
                        'description' => 'ID of the campaign (optional, will create one if not provided)',
                        'required' => false,
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
            // Rainbow Dashboard Tools
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
                        'description' => 'The ID of the user',
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
                'name' => 'rainbow_dashboard_lookup_tickets_by_user',
                'description' => 'Lookup tickets by username',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'user_name',
                        'type' => 'string',
                        'description' => 'The username to lookup tickets for',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'rainbow_dashboard_lookup_user',
                'description' => 'Lookup user by username',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'user_name',
                        'type' => 'string',
                        'description' => 'The username to lookup',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'rainbow_dashboard_get_ticket',
                'description' => 'Get a specific ticket by ID',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'ticket_id',
                        'type' => 'integer',
                        'description' => 'The ID of the ticket to retrieve',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'rainbow_dashboard_create_ticket',
                'description' => 'Create a new ticket',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'title',
                        'type' => 'string',
                        'description' => 'The title of the ticket',
                        'required' => true,
                    ],
                    [
                        'name' => 'description',
                        'type' => 'string',
                        'description' => 'The description of the ticket',
                        'required' => true,
                    ],
                    [
                        'name' => 'category',
                        'type' => 'string',
                        'description' => 'The category of the ticket',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'rainbow_dashboard_add_reply',
                'description' => 'Add a reply to an existing ticket',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'ticket_id',
                        'type' => 'integer',
                        'description' => 'The ID of the ticket to reply to',
                        'required' => true,
                    ],
                    [
                        'name' => 'content',
                        'type' => 'string',
                        'description' => 'The content of the reply',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'rainbow_dashboard_close_ticket',
                'description' => 'Close an existing ticket',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'ticket_id',
                        'type' => 'integer',
                        'description' => 'The ID of the ticket to close',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'rainbow_dashboard_reopen_ticket',
                'description' => 'Reopen a closed ticket',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'ticket_id',
                        'type' => 'integer',
                        'description' => 'The ID of the ticket to reopen',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'rainbow_dashboard_get_recent_tickets',
                'description' => 'Get recent tickets with pattern analysis',
                'strict' => true,
                'parameters' => [],
            ],
            [
                'name' => 'rainbow_dashboard_update_assignment',
                'description' => 'Update the assignment of a ticket to a different user',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'ticket_id',
                        'type' => 'integer',
                        'description' => 'The ID of the ticket to update',
                        'required' => true,
                    ],
                    [
                        'name' => 'user_id',
                        'type' => 'integer',
                        'description' => 'The ID of the user to assign the ticket to',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'rainbow_dashboard_update_ticket',
                'description' => 'Update a ticket\'s details',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'ticket_id',
                        'type' => 'integer',
                        'description' => 'The ID of the ticket to update',
                        'required' => true,
                    ],
                    [
                        'name' => 'ticket_data',
                        'type' => 'object',
                        'description' => 'The updated ticket data (title, description, category, etc.)',
                        'required' => true,
                    ],
                ],
            ],
            // Rainbow Customer Tools
            [
                'name' => 'rainbow_customer_search',
                'description' => 'Search for customers in the Rainbow system',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'search_term',
                        'type' => 'string',
                        'description' => 'The search term to look up customers',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'rainbow_get_cpni_questions',
                'description' => 'Get CPNI verification questions for a customer',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'customer_id',
                        'type' => 'integer',
                        'description' => 'The ID of the customer',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'rainbow_verify_cpni',
                'description' => 'Verify CPNI for a customer',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'customer_id',
                        'type' => 'integer',
                        'description' => 'The ID of the customer',
                        'required' => true,
                    ],
                    [
                        'name' => 'answers',
                        'type' => 'array',
                        'description' => 'The answers to CPNI questions',
                        'required' => true,
                    ],
                ],
            ],
            // Coding Tools
            [
                'name' => 'suggest_file_change',
                'description' => 'Suggest changes to a file',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'file_path',
                        'type' => 'string',
                        'description' => 'Path to the file to modify',
                        'required' => true,
                    ],
                    [
                        'name' => 'changes',
                        'type' => 'object',
                        'description' => 'The suggested changes to make',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'suggest_artisan_command',
                'description' => 'Suggest an Artisan command to run',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'command',
                        'type' => 'string',
                        'description' => 'The Artisan command to run',
                        'required' => true,
                    ],
                    [
                        'name' => 'arguments',
                        'type' => 'array',
                        'description' => 'Command arguments',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'suggest_sql',
                'description' => 'Suggest SQL queries',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'query',
                        'type' => 'string',
                        'description' => 'The SQL query to execute',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'analyze_requirements',
                'description' => 'Analyze project requirements',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'requirements',
                        'type' => 'string',
                        'description' => 'The requirements to analyze',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'design_schema',
                'description' => 'Design database schema',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'requirements',
                        'type' => 'string',
                        'description' => 'The requirements for the schema',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'create_migration',
                'description' => 'Create a database migration',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'migration_name',
                        'type' => 'string',
                        'description' => 'Name of the migration',
                        'required' => true,
                    ],
                    [
                        'name' => 'schema',
                        'type' => 'object',
                        'description' => 'The schema for the migration',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'create_model',
                'description' => 'Create a model',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'model_name',
                        'type' => 'string',
                        'description' => 'Name of the model',
                        'required' => true,
                    ],
                    [
                        'name' => 'attributes',
                        'type' => 'object',
                        'description' => 'The attributes for the model',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'create_controller',
                'description' => 'Create a controller',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'controller_name',
                        'type' => 'string',
                        'description' => 'Name of the controller',
                        'required' => true,
                    ],
                    [
                        'name' => 'methods',
                        'type' => 'array',
                        'description' => 'The methods to include in the controller',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'create_api_routes',
                'description' => 'Create API routes',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'routes',
                        'type' => 'array',
                        'description' => 'The routes to create',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'create_frontend_component',
                'description' => 'Create a frontend component',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'component_name',
                        'type' => 'string',
                        'description' => 'Name of the component',
                        'required' => true,
                    ],
                    [
                        'name' => 'props',
                        'type' => 'array',
                        'description' => 'The props for the component',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'create_test',
                'description' => 'Create a test',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'test_name',
                        'type' => 'string',
                        'description' => 'Name of the test',
                        'required' => true,
                    ],
                    [
                        'name' => 'test_cases',
                        'type' => 'array',
                        'description' => 'The test cases to include',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'create_project_skeleton',
                'description' => 'Create a project skeleton',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'project_name',
                        'type' => 'string',
                        'description' => 'Name of the project',
                        'required' => true,
                    ],
                    [
                        'name' => 'structure',
                        'type' => 'object',
                        'description' => 'The project structure to create',
                        'required' => true,
                    ],
                ],
            ],
            // OpenAI Image Generation Tools
            [
                'name' => 'openai_generate_image',
                'description' => 'Generate an image using OpenAI\'s DALL-E model',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'prompt',
                        'type' => 'string',
                        'description' => 'Text description of the desired image',
                        'required' => true,
                    ],
                    [
                        'name' => 'n',
                        'type' => 'integer',
                        'description' => 'Number of images to generate (1-10)',
                        'required' => false,
                    ],
                    [
                        'name' => 'size',
                        'type' => 'string',
                        'description' => 'Size of the generated image (256x256, 512x512, 1024x1024, 1024x1792, 1792x1024)',
                        'required' => false,
                    ],
                    [
                        'name' => 'quality',
                        'type' => 'string',
                        'description' => 'Quality of the image (standard, hd)',
                        'required' => false,
                    ],
                    [
                        'name' => 'style',
                        'type' => 'string',
                        'description' => 'Style of the image (vivid, natural)',
                        'required' => false,
                    ],
                    [
                        'name' => 'response_format',
                        'type' => 'string',
                        'description' => 'Format of the response (url, b64_json)',
                        'required' => false,
                    ],
                    [
                        'name' => 'save_path',
                        'type' => 'string',
                        'description' => 'Path to save the generated image',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'openai_edit_image',
                'description' => 'Edit an existing image using OpenAI\'s DALL-E model',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'image_path',
                        'type' => 'string',
                        'description' => 'Path to the image to edit',
                        'required' => true,
                    ],
                    [
                        'name' => 'prompt',
                        'type' => 'string',
                        'description' => 'Text description of the desired edit',
                        'required' => true,
                    ],
                    [
                        'name' => 'mask_path',
                        'type' => 'string',
                        'description' => 'Path to the mask image (optional)',
                        'required' => false,
                    ],
                    [
                        'name' => 'n',
                        'type' => 'integer',
                        'description' => 'Number of images to generate (1-10)',
                        'required' => false,
                    ],
                    [
                        'name' => 'size',
                        'type' => 'string',
                        'description' => 'Size of the generated image (256x256, 512x512, 1024x1024)',
                        'required' => false,
                    ],
                    [
                        'name' => 'response_format',
                        'type' => 'string',
                        'description' => 'Format of the response (url, b64_json)',
                        'required' => false,
                    ],
                    [
                        'name' => 'save_path',
                        'type' => 'string',
                        'description' => 'Path to save the edited image',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'openai_create_variation',
                'description' => 'Create a variation of an existing image using OpenAI\'s DALL-E model',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'image_path',
                        'type' => 'string',
                        'description' => 'Path to the image to create variation from',
                        'required' => true,
                    ],
                    [
                        'name' => 'n',
                        'type' => 'integer',
                        'description' => 'Number of variations to generate (1-10)',
                        'required' => false,
                    ],
                    [
                        'name' => 'size',
                        'type' => 'string',
                        'description' => 'Size of the generated image (256x256, 512x512, 1024x1024)',
                        'required' => false,
                    ],
                    [
                        'name' => 'response_format',
                        'type' => 'string',
                        'description' => 'Format of the response (url, b64_json)',
                        'required' => false,
                    ],
                    [
                        'name' => 'save_path',
                        'type' => 'string',
                        'description' => 'Path to save the variation image',
                        'required' => false,
                    ],
                ],
            ],
            // WebSocket Control Tools
            [
                'name' => 'end_websocket_connection',
                'description' => 'End the current WebSocket connection',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'reason',
                        'type' => 'string',
                        'description' => 'Reason for ending the connection',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'end_conversation',
                'description' => 'End the current conversation and return to the web server',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'reason',
                        'type' => 'string',
                        'description' => 'Reason for ending the conversation',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'pause_conversation',
                'description' => 'Pause the current conversation temporarily',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'reason',
                        'type' => 'string',
                        'description' => 'Reason for pausing the conversation',
                        'required' => true,
                    ],
                    [
                        'name' => 'duration',
                        'type' => 'integer',
                        'description' => 'Duration in seconds to pause (0 for indefinite)',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'resume_conversation',
                'description' => 'Resume a paused conversation',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'reason',
                        'type' => 'string',
                        'description' => 'Reason for resuming the conversation',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'end_call',
                'description' => 'End the current call but allow the conversation to continue elsewhere',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'reason',
                        'type' => 'string',
                        'description' => 'Reason for ending the call',
                        'required' => true,
                    ],
                    [
                        'name' => 'redirect_url',
                        'type' => 'string',
                        'description' => 'URL to redirect the conversation to (optional)',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'websocket_control_add_phone',
                'description' => 'Add a phone to the current call',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'phone_number',
                        'type' => 'string',
                        'description' => 'The phone number to add',
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'websocket_control_add_assistant',
                'description' => 'Add an assistant to the current call',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'assistant_id',
                        'type' => 'string',
                        'description' => 'The ID of the assistant to add',
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'websocket_control_change_assistant',
                'description' => 'Change the assistant in the current call',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'assistant_id',
                        'type' => 'string',
                        'description' => 'The ID of the new assistant',
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'websocket_control_disconnect_caller',
                'description' => 'Disconnect a caller from the current call',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'caller_id',
                        'type' => 'string',
                        'description' => 'The ID of the caller to disconnect',
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'websocket_control_end_call',
                'description' => 'End the current call',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'call_id',
                        'type' => 'string',
                        'description' => 'The ID of the call to end',
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'websocket_control_resume_conversation',
                'description' => 'Resume a paused conversation',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'conversation_id',
                        'type' => 'string',
                        'description' => 'The ID of the conversation to resume',
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'websocket_control_pause_conversation',
                'description' => 'Pause the current conversation',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'conversation_id',
                        'type' => 'string',
                        'description' => 'The ID of the conversation to pause',
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'websocket_control_start_recording',
                'description' => 'Start recording the current call',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'call_id',
                        'type' => 'string',
                        'description' => 'The ID of the call to record',
                        'required' => true
                    ],
                    [
                        'name' => 'recording_type',
                        'type' => 'string',
                        'description' => 'Type of recording (audio, video, both)',
                        'required' => false
                    ]
                ]
            ],
            [
                'name' => 'websocket_control_stop_recording',
                'description' => 'Stop recording the current call',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'call_id',
                        'type' => 'string',
                        'description' => 'The ID of the call',
                        'required' => true
                    ],
                    [
                        'name' => 'recording_id',
                        'type' => 'string',
                        'description' => 'The ID of the recording to stop',
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'websocket_control_start_transcription',
                'description' => 'Start real-time transcription of the call',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'call_id',
                        'type' => 'string',
                        'description' => 'The ID of the call to transcribe',
                        'required' => true
                    ],
                    [
                        'name' => 'language',
                        'type' => 'string',
                        'description' => 'Language code for transcription (e.g., en-US)',
                        'required' => false
                    ],
                    [
                        'name' => 'speaker_diarization',
                        'type' => 'boolean',
                        'description' => 'Whether to identify different speakers',
                        'required' => false
                    ]
                ]
            ],
            [
                'name' => 'websocket_control_stop_transcription',
                'description' => 'Stop real-time transcription of the call',
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
                        'description' => 'The ID of the transcription to stop',
                        'required' => true
                    ]
                ]
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
            [
                'name' => 'richbot_search_contacts',
                'description' => 'Search for contacts in the RichBot system',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'search_term',
                        'type' => 'string',
                        'description' => 'The term to search for',
                        'required' => true
                    ],
                    [
                        'name' => 'search_type',
                        'type' => 'string',
                        'description' => 'The type of search (all, name, email, phone)',
                        'required' => false
                    ]
                ]
            ],
            [
                'name' => 'richbot_search_assistants',
                'description' => 'Search for assistants in the RichBot system',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'search_term',
                        'type' => 'string',
                        'description' => 'The term to search for',
                        'required' => true
                    ],
                    [
                        'name' => 'search_type',
                        'type' => 'string',
                        'description' => 'The type of search (all, name, specialty, availability)',
                        'required' => false
                    ]
                ]
            ],
            [
                'name' => 'richbot_get_contact',
                'description' => 'Get details for a specific contact',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'contact_id',
                        'type' => 'string',
                        'description' => 'The ID of the contact to retrieve',
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'richbot_get_assistant',
                'description' => 'Get details for a specific assistant',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'assistant_id',
                        'type' => 'string',
                        'description' => 'The ID of the assistant to retrieve',
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'richbot_get_contact_history',
                'description' => 'Get interaction history for a contact',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'contact_id',
                        'type' => 'string',
                        'description' => 'The ID of the contact',
                        'required' => true
                    ],
                    [
                        'name' => 'limit',
                        'type' => 'integer',
                        'description' => 'Maximum number of history items to return',
                        'required' => false
                    ]
                ]
            ],
            [
                'name' => 'richbot_get_available_assistants',
                'description' => 'Get list of available assistants for a time slot',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'start_time',
                        'type' => 'string',
                        'description' => 'Start time of the slot',
                        'required' => true
                    ],
                    [
                        'name' => 'end_time',
                        'type' => 'string',
                        'description' => 'End time of the slot',
                        'required' => true
                    ],
                    [
                        'name' => 'specialty',
                        'type' => 'string',
                        'description' => 'Optional specialty filter',
                        'required' => false
                    ]
                ]
            ],
            [
                'name' => 'rainbow_dashboard_search_kb_article',
                'description' => 'Search for knowledge base articles in the Rainbow dashboard',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'search_string',
                        'type' => 'string',
                        'description' => 'The search string to find articles',
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'rainbow_dashboard_get_kb_article',
                'description' => 'Get a specific knowledge base article from the Rainbow dashboard',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'article_id',
                        'type' => 'integer',
                        'description' => 'The ID of the knowledge base article to retrieve',
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'rainbow_dashboard_email_kb_article',
                'description' => 'Email a knowledge base article to a user',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'article_id',
                        'type' => 'integer',
                        'description' => 'The ID of the knowledge base article to email',
                        'required' => true
                    ],
                    [
                        'name' => 'user_id',
                        'type' => 'integer',
                        'description' => 'The ID of the user to send the article to',
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'rainbow_dashboard_get_categories',
                'description' => 'Get all knowledge base categories',
                'strict' => true,
                'parameters' => [],
            ],
            [
                'name' => 'rainbow_dashboard_create_article',
                'description' => 'Create a new knowledge base article',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'title',
                        'type' => 'string',
                        'description' => 'Title of the article',
                        'required' => true,
                    ],
                    [
                        'name' => 'content',
                        'type' => 'string',
                        'description' => 'Content of the article',
                        'required' => true,
                    ],
                    [
                        'name' => 'k_b_category_id',
                        'type' => 'integer',
                        'description' => 'ID of the category',
                        'required' => true,
                    ],
                    [
                        'name' => 'help_desk_article',
                        'type' => 'boolean',
                        'description' => 'Whether this is a help desk article',
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'richbot_add_contact',
                'description' => 'Add a new contact to the RichBot system',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'name',
                        'type' => 'string',
                        'description' => 'The name of the contact',
                        'required' => true
                    ],
                    [
                        'name' => 'email',
                        'type' => 'string',
                        'description' => 'The email address of the contact',
                        'required' => true
                    ],
                    [
                        'name' => 'phone',
                        'type' => 'string',
                        'description' => 'The phone number of the contact',
                        'required' => false
                    ]
                ]
            ],
        ];
    }

    protected function getToolGroups()
    {
        return [
            [
                'name' => 'File System',
                'description' => 'Tools for file and directory operations',
                'tools' => [
                    'read_file',
                    'write_file',
                    'delete_file',
                    'list_files',
                    'create_directory',
                    'delete_directory',
                    'move_file',
                    'copy_file',
                    'search_files'
                ]
            ],
            [
                'name' => 'Communication',
                'description' => 'Tools for sending emails and SMS messages',
                'tools' => [
                    'send_email',
                    'send_sms',
                    'send_bulk_email'
                ]
            ],
            [
                'name' => 'Task Management',
                'description' => 'Tools for managing tasks and projects',
                'tools' => [
                    'create_task',
                    'update_task',
                    'delete_task',
                    'list_tasks',
                    'create_project',
                    'update_project',
                    'delete_project',
                    'list_projects'
                ]
            ],
            [
                'name' => 'Appointment Management',
                'description' => 'Tools for managing appointments and schedules',
                'tools' => [
                    'create_appointment',
                    'update_appointment',
                    'delete_appointment',
                    'list_appointments'
                ]
            ],
            [
                'name' => 'User Management',
                'description' => 'Tools for managing users and permissions',
                'tools' => [
                    'create_user',
                    'update_user',
                    'delete_user',
                    'list_users'
                ]
            ],
            [
                'name' => 'Survey Management',
                'description' => 'Tools for creating and managing surveys',
                'tools' => [
                    'survey_list',
                    'survey_get',
                    'survey_question_create',
                    'survey_question_update',
                    'survey_question_delete',
                    'survey_question_order_update',
                    'survey_campaign_list',
                    'survey_campaign_create',
                    'survey_campaign_get',
                    'survey_campaign_update',
                    'survey_campaign_delete',
                    'survey_campaign_contacts_get',
                    'survey_campaign_contacts_add',
                    'survey_campaign_contact_remove',
                    'survey_campaign_survey_get',
                    'survey_start',
                    'survey_question_next',
                    'survey_answer_submit',
                    'survey_progress_get',
                    'survey_complete',
                    'survey_results_get'
                ]
            ],
            [
                'name' => 'Rainbow Dashboard',
                'description' => 'Tools for managing Rainbow Dashboard tickets',
                'tools' => [
                    'rainbow_dashboard_get_all_tickets',
                    'rainbow_dashboard_get_my_tickets',
                    'rainbow_dashboard_get_user_tickets',
                    'rainbow_dashboard_lookup_tickets_by_user',
                    'rainbow_dashboard_lookup_user',
                    'rainbow_dashboard_get_ticket',
                    'rainbow_dashboard_create_ticket',
                    'rainbow_dashboard_add_reply',
                    'rainbow_dashboard_close_ticket',
                    'rainbow_dashboard_reopen_ticket',
                    'rainbow_dashboard_get_recent_tickets',
                    'rainbow_dashboard_update_assignment',
                    'rainbow_dashboard_update_ticket'
                ]
            ],
            [
                'name' => 'Rainbow Customer',
                'description' => 'Tools for managing Rainbow customer data',
                'tools' => [
                    'rainbow_customer_search',
                    'rainbow_get_cpni_questions',
                    'rainbow_verify_cpni'
                ]
            ],
            [
                'name' => 'Development',
                'description' => 'Tools for development and coding tasks',
                'tools' => [
                    'suggest_file_change',
                    'suggest_artisan_command',
                    'suggest_sql',
                    'analyze_requirements',
                    'design_schema',
                    'create_migration',
                    'create_model',
                    'create_controller',
                    'create_api_routes',
                    'create_frontend_component',
                    'create_test',
                    'create_project_skeleton'
                ]
            ],
            [
                'name' => 'OpenAI Image Generation',
                'description' => 'Tools for generating and editing images using OpenAI\'s DALL-E model',
                'tools' => [
                    'openai_generate_image',
                    'openai_edit_image',
                    'openai_create_variation'
                ]
            ],
            [
                'name' => 'WebSocket Control',
                'description' => 'Tools for controlling WebSocket connections and conversations',
                'tools' => [
                    'websocket_control_add_phone',
                    'websocket_control_add_assistant',
                    'websocket_control_change_assistant',
                    'websocket_control_disconnect_caller',
                    'websocket_control_end_call',
                    'websocket_control_resume_conversation',
                    'websocket_control_pause_conversation',
                    'websocket_control_start_recording',
                    'websocket_control_stop_recording',
                    'websocket_control_start_transcription',
                    'websocket_control_stop_transcription',
                    'websocket_control_get_transcription_status',
                    'websocket_control_add_monitor'
                ]
            ],
            [
                'name' => 'RichBot',
                'description' => 'Tools for managing RichBot contacts and assistants',
                'tools' => [
                    'richbot_search_contacts',
                    'richbot_search_assistants',
                    'richbot_get_contact',
                    'richbot_get_assistant',
                    'richbot_get_contact_history',
                    'richbot_get_available_assistants',
                    'richbot_add_contact'
                ]
            ],
            [
                'name' => 'Rainbow Knowledge Base',
                'description' => 'Tools for managing Rainbow Knowledge Base articles',
                'tools' => [
                    'rainbow_dashboard_search_kb_article',
                    'rainbow_dashboard_get_kb_article',
                    'rainbow_dashboard_email_kb_article',
                    'rainbow_dashboard_get_categories',
                    'rainbow_dashboard_create_article'
                ]
            ],
            [
                'name' => 'Weather',
                'description' => 'Tools for weather data and forecasts',
                'tools' => [
                    'weather_get_by_coordinates',
                    'weather_get_by_ip',
                    'weather_alerts_get',
                    'weather_hourly_get',
                    'weather_grid_data_get',
                    'weather_by_address'
                ]
            ],
            [
                'name' => 'Contact',
                'description' => 'Tools for managing contacts',
                'tools' => [
                    'contact_start_opt_in',
                    'contact_opt_in',
                    'contact_update',
                    'contact_delete',
                    'contact_get',
                    'contact_search',
                    'contact_list',
                    'contact_add',
                    
                ]
            ]
        ];
    }

    protected function importToolGroup($groupData, $createdTools, $force)
    {
        // Get the first user from the database to use as the owner
        $user = DB::table('users')->first();
        
        if (!$user) {
            $this->error('No users found in the database. Please create a user first.');
            return;
        }

        $group = DB::table('tool_groups')
            ->where('name', $groupData['name'])
            ->first();

        if ($group && !$force) {
            $this->info("Group {$groupData['name']} already exists. Skipping...");
            return;
        }

        if ($group) {
            DB::table('tool_groups')
                ->where('id', $group->id)
                ->update([
                    'description' => $groupData['description'],
                    'user_id' => $user->id,
                    'updated_at' => now()
                ]);
            $groupId = $group->id;
        } else {
            $groupId = DB::table('tool_groups')->insertGetId([
                'name' => $groupData['name'],
                'description' => $groupData['description'],
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Clear existing tool associations
        DB::table('tool_tool_group')->where('tool_group_id', $groupId)->delete();

        // Associate tools with the group
        foreach ($groupData['tools'] as $toolName) {

            if($toolName instanceof Tool) {
                $toolName = $toolName->name;
            }

            if (isset($createdTools[$toolName])) {
                DB::table('tool_tool_group')->insert([
                    'tool_id' => $createdTools[$toolName]->id,
                    'tool_group_id' => $groupId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        $this->info("Group {$groupData['name']} imported successfully.");
    }

    protected function importTool($toolData, $force, $addOnly = false)
    {
        $existingTool = \App\Models\Tool::where('name', $toolData['name'])->first();

        if ($existingTool) {
            if ($addOnly) {
                $this->info("Tool '{$toolData['name']}' already exists. Skipping...");
                return $existingTool;
            }
            if (!$force) {
                $this->warn("Tool '{$toolData['name']}' already exists. Use --force to update.");
                return $existingTool;
            }
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($toolData, $existingTool) {
            if ($existingTool) {
                $tool = $existingTool;
                $tool->update([
                    'description' => $toolData['description'],
                    'strict' => $toolData['strict'],
                ]);
                $tool->parameters()->delete();
            } else {
                $tool = \App\Models\Tool::create([
                    'name' => $toolData['name'],
                    'description' => $toolData['description'],
                    'strict' => $toolData['strict'],
                ]);
            }

            foreach ($toolData['parameters'] as $paramData) {
                $tool->parameters()->create([
                    'name' => $paramData['name'],
                    'type' => $paramData['type'],
                    'description' => $paramData['description'] ?? null,
                    'required' => $paramData['required'] ?? false,
                ]);
            }

            $this->info("Tool '{$toolData['name']}' has been " . ($existingTool ? 'updated' : 'created') . " successfully.");
            return $tool;
        });
    }
} 