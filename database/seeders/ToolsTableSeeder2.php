<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tool;

class ToolsTableSeeder2 extends Seeder
{
    public function run()
    {
        $tools = [
            // Task Management Tools

            // 1. Create Task
            [
                'name' => 'create_task',
                'method' => 'post',
                'summary' => 'Create a new task',
                'operation_id' => 'createTask',
                'parameters' => [
                    [
                        'name' => 'title',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'Title of the task',
                        ],
                    ],
                    [
                        'name' => 'description',
                        'in' => 'body',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'Description of the task',
                        ],
                    ],
                    [
                        'name' => 'project_id',
                        'in' => 'body',
                        'required' => false,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'ID of the project the task belongs to',
                        ],
                    ],
                    [
                        'name' => 'order',
                        'in' => 'body',
                        'required' => false,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'Order or priority of the task',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Task created successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => ['type' => 'object'],
                            ],
                        ],
                    ],
                    '400' => [
                        'description' => 'Failed to create task',
                    ],
                ],
            ],

            // 2. Update Task
            [
                'name' => 'update_task',
                'method' => 'put',
                'summary' => 'Update an existing task',
                'operation_id' => 'updateTask',
                'parameters' => [
                    [
                        'name' => 'task_id',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'ID of the task to update',
                        ],
                    ],
                    [
                        'name' => 'title',
                        'in' => 'body',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'New title of the task',
                        ],
                    ],
                    [
                        'name' => 'description',
                        'in' => 'body',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'New description of the task',
                        ],
                    ],
                    [
                        'name' => 'project_id',
                        'in' => 'body',
                        'required' => false,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'New project ID the task belongs to',
                        ],
                    ],
                    [
                        'name' => 'order',
                        'in' => 'body',
                        'required' => false,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'New order or priority of the task',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Task updated successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => ['type' => 'object'],
                            ],
                        ],
                    ],
                    '404' => [
                        'description' => 'Task not found',
                    ],
                ],
            ],

            // 3. Delete Task
            [
                'name' => 'delete_task',
                'method' => 'delete',
                'summary' => 'Delete a task',
                'operation_id' => 'deleteTask',
                'parameters' => [
                    [
                        'name' => 'task_id',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'ID of the task to delete',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Task deleted successfully',
                    ],
                    '404' => [
                        'description' => 'Task not found',
                    ],
                ],
            ],

            // 4. List Tasks
            [
                'name' => 'list_tasks',
                'method' => 'get',
                'summary' => 'List tasks with optional filters',
                'operation_id' => 'listTasks',
                'parameters' => [
                    [
                        'name' => 'project_id',
                        'in' => 'query',
                        'required' => false,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'Filter tasks by project ID',
                        ],
                    ],
                    [
                        'name' => 'assigned_user_id',
                        'in' => 'query',
                        'required' => false,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'Filter tasks by assigned user ID',
                        ],
                    ],
                    [
                        'name' => 'creator_user_id',
                        'in' => 'query',
                        'required' => false,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'Filter tasks by creator user ID',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'List of tasks',
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

            // Project Management Tools

            // 5. Create Project
            [
                'name' => 'create_project',
                'method' => 'post',
                'summary' => 'Create a new project',
                'operation_id' => 'createProject',
                'parameters' => [
                    [
                        'name' => 'name',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'Name of the project',
                        ],
                    ],
                    [
                        'name' => 'description',
                        'in' => 'body',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'Description of the project',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Project created successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => ['type' => 'object'],
                            ],
                        ],
                    ],
                    '400' => [
                        'description' => 'Failed to create project',
                    ],
                ],
            ],

            // 6. Update Project
            [
                'name' => 'update_project',
                'method' => 'put',
                'summary' => 'Update an existing project',
                'operation_id' => 'updateProject',
                'parameters' => [
                    [
                        'name' => 'project_id',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'ID of the project to update',
                        ],
                    ],
                    [
                        'name' => 'name',
                        'in' => 'body',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'New name of the project',
                        ],
                    ],
                    [
                        'name' => 'description',
                        'in' => 'body',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'New description of the project',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Project updated successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => ['type' => 'object'],
                            ],
                        ],
                    ],
                    '404' => [
                        'description' => 'Project not found',
                    ],
                ],
            ],

            // 7. Delete Project
            [
                'name' => 'delete_project',
                'method' => 'delete',
                'summary' => 'Delete a project',
                'operation_id' => 'deleteProject',
                'parameters' => [
                    [
                        'name' => 'project_id',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'ID of the project to delete',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Project deleted successfully',
                    ],
                    '404' => [
                        'description' => 'Project not found',
                    ],
                ],
            ],

            // 8. List Projects
            [
                'name' => 'list_projects',
                'method' => 'get',
                'summary' => 'List all projects',
                'operation_id' => 'listProjects',
                'parameters' => [],
                'responses' => [
                    '200' => [
                        'description' => 'List of projects',
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

            // Task Assignment Tools

            // 9. Assign User to Task
            [
                'name' => 'assign_user_to_task',
                'method' => 'post',
                'summary' => 'Assign a user to a task',
                'operation_id' => 'assignUserToTask',
                'parameters' => [
                    [
                        'name' => 'task_id',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'ID of the task',
                        ],
                    ],
                    [
                        'name' => 'user_id',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'ID of the user to assign',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'User assigned to task successfully',
                    ],
                    '404' => [
                        'description' => 'Task or user not found',
                    ],
                ],
            ],

            // 10. Unassign User from Task
            [
                'name' => 'unassign_user_from_task',
                'method' => 'post',
                'summary' => 'Unassign a user from a task',
                'operation_id' => 'unassignUserFromTask',
                'parameters' => [
                    [
                        'name' => 'task_id',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'ID of the task',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'User unassigned from task successfully',
                    ],
                    '404' => [
                        'description' => 'Task not found',
                    ],
                ],
            ],

            // 11. Reorder Tasks in Project
            [
                'name' => 'reorder_tasks_in_project',
                'method' => 'post',
                'summary' => 'Reorder tasks within a project',
                'operation_id' => 'reorderTasksInProject',
                'parameters' => [
                    [
                        'name' => 'project_id',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'ID of the project',
                        ],
                    ],
                    [
                        'name' => 'task_orders',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'integer',
                                'description' => 'Task IDs in the new order',
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Tasks reordered successfully',
                    ],
                    '400' => [
                        'description' => 'Failed to reorder tasks',
                    ],
                ],
            ],

            // Appointment Management Tools

            // 12. List Appointments
            [
                'name' => 'list_appointments',
                'method' => 'get',
                'summary' => 'List all appointments',
                'operation_id' => 'listAppointments',
                'parameters' => [],
                'responses' => [
                    '200' => [
                        'description' => 'List of appointments',
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

            // 13. Add Appointment
            [
                'name' => 'add_appointment',
                'method' => 'post',
                'summary' => 'Add a new appointment',
                'operation_id' => 'addAppointment',
                'parameters' => [
                    [
                        'name' => 'title',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'Title of the appointment',
                        ],
                    ],
                    [
                        'name' => 'description',
                        'in' => 'body',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'Description of the appointment',
                        ],
                    ],
                    [
                        'name' => 'start_time',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'format' => 'date-time',
                            'description' => 'Start time in ISO 8601 format',
                        ],
                    ],
                    [
                        'name' => 'end_time',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'format' => 'date-time',
                            'description' => 'End time in ISO 8601 format',
                        ],
                    ],
                    [
                        'name' => 'all_day',
                        'in' => 'body',
                        'required' => false,
                        'schema' => [
                            'type' => 'boolean',
                            'description' => 'Is it an all-day appointment?',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Appointment added successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => ['type' => 'object'],
                            ],
                        ],
                    ],
                    '400' => [
                        'description' => 'Failed to add appointment',
                    ],
                ],
            ],

            // 14. View Appointment
            [
                'name' => 'view_appointment',
                'method' => 'get',
                'summary' => 'View a specific appointment by ID',
                'operation_id' => 'viewAppointment',
                'parameters' => [
                    [
                        'name' => 'appointment_id',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'ID of the appointment',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Appointment details',
                        'content' => [
                            'application/json' => [
                                'schema' => ['type' => 'object'],
                            ],
                        ],
                    ],
                    '404' => [
                        'description' => 'Appointment not found',
                    ],
                ],
            ],

            // 15. Update Appointment
            [
                'name' => 'update_appointment',
                'method' => 'put',
                'summary' => 'Update an existing appointment',
                'operation_id' => 'updateAppointment',
                'parameters' => [
                    [
                        'name' => 'appointment_id',
                        'in' => 'body',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'ID of the appointment to update',
                        ],
                    ],
                    [
                        'name' => 'title',
                        'in' => 'body',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'New title of the appointment',
                        ],
                    ],
                    [
                        'name' => 'description',
                        'in' => 'body',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'description' => 'New description of the appointment',
                        ],
                    ],
                    [
                        'name' => 'start_time',
                        'in' => 'body',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'format' => 'date-time',
                            'description' => 'New start time',
                        ],
                    ],
                    [
                        'name' => 'end_time',
                        'in' => 'body',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'format' => 'date-time',
                            'description' => 'New end time',
                        ],
                    ],
                    [
                        'name' => 'all_day',
                        'in' => 'body',
                        'required' => false,
                        'schema' => [
                            'type' => 'boolean',
                            'description' => 'Is it an all-day appointment?',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Appointment updated successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => ['type' => 'object'],
                            ],
                        ],
                    ],
                    '404' => [
                        'description' => 'Appointment not found',
                    ],
                ],
            ],

            // 16. Delete Appointment
            [
                'name' => 'delete_appointment',
                'method' => 'delete',
                'summary' => 'Delete an appointment',
                'operation_id' => 'deleteAppointment',
                'parameters' => [
                    [
                        'name' => 'appointment_id',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                            'description' => 'ID of the appointment to delete',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Appointment deleted successfully',
                    ],
                    '404' => [
                        'description' => 'Appointment not found',
                    ],
                ],
            ],

            // Add any additional tools here...
        ];

        foreach ($tools as $toolData) {
            Tool::create([
                'name' => $toolData['name'],
                'method' => $toolData['method'],
                'summary' => $toolData['summary'],
                'operation_id' => $toolData['operation_id'],
                'parameters' => json_encode($toolData['parameters']),
                'responses' => json_encode($toolData['responses']),
            ]);
        }
    }
}
