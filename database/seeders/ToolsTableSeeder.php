<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tool;

class ToolsTableSeeder extends Seeder
{
    public function run()
    {
        $tools = [
            // 1. Generate Image
            [
                'name' => 'generate_image',
                'method' => 'post',
                'summary' => 'Generate an image based on a prompt',
                'operation_id' => 'generateImage',
                'parameters' => [
                    [
                        'name' => 'prompt',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The prompt to generate the image from',
                        ],
                    ],
                    [
                        'name' => 'size',
                        'in' => 'body',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'enum' => ['256x256', '512x512', '1024x1024'],
                            'default' => '256x256',
                            'description' => 'The size of the generated image',
                        ],
                    ],
                    [
                        'name' => 'target_path',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The file path to save the generated image',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Image generated and saved successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'success' => ['type' => 'boolean'],
                                        'message' => ['type' => 'string'],
                                        'public_url' => ['type' => 'string'],
                                        'file_path' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    '400' => [
                        'description' => 'Failed to generate or save the image',
                    ],
                ],
            ],

            // 2. Update Display
            [
                'name' => 'update_display',
                'method' => 'post',
                'summary' => 'Update display with new HTML content',
                'operation_id' => 'updateDisplay',
                'parameters' => [
                    [
                        'name' => 'display_name',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'Name of the display to update',
                        ],
                    ],
                    [
                        'name' => 'html_content',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'HTML content to update the display with',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Display updated successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'success' => ['type' => 'boolean'],
                                        'message' => ['type' => 'string'],
                                        'path' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    '400' => [
                        'description' => 'Failed to update the display',
                    ],
                ],
            ],

            // 3. List Users
            [
                'name' => 'list_users',
                'method' => 'get',
                'summary' => 'List all users',
                'operation_id' => 'listUsers',
                'parameters' => [],
                'responses' => [
                    '200' => [
                        'description' => 'List of users',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'object'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // 4. Add User
            [
                'name' => 'add_user',
                'method' => 'post',
                'summary' => 'Add a new user',
                'operation_id' => 'addUser',
                'parameters' => [
                    [
                        'name' => 'name',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'Name of the user',
                        ],
                    ],
                    [
                        'name' => 'email',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'format' => 'email',
                            'description' => 'Email address of the user',
                        ],
                    ],
                    [
                        'name' => 'password',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'format' => 'password',
                            'description' => 'Password for the user account',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'User added successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => ['type' => 'object'],
                            ],
                        ],
                    ],
                    '400' => [
                        'description' => 'Validation errors occurred',
                    ],
                ],
            ],

            // 5. View User
            [
                'name' => 'view_user',
                'method' => 'get',
                'summary' => 'View a specific user by ID',
                'operation_id' => 'viewUser',
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'ID of the user to view',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'User details',
                        'content' => [
                            'application/json' => [
                                'schema' => ['type' => 'object'],
                            ],
                        ],
                    ],
                    '404' => [
                        'description' => 'User not found',
                    ],
                ],
            ],

            // 6. Delete User
            [
                'name' => 'delete_user',
                'method' => 'delete',
                'summary' => 'Delete a user by ID',
                'operation_id' => 'deleteUser',
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'ID of the user to delete',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'User deleted successfully',
                    ],
                    '404' => [
                        'description' => 'User not found',
                    ],
                ],
            ],

            // 7. List Folders
            [
                'name' => 'list_folders',
                'method' => 'get',
                'summary' => 'List all folders in a directory',
                'operation_id' => 'listFolders',
                'parameters' => [
                    [
                        'name' => 'directory',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The directory path to list folders from',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'List of folders in the directory',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                    '404' => [
                        'description' => 'Directory not found',
                    ],
                ],
            ],

            // 8. List Files
            [
                'name' => 'list_files',
                'method' => 'get',
                'summary' => 'List all files in a directory',
                'operation_id' => 'listFiles',
                'parameters' => [
                    [
                        'name' => 'directory',
                        'in' => 'query',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The directory path to list files from',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'List of files in the directory',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                    '404' => [
                        'description' => 'Directory not found',
                    ],
                ],
            ],

            // 9. Create Directory
            [
                'name' => 'create_directory',
                'method' => 'post',
                'summary' => 'Create a new directory',
                'operation_id' => 'createDirectory',
                'parameters' => [
                    [
                        'name' => 'directory',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The directory path to create',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Directory created successfully',
                    ],
                    '400' => [
                        'description' => 'Directory already exists',
                    ],
                ],
            ],

            // 10. Delete Directory
            [
                'name' => 'delete_directory',
                'method' => 'delete',
                'summary' => 'Delete a directory',
                'operation_id' => 'deleteDirectory',
                'parameters' => [
                    [
                        'name' => 'directory',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The directory path to delete',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Directory deleted successfully',
                    ],
                    '404' => [
                        'description' => 'Directory not found',
                    ],
                ],
            ],

            // 11. Send Email
            [
                'name' => 'send_email',
                'method' => 'post',
                'summary' => 'Send an email to a specified address',
                'operation_id' => 'sendEmail',
                'parameters' => [
                    [
                        'name' => 'to',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'format' => 'email',
                            'description' => 'Recipient email address',
                        ],
                    ],
                    [
                        'name' => 'subject',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'Email subject',
                        ],
                    ],
                    [
                        'name' => 'body',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'Email body content',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Email sent successfully',
                    ],
                    '400' => [
                        'description' => 'Failed to send email',
                    ],
                ],
            ],

            // 12. Send SMS
            [
                'name' => 'send_sms',
                'method' => 'post',
                'summary' => 'Send an SMS to a specified number',
                'operation_id' => 'sendSms',
                'parameters' => [
                    [
                        'name' => 'to',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'Recipient phone number',
                        ],
                    ],
                    [
                        'name' => 'body',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'SMS message content',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'SMS sent successfully',
                    ],
                    '400' => [
                        'description' => 'Failed to send SMS',
                    ],
                ],
            ],

            // 13. Get Current Weather
            [
                'name' => 'get_current_weather',
                'method' => 'get',
                'summary' => 'Provides current weather information for a specified location',
                'operation_id' => 'getCurrentWeather',
                'parameters' => [
                    [
                        'name' => 'location',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'Location to get the weather for (e.g., "Paris, FR")',
                        ],
                    ],
                    [
                        'name' => 'format',
                        'in' => 'query',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'enum' => ['celsius', 'fahrenheit'],
                            'default' => 'celsius',
                            'description' => 'Temperature format',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Current weather information',
                        'content' => [
                            'application/json' => [
                                'schema' => ['type' => 'string'],
                            ],
                        ],
                    ],
                    '400' => [
                        'description' => 'Failed to fetch weather information',
                    ],
                ],
            ],

            // 14. Download File
            [
                'name' => 'download_file',
                'method' => 'get',
                'summary' => 'Download a file from the server',
                'operation_id' => 'downloadFile',
                'parameters' => [
                    [
                        'name' => 'filePath',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The path of the file to download',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'File contents',
                        'content' => [
                            'application/octet-stream' => [
                                'schema' => [
                                    'type' => 'string',
                                    'format' => 'binary',
                                ],
                            ],
                        ],
                    ],
                    '404' => [
                        'description' => 'File not found',
                    ],
                ],
            ],

            // 15. Delete File
            [
                'name' => 'delete_file',
                'method' => 'delete',
                'summary' => 'Delete a file from the server',
                'operation_id' => 'deleteFile',
                'parameters' => [
                    [
                        'name' => 'filePath',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The path of the file to delete',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'File deleted successfully',
                    ],
                    '404' => [
                        'description' => 'File not found',
                    ],
                ],
            ],

            // 16. Put Text
            [
                'name' => 'put_text',
                'method' => 'post',
                'summary' => 'Save text content to a specified file, replacing existing content',
                'operation_id' => 'putText',
                'parameters' => [
                    [
                        'name' => 'filePath',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The path of the file to write to',
                        ],
                    ],
                    [
                        'name' => 'content',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The text content to save',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Content saved successfully',
                    ],
                    '400' => [
                        'description' => 'Failed to save content',
                    ],
                ],
            ],

            // 17. Append Text
            [
                'name' => 'append_text',
                'method' => 'post',
                'summary' => 'Append text content to a specified file',
                'operation_id' => 'appendText',
                'parameters' => [
                    [
                        'name' => 'filePath',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The path of the file to append to',
                        ],
                    ],
                    [
                        'name' => 'content',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The text content to append',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Content appended successfully',
                    ],
                    '400' => [
                        'description' => 'Failed to append content',
                    ],
                ],
            ],

            // 18. Edit File
            [
                'name' => 'edit',
                'method' => 'put',
                'summary' => 'Edit an existing file\'s content',
                'operation_id' => 'editFile',
                'parameters' => [
                    [
                        'name' => 'filePath',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The path of the file to edit',
                        ],
                    ],
                    [
                        'name' => 'content',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The new content to write to the file',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Content edited successfully',
                    ],
                    '404' => [
                        'description' => 'File not found',
                    ],
                ],
            ],

            // 19. Read File
            [
                'name' => 'read_file',
                'method' => 'get',
                'summary' => 'Read the contents of a file',
                'operation_id' => 'readFile',
                'parameters' => [
                    [
                        'name' => 'filePath',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The path of the file to read',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'File contents',
                        'content' => [
                            'text/plain' => [
                                'schema' => [
                                    'type' => 'string',
                                    'description' => 'Contents of the file',
                                ],
                            ],
                        ],
                    ],
                    '404' => [
                        'description' => 'File not found',
                    ],
                ],
            ],

            // 20. Move File
            [
                'name' => 'move_file',
                'method' => 'post',
                'summary' => 'Move a file to a new location',
                'operation_id' => 'moveFile',
                'parameters' => [
                    [
                        'name' => 'sourcePath',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The current path of the file',
                        ],
                    ],
                    [
                        'name' => 'destinationPath',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The new path for the file',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'File moved successfully',
                    ],
                    '404' => [
                        'description' => 'Source file not found',
                    ],
                ],
            ],

            // 21. Copy File
            [
                'name' => 'copy_file',
                'method' => 'post',
                'summary' => 'Copy a file to a new location',
                'operation_id' => 'copyFile',
                'parameters' => [
                    [
                        'name' => 'sourcePath',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The current path of the file',
                        ],
                    ],
                    [
                        'name' => 'destinationPath',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'The destination path for the copied file',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'File copied successfully',
                    ],
                    '404' => [
                        'description' => 'Source file not found',
                    ],
                ],
            ],

            // 22. Calculate Sum (Example of Additional Tool)
            [
                'name' => 'calculate_sum',
                'method' => 'post',
                'summary' => 'Calculate the sum of two numbers',
                'operation_id' => 'calculateSum',
                'parameters' => [
                    [
                        'name' => 'a',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'number',
                            'description' => 'First number',
                        ],
                    ],
                    [
                        'name' => 'b',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'number',
                            'description' => 'Second number',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Calculation successful',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'success' => ['type' => 'boolean'],
                                        'data' => [
                                            'type' => 'number',
                                            'description' => 'Sum of a and b',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    '400' => [
                        'description' => 'Missing required parameters',
                    ],
                ],
            ],

            // Add any additional tools here...
        ];

        foreach ($tools as $toolData) {

            $tool = [
                'name' => $toolData['name'],
                'method' => $toolData['method'],
                'summary' => $toolData['summary'],
                'operation_id' => $toolData['operation_id'],
                'parameters' => json_encode($toolData['parameters']),
                'responses' => json_encode($toolData['responses']),
            ];


            dump($tool);
            $results = Tool::create($tool);
            dump($results);
        }
    }
}
