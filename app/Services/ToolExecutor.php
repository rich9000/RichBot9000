<?php

namespace App\Services;

use App\Models\TicketSummary;
use App\Services\TroubleTicketService;

use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client;
use App\Services\OpenAIAssistant;
use App\Models\User;
use App\Models\Task;
use App\Models\Project;
use App\Models\Appointment;
use App\Models\Display;
use Illuminate\Support\Facades\Log;


class ToolExecutor
{
    public int $auth_user_id = 1;
    private $disk = false;

    public function __construct()
    {
        $this->auth_user_id = 1;
        $this->disk = Storage::disk('richbot_sandbox');

    }


    /**
     * Get current weather information.
     *
     * @param array $arguments
     * @return array
     */
    public function get_current_weather($arguments)
    {
        $format = $arguments['format'] ?? 'celsius';
        $location = $arguments['location'] ?? null;

        if (!$location) {
            return ['success' => false, 'error' => 'Missing required parameter: location'];
        }

        try {
            $apiKey = env('OPENWEATHER_API_KEY');
            if (!$apiKey) {
                throw new Exception("OpenWeather API key not set in .env.");
            }

            $units = $format === 'fahrenheit' ? 'imperial' : 'metric';

            $url = "http://api.openweathermap.org/data/2.5/weather?q=" . urlencode($location) . "&appid={$apiKey}&units={$units}";
            $response = file_get_contents($url);
            if ($response === false) {
                throw new Exception("Failed to fetch weather information.");
            }
            $data = json_decode($response, true);
            if (isset($data['main']['temp']) && isset($data['weather'][0]['description'])) {
                $weatherInfo = "Temperature: {$data['main']['temp']}°" . strtoupper(substr($units, 0, 1)) . "\nConditions: {$data['weather'][0]['description']}";
            } else {
                $weatherInfo = 'No weather information available.';
            }

            return [
                'success' => true,
                'data' => $weatherInfo,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate an image based on a prompt.
     *
     * @param array $arguments
     * @return array
     */
    public function generate_image($arguments)
    {
        $prompt = $arguments['prompt'] ?? null;
        $size = $arguments['size'] ?? '256x256';
        $target_path = $arguments['target_path'] ?? null;

        if (!$prompt || !$target_path) {
            return ['success' => false, 'error' => 'Missing required parameters: prompt, target_path'];
        }

        try {
            $openAiAssistant = new OpenAIAssistant();
            $url = $openAiAssistant->generateImageUrl($prompt, $size);
            $imageContent = file_get_contents($url);

            if ($imageContent === false) {
                return ['success' => false, 'error' => 'Failed to download the image from the generated URL.'];
            }

            $this->disk->put($target_path, $imageContent);

            return [
                'success' => true,
                'message' => 'Image generated and saved successfully!',
                'public_url' => $url,
                'file_path' => $target_path,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update display with new HTML content.
     *
     * @param array $arguments
     * @return array
     */
    public function update_display($arguments)
    {
        $display_name = $arguments['display_name'] ?? null;
        $html_content = $arguments['html_content'] ?? null;

        if (!$display_name || !$html_content) {
            return ['success' => false, 'error' => 'Missing required parameters: display_name, html_content'];
        }

        try {
            $path = '/public/richbotdisplay/displays/' . $display_name . '.html';
            $this->disk->put($path, $html_content);

            return [
                'success' => true,
                'message' => 'Display updated successfully!',
                'path' => $path,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // List all users
    public function list_users()
    {
        $users = User::all();
        return ['success' => true, 'data' => $users];
    }

    // Add a new user
    public function add_user($arguments)
    {
        $validator = Validator::make($arguments, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'error' => $validator->errors()->all()];
        }

        $validated = $validator->validated();
        $validated['password'] = bcrypt($validated['password']);

        try {
            $user = User::create($validated);
            return ['success' => true, 'data' => $user];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to add user: ' . $e->getMessage()];
        }
    }

    // View a specific user by ID
    public function view_user($arguments)
    {
        $id = $arguments['id'] ?? null;

        if (!$id) {
            return ['success' => false, 'error' => 'Missing required parameter: id'];
        }

        $user = User::find($id);

        if ($user) {
            return ['success' => true, 'data' => $user];
        }

        return ['success' => false, 'error' => 'User not found'];
    }

    // Delete a user by ID
    public function delete_user($arguments)
    {
        $id = $arguments['id'] ?? null;

        if (!$id) {
            return ['success' => false, 'error' => 'Missing required parameter: id'];
        }

        $user = User::find($id);

        if ($user) {
            try {
                $user->delete();
                return ['success' => true, 'message' => 'User deleted successfully'];
            } catch (Exception $e) {
                return ['success' => false, 'error' => 'Failed to delete user: ' . $e->getMessage()];
            }
        }

        return ['success' => false, 'error' => 'User not found'];
    }

    // Download a file from the server
    public function download_file($arguments)
    {
        $filePath = $arguments['filePath'] ?? null;

        if (!$filePath) {
            return ['success' => false, 'error' => 'Missing required parameter: filePath'];
        }

        if ($this->disk->exists($filePath)) {
            $content = $this->disk->get($filePath);
            return ['success' => true, 'data' => $content];
        }

        return ['success' => false, 'error' => 'File not found'];
    }

    // Delete a file from the server
    public function delete_file($arguments)
    {
        $filePath = $arguments['filePath'] ?? null;

        if (!$filePath) {
            return ['success' => false, 'error' => 'Missing required parameter: filePath'];
        }

        if ($this->disk->exists($filePath)) {
            $this->disk->delete($filePath);
            return ['success' => true, 'message' => 'File deleted successfully'];
        }

        return ['success' => false, 'error' => 'File not found'];
    }

    // List all files in a specified directory
    public function list_files($arguments)
    {
        $directory = $arguments['directory'] ?? '/';

        if ($this->disk->exists($directory)) {
            $files = $this->disk->files($directory);
            return ['success' => true, 'data' => $files];
        }

        return ['success' => false, 'error' => 'Directory not found'];
    }

    // List all folders in a specified directory
    public function list_folders($arguments)
    {
        $directory = $arguments['directory'] ?? null;

        if (!$directory) {
            return ['success' => false, 'error' => 'Missing required parameter: directory'];
        }

        if ($this->disk->exists($directory)) {
            $folders = $this->disk->directories($directory);
            return ['success' => true, 'data' => $folders];
        }

        return ['success' => false, 'error' => 'Directory not found'];
    }

    // Create a new directory
    public function create_directory($arguments)
    {
        $directory = $arguments['directory'] ?? null;

        if (!$directory) {
            return ['success' => false, 'error' => 'Missing required parameter: directory'];
        }

        if (!$this->disk->exists($directory)) {
            $this->disk->makeDirectory($directory);
            return ['success' => true, 'message' => 'Directory created successfully'];
        }

        return ['success' => false, 'error' => 'Directory already exists'];
    }

    // Delete a directory
    public function delete_directory($arguments)
    {
        $directory = $arguments['directory'] ?? null;

        if (!$directory) {
            return ['success' => false, 'error' => 'Missing required parameter: directory'];
        }

        if ($this->disk->exists($directory)) {
            $this->disk->deleteDirectory($directory);
            return ['success' => true, 'message' => 'Directory deleted successfully'];
        }

        return ['success' => false, 'error' => 'Directory not found'];
    }

    // Save text content to a specified file, replacing existing content
    public function put_text($arguments)
    {
        $filePath = $arguments['filePath'] ?? null;
        $content = $arguments['content'] ?? null;

        if (!$filePath || !$content) {
            return ['success' => false, 'error' => 'Missing required parameters: filePath, content'];
        }

        try {
            $this->disk->put($filePath, $content);
            return ['success' => true, 'message' => 'Content saved successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to save content: ' . $e->getMessage()];
        }
    }

    // Append text content to a specified file
    public function append_text($arguments)
    {
        $filePath = $arguments['filePath'] ?? null;
        $content = $arguments['content'] ?? null;

        if (!$filePath || !$content) {
            return ['success' => false, 'error' => 'Missing required parameters: filePath, content'];
        }

        try {
            $this->disk->append($filePath, $content);
            return ['success' => true, 'message' => 'Content appended successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to append content: ' . $e->getMessage()];
        }
    }

    // Edit an existing content
    public function edit($arguments)
    {
        $filePath = $arguments['filePath'] ?? null;
        $content = $arguments['content'] ?? null;

        if (!$filePath || !$content) {
            return ['success' => false, 'error' => 'Missing required parameters: filePath, content'];
        }

        if ($this->disk->exists($filePath)) {
            try {
                $this->disk->put($filePath, $content);
                return ['success' => true, 'message' => 'Content edited successfully'];
            } catch (Exception $e) {
                return ['success' => false, 'error' => 'Failed to edit content: ' . $e->getMessage()];
            }
        }

        return ['success' => false, 'error' => 'File not found'];
    }

    // Send an email to a specified address
    public function send_email($arguments)
    {
        $to = $arguments['to'] ?? null;
        $subject = $arguments['subject'] ?? null;
        $body = $arguments['body'] ?? null;

        if (!$to || !$subject || !$body) {
            return ['success' => false, 'error' => 'Missing required parameters: to, subject, body'];
        }

        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });

            return ['success' => true, 'message' => 'Email sent successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to send email: ' . $e->getMessage()];
        }
    }

    // Send an SMS to a specified number
    public function send_sms($arguments)
    {

        $user = request()->user();






        $to = $arguments['to'] ?? null;
        $body = $arguments['body'] ?? null;


        $body = "From: $user->name $user->email\n$body";


        if (!$to || !$body) {
            return ['success' => false, 'error' => 'Missing required parameters: to, body'];
        }

        $sid = env('TWILIO_SID');
        $token = env('TWILIO_TOKEN');
        $twilioNumber = env('TWILIO_FROM');

        $client = new Client($sid, $token);

        try {
            $message = $client->messages->create(
                $to,
                [
                    'from' => $twilioNumber,
                    'body' => $body,
                ]
            );

            return ['success' => true, 'message' => 'SMS sent successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to send SMS: ' . $e->getMessage()];
        }
    }

    public function email_rich($arguments)
    {
        $to = 'richcarroll@gmail.com';
        $subject = $arguments['subject'] ?? null;


        $body = $arguments['body'] ?? null;

        $user = request()->user();
        
        $user = request()->user();
        if($user){

            $body = "From: $user->name $user->email\n$body";

        } else{

            $body = "From: Richbot9000 (cronbot)\n$body";

        }
        
        

        if (!$to || !$subject || !$body) {
            return ['success' => false, 'error' => 'Missing required parameters: to, subject, body'];
        }

        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });

            return ['success' => true, 'message' => 'Email sent successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to send email: ' . $e->getMessage()];
        }
    }



    // Send an SMS to a specified number
    public function sms_rich($arguments)
    {

        $body = $arguments['message'] ?? null;

        $to = '7852881144';


        if (!$body) {
            return ['success' => false, 'error' => 'Missing required parameters: to, body'];
        }

        $user = request()->user();
        if($user){

            $body = "From: $user->name $user->email\n$body";

        } else{

            $body = "From: RichBot (cronbot)\n$body";

        }


        $sid = env('TWILIO_SID');
        $token = env('TWILIO_TOKEN');
        $twilioNumber = env('TWILIO_FROM');

        $client = new Client($sid, $token);

        try {
            $message = $client->messages->create(
                $to,
                [
                    'from' => $twilioNumber,
                    'body' => $body,
                ]
            );

            return ['success' => true, 'message' => 'SMS sent successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to send SMS: ' . $e->getMessage()];
        }
    }

    // Create a new task
    public function create_task($arguments)
    {
        $title = $arguments['title'] ?? null;
        $description = $arguments['description'] ?? null;
        $project_id = $arguments['project_id'] ?? null;
        $order = $arguments['order'] ?? null;

        if (!$title) {
            return ['success' => false, 'error' => 'Missing required parameter: title'];
        }

        try {
            $task = Task::create([
                'title' => $title,
                'description' => $description,
                'user_id' => $this->auth_user_id,
                'project_id' => $project_id,
                'order' => $order,
            ]);

            return ['success' => true, 'data' => $task];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to create task: ' . $e->getMessage()];
        }
    }

    // Update an existing task
    public function update_task($arguments)
    {
        $task_id = $arguments['task_id'] ?? null;

        if (!$task_id) {
            return ['success' => false, 'error' => 'Missing required parameter: task_id'];
        }

        $task = Task::find($task_id);
        if (!$task) {
            return ['success' => false, 'error' => 'Task not found'];
        }

        $fields = ['title', 'description', 'project_id', 'order'];

        foreach ($fields as $field) {
            if (isset($arguments[$field])) {
                $task->$field = $arguments[$field];
            }
        }

        try {
            $task->save();
            return ['success' => true, 'data' => $task];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to update task: ' . $e->getMessage()];
        }
    }

    // Delete a task
    public function delete_task($arguments)
    {
        $task_id = $arguments['task_id'] ?? null;

        if (!$task_id) {
            return ['success' => false, 'error' => 'Missing required parameter: task_id'];
        }

        $task = Task::find($task_id);
        if (!$task) {
            return ['success' => false, 'error' => 'Task not found'];
        }

        try {
            $task->delete();
            return ['success' => true, 'message' => 'Task deleted successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to delete task: ' . $e->getMessage()];
        }
    }

    // List tasks with optional filters
    public function list_tasks($arguments)
    {
        $query = Task::query();

        if (isset($arguments['project_id'])) {
            $query->where('project_id', $arguments['project_id']);
        }

        if (isset($arguments['assigned_user_id'])) {
            $query->whereHas('users', function ($q) use ($arguments) {
                $q->where('users.id', $arguments['assigned_user_id']);
            });
        }

        if (isset($arguments['creator_user_id'])) {
            $query->where('user_id', $arguments['creator_user_id']);
        }

        try {
            $tasks = $query->get();
            return ['success' => true, 'data' => $tasks];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to list tasks: ' . $e->getMessage()];
        }
    }

    // Create a new project
    public function create_project($arguments)
    {
        $name = $arguments['name'] ?? null;
        $description = $arguments['description'] ?? null;

        if (!$name) {
            return ['success' => false, 'error' => 'Missing required parameter: name'];
        }

        try {
            $project = Project::create([
                'name' => $name,
                'description' => $description,
            ]);

            return ['success' => true, 'data' => $project];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to create project: ' . $e->getMessage()];
        }
    }

    // Update an existing project
    public function update_project($arguments)
    {
        $project_id = $arguments['project_id'] ?? null;

        if (!$project_id) {
            return ['success' => false, 'error' => 'Missing required parameter: project_id'];
        }

        $project = Project::find($project_id);
        if (!$project) {
            return ['success' => false, 'error' => 'Project not found'];
        }

        $fields = ['name', 'description'];

        foreach ($fields as $field) {
            if (isset($arguments[$field])) {
                $project->$field = $arguments[$field];
            }
        }

        try {
            $project->save();
            return ['success' => true, 'data' => $project];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to update project: ' . $e->getMessage()];
        }
    }

    // Delete a project
    public function delete_project($arguments)
    {
        $project_id = $arguments['project_id'] ?? null;

        if (!$project_id) {
            return ['success' => false, 'error' => 'Missing required parameter: project_id'];
        }

        $project = Project::find($project_id);
        if (!$project) {
            return ['success' => false, 'error' => 'Project not found'];
        }

        try {
            $project->delete();
            return ['success' => true, 'message' => 'Project deleted successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to delete project: ' . $e->getMessage()];
        }
    }

    // List all projects
    public function list_projects($arguments)
    {
        try {
            $projects = Project::all();
            return ['success' => true, 'data' => $projects];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to list projects: ' . $e->getMessage()];
        }
    }

    // Assign a user to a task
    public function assign_user_to_task($arguments)
    {
        $task_id = $arguments['task_id'] ?? null;
        $user_id = $arguments['user_id'] ?? null;

        if (!$task_id || !$user_id) {
            return ['success' => false, 'error' => 'Missing required parameters: task_id, user_id'];
        }

        $task = Task::find($task_id);
        $user = User::find($user_id);

        if (!$task) {
            return ['success' => false, 'error' => 'Task not found'];
        }

        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }

        try {
            $task->users()->attach($user_id);
            return ['success' => true, 'message' => 'User assigned to task successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to assign user to task: ' . $e->getMessage()];
        }
    }

    // Unassign a user from a task
    public function unassign_user_from_task($arguments)
    {
        $task_id = $arguments['task_id'] ?? null;
        $user_id = $arguments['user_id'] ?? null;

        if (!$task_id || !$user_id) {
            return ['success' => false, 'error' => 'Missing required parameters: task_id, user_id'];
        }

        $task = Task::find($task_id);
        $user = User::find($user_id);

        if (!$task) {
            return ['success' => false, 'error' => 'Task not found'];
        }

        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }

        try {
            $task->users()->detach($user_id);
            return ['success' => true, 'message' => 'User unassigned from task successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to unassign user from task: ' . $e->getMessage()];
        }
    }

    // Reorder tasks within a project
    public function reorder_tasks_in_project($arguments)
    {
        $project_id = $arguments['project_id'] ?? null;
        $task_orders = $arguments['task_orders'] ?? null;

        if (!$project_id || !$task_orders) {
            return ['success' => false, 'error' => 'Missing required parameters: project_id, task_orders'];
        }

        $project = Project::find($project_id);

        if (!$project) {
            return ['success' => false, 'error' => 'Project not found'];
        }

        try {
            foreach ($task_orders as $order => $task_id) {
                $task = Task::find($task_id);
                if ($task && $task->project_id == $project_id) {
                    $task->order = $order;
                    $task->save();
                } else {
                    return ['success' => false, 'error' => "Task ID {$task_id} not found in project"];
                }
            }

            return ['success' => true, 'message' => 'Tasks reordered successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to reorder tasks: ' . $e->getMessage()];
        }
    }

    // List all appointments
    public function list_appointments($arguments)
    {
        try {
            $appointments = Appointment::with('user')->orderBy('start_time', 'asc')->get();
            return ['success' => true, 'data' => $appointments];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to list appointments: ' . $e->getMessage()];
        }
    }

    // Add a new appointment
    public function add_appointment($arguments)
    {
        $title = $arguments['title'] ?? null;
        $description = $arguments['description'] ?? null;
        $start_time = $arguments['start_time'] ?? null;
        $end_time = $arguments['end_time'] ?? null;
        $all_day = $arguments['all_day'] ?? false;

        if (!$title || !$start_time || !$end_time) {
            return ['success' => false, 'error' => 'Missing required parameters: title, start_time, end_time'];
        }

        try {
            $appointment = Appointment::create([
                'user_id' => $this->auth_user_id,
                'title' => $title,
                'description' => $description,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'all_day' => $all_day,
            ]);

            return ['success' => true, 'data' => $appointment];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to add appointment: ' . $e->getMessage()];
        }
    }

    // View a specific appointment by ID
    public function view_appointment($arguments)
    {
        $appointment_id = $arguments['appointment_id'] ?? null;

        if (!$appointment_id) {
            return ['success' => false, 'error' => 'Missing required parameter: appointment_id'];
        }

        $appointment = Appointment::with('user')->find($appointment_id);

        if ($appointment) {
            return ['success' => true, 'data' => $appointment];
        }

        return ['success' => false, 'error' => 'Appointment not found'];
    }

    // Update an existing appointment
    public function update_appointment($arguments)
    {
        $appointment_id = $arguments['appointment_id'] ?? null;

        if (!$appointment_id) {
            return ['success' => false, 'error' => 'Missing required parameter: appointment_id'];
        }

        $appointment = Appointment::find($appointment_id);

        if (!$appointment) {
            return ['success' => false, 'error' => 'Appointment not found'];
        }

        $fields = ['title', 'description', 'start_time', 'end_time', 'all_day'];

        foreach ($fields as $field) {
            if (isset($arguments[$field])) {
                $appointment->$field = $arguments[$field];
            }
        }

        try {
            $appointment->save();
            return ['success' => true, 'data' => $appointment];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to update appointment: ' . $e->getMessage()];
        }
    }

    // Delete an appointment by ID
    public function delete_appointment($arguments)
    {
        $appointment_id = $arguments['appointment_id'] ?? null;

        if (!$appointment_id) {
            return ['success' => false, 'error' => 'Missing required parameter: appointment_id'];
        }

        $appointment = Appointment::find($appointment_id);

        if ($appointment) {
            try {
                $appointment->delete();
                return ['success' => true, 'message' => 'Appointment deleted successfully'];
            } catch (Exception $e) {
                return ['success' => false, 'error' => 'Failed to delete appointment: ' . $e->getMessage()];
            }
        }

        return ['success' => false, 'error' => 'Appointment not found'];
    }

    // Read the contents of a file
    public function read_file($arguments)
    {
        $filePath = $arguments['filePath'] ?? null;

        if (!$filePath) {
            return ['success' => false, 'error' => 'Missing required parameter: filePath'];
        }

        if ($this->disk->exists($filePath)) {
            $content = $this->disk->get($filePath);
            return ['success' => true, 'data' => $content];
        }

        return ['success' => false, 'error' => 'File not found'];
    }

    // Move a file to a new location
    public function move_file($arguments)
    {
        $sourcePath = $arguments['sourcePath'] ?? null;
        $destinationPath = $arguments['destinationPath'] ?? null;

        if (!$sourcePath || !$destinationPath) {
            return ['success' => false, 'error' => 'Missing required parameters: sourcePath, destinationPath'];
        }

        if ($this->disk->exists($sourcePath)) {
            $this->disk->move($sourcePath, $destinationPath);
            return ['success' => true, 'message' => 'File moved successfully'];
        }

        return ['success' => false, 'error' => 'Source file not found'];
    }

    // Copy a file to a new location
    public function copy_file($arguments)
    {
        $sourcePath = $arguments['sourcePath'] ?? null;
        $destinationPath = $arguments['destinationPath'] ?? null;

        if (!$sourcePath || !$destinationPath) {
            return ['success' => false, 'error' => 'Missing required parameters: sourcePath, destinationPath'];
        }

        if ($this->disk->exists($sourcePath)) {
            $this->disk->copy($sourcePath, $destinationPath);
            return ['success' => true, 'message' => 'File copied successfully'];
        }

        return ['success' => false, 'error' => 'Source file not found'];
    }


    // Read the contents of a file
    public function update_main_display($arguments)
    {
        $html = $arguments['html'] ?? null;

        if (!$html) {
            return ['success' => false, 'error' => 'Missing required parameter: html'];
        }


        $display = Display::where(['name'=>'main','status'=>1])->first();
        $display->status = 0;
        $display->save();

        $new_display = new Display();
        $new_display->name='main';
        $new_display->content=$html;
        $new_display->status=1;
        $new_display->save();


        return ['success' => true, 'message' => 'main display updated successfully.'];

    }
    // Add any additional methods as needed...


    public function upload_file($arguments)
    {
        $fileContent = $arguments['fileContent'] ?? null;
        $destination = $arguments['destination'] ?? null;
        $userId = $arguments['user_id'] ?? null;
        $conversationId = $arguments['conversation_id'] ?? null;

        if (!$fileContent || !$destination || !$userId || !$conversationId) {
            return ['success' => false, 'error' => 'Missing required parameters: fileContent, destination, user_id, conversation_id'];
        }

        try {
            // Get original content from disk, if it exists
            $originalContent = $this->disk->exists($destination) ? $this->disk->get($destination) : null;

            // Store the request in the database
            DB::table('file_change_requests')->insert([
                'user_id' => $userId,
                'conversation_id' => $conversationId,
                'file_path' => $destination,
                'new_content' => $fileContent,
                'original_content' => $originalContent,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['success' => true, 'message' => 'File upload request submitted for approval'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to upload file: ' . $e->getMessage()];
        }
    }

        // 2. Create a new folder on the server
        public function create_folder($arguments)
        {
            $path = $arguments['path'] ?? null;

            if (!$path) {
                return ['success' => false, 'error' => 'Missing required parameter: path'];
            }

            if (!$this->disk->exists($path)) {
                $this->disk->makeDirectory($path);
                return ['success' => true, 'message' => 'Folder created successfully'];
            }

            return ['success' => false, 'error' => 'Folder already exists'];
        }

        // 3. Delete a folder from the server
        public function delete_folder($arguments)
        {
            $path = $arguments['path'] ?? null;

            if (!$path) {
                return ['success' => false, 'error' => 'Missing required parameter: path'];
            }

            if ($this->disk->exists($path)) {
                $this->disk->deleteDirectory($path);
                return ['success' => true, 'message' => 'Folder deleted successfully'];
            }

            return ['success' => false, 'error' => 'Folder not found'];
        }

        // 4. List contents of a directory
        public function list_directory_contents($arguments)
        {
            $path = $arguments['path'] ?? '/';

            if ($this->disk->exists($path)) {
                $files = $this->disk->files($path);
                $directories = $this->disk->directories($path);
                return ['success' => true, 'files' => $files, 'directories' => $directories];
            }

            return ['success' => false, 'error' => 'Directory not found'];
        }

        // 5. Search for files in a directory
        public function search_files($arguments)
        {
            $query = $arguments['query'] ?? null;
            $directory = $arguments['directory'] ?? '/';

            if (!$query) {
                return ['success' => false, 'error' => 'Missing required parameter: query'];
            }

            if ($this->disk->exists($directory)) {
                $allFiles = $this->disk->allFiles($directory);
                $matchedFiles = array_filter($allFiles, function ($file) use ($query) {
                    return stripos($file, $query) !== false;
                });

                return ['success' => true, 'data' => array_values($matchedFiles)];
            }

            return ['success' => false, 'error' => 'Directory not found'];
        }

        // 6. Send an email to multiple recipients
        public function send_bulk_email($arguments)
        {
            $recipients = $arguments['recipients'] ?? null;
            $subject = $arguments['subject'] ?? null;
            $body = $arguments['body'] ?? null;

            if (!$recipients || !$subject || !$body) {
                return ['success' => false, 'error' => 'Missing required parameters: recipients, subject, body'];
            }

            try {
                foreach ($recipients as $to) {
                    Mail::raw($body, function ($message) use ($to, $subject) {
                        $message->to($to)->subject($subject);
                    });
                }

                return ['success' => true, 'message' => 'Bulk emails sent successfully'];
            } catch (Exception $e) {
                return ['success' => false, 'error' => 'Failed to send bulk emails: ' . $e->getMessage()];
            }
        }

        // 7. Send a notification to users
        public function send_notification($arguments)
        {
            // Placeholder for send_notification method
            return ['success' => false, 'error' => 'Method send_notification not implemented yet.'];
        }

        // 8. Manage tasks (create, update, delete)
        public function manage_task($arguments)
        {
            $action = $arguments['action'] ?? null;

            if (!$action) {
                return ['success' => false, 'error' => 'Missing required parameter: action'];
            }

            switch ($action) {
                case 'create':
                    return $this->create_task($arguments);
                case 'update':
                    return $this->update_task($arguments);
                case 'delete':
                    return $this->delete_task($arguments);
                default:
                    return ['success' => false, 'error' => 'Invalid action specified'];
            }
        }

        // 9. Search tasks with optional filters
        public function search_tasks($arguments)
        {
            $query = Task::query();

            if (isset($arguments['project_id'])) {
                $query->where('project_id', $arguments['project_id']);
            }

            if (isset($arguments['assigned_user_id'])) {
                $query->whereHas('users', function ($q) use ($arguments) {
                    $q->where('users.id', $arguments['assigned_user_id']);
                });
            }

            if (isset($arguments['query'])) {
                $searchTerm = $arguments['query'];
                $query->where('title', 'like', "%$searchTerm%");
            }

            try {
                $tasks = $query->get();
                return ['success' => true, 'data' => $tasks];
            } catch (Exception $e) {
                return ['success' => false, 'error' => 'Failed to search tasks: ' . $e->getMessage()];
            }
        }

        // 10. Manage projects (create, update, delete)
        public function manage_project($arguments)
        {
            $action = $arguments['action'] ?? null;

            if (!$action) {
                return ['success' => false, 'error' => 'Missing required parameter: action'];
            }

            switch ($action) {
                case 'create':
                    return $this->create_project($arguments);
                case 'update':
                    return $this->update_project($arguments);
                case 'delete':
                    return $this->delete_project($arguments);
                default:
                    return ['success' => false, 'error' => 'Invalid action specified'];
            }
        }

        // 11. Manage users (create, update, delete)
        public function manage_user($arguments)
        {
            // Placeholder for manage_user method
            return ['success' => false, 'error' => 'Method manage_user not implemented yet.'];
        }

        // 12. Manage appointments (create, update, delete)
        public function manage_appointment($arguments)
        {
            $action = $arguments['action'] ?? null;

            if (!$action) {
                return ['success' => false, 'error' => 'Missing required parameter: action'];
            }

            switch ($action) {
                case 'create':
                    return $this->add_appointment($arguments);
                case 'update':
                    return $this->update_appointment($arguments);
                case 'delete':
                    return $this->delete_appointment($arguments);
                default:
                    return ['success' => false, 'error' => 'Invalid action specified'];
            }
        }

        // 13. Search appointments with optional filters
        public function search_appointments($arguments)
        {
            $query = Appointment::query();

            if (isset($arguments['user_id'])) {
                $query->where('user_id', $arguments['user_id']);
            }

            if (isset($arguments['query'])) {
                $searchTerm = $arguments['query'];
                $query->where('title', 'like', "%$searchTerm%");
            }

            try {
                $appointments = $query->get();
                return ['success' => true, 'data' => $appointments];
            } catch (Exception $e) {
                return ['success' => false, 'error' => 'Failed to search appointments: ' . $e->getMessage()];
            }
        }

        // 14. Invite users to an appointment
        public function invite_users_to_appointment($arguments)
        {
            // Placeholder for invite_users_to_appointment method
            return ['success' => false, 'error' => 'Method invite_users_to_appointment not implemented yet.'];
        }

        // 15. List all appointments for a user
        public function list_user_appointments($arguments)
        {
            $user_id = $arguments['user_id'] ?? null;

            if (!$user_id) {
                return ['success' => false, 'error' => 'Missing required parameter: user_id'];
            }

            try {
                $appointments = Appointment::where('user_id', $user_id)->get();
                return ['success' => true, 'data' => $appointments];
            } catch (Exception $e) {
                return ['success' => false, 'error' => 'Failed to list user appointments: ' . $e->getMessage()];
            }
        }

        // 16. Log an event in the system
        public function log_event($arguments)
        {
            // Placeholder for log_event method
            return ['success' => false, 'error' => 'Method log_event not implemented yet.'];
        }

        // 17. Retrieve system logs
        public function get_system_logs($arguments)
        {
            // Placeholder for get_system_logs method
            return ['success' => false, 'error' => 'Method get_system_logs not implemented yet.'];
        }

        // 18. Set permissions for a user or role
        public function set_permissions($arguments)
        {
            // Placeholder for set_permissions method
            return ['success' => false, 'error' => 'Method set_permissions not implemented yet.'];
        }

        // 19. Get permissions for a user or role
        public function get_permissions($arguments)
        {
            // Placeholder for get_permissions method
            return ['success' => false, 'error' => 'Method get_permissions not implemented yet.'];
        }

        // 20. Export data from the system
        public function export_data($arguments)
        {
            // Placeholder for export_data method
            return ['success' => false, 'error' => 'Method export_data not implemented yet.'];
        }

        // 21. Import data into the system
        public function import_data($arguments)
        {
            // Placeholder for import_data method
            return ['success' => false, 'error' => 'Method import_data not implemented yet.'];
        }

        // 22. Report an error or issue
        public function report_error($arguments)
        {
            // Placeholder for report_error method
            return ['success' => false, 'error' => 'Method report_error not implemented yet.'];
        }

        // 23. Send a response to a user query
        public function respond_to_user($arguments)
        {
            // Placeholder for respond_to_user method
            return ['success' => false, 'error' => 'Method respond_to_user not implemented yet.'];
        }

        // 24. Authenticate a user
        public function user_auth($arguments)
        {
            // Placeholder for user_auth method
            return ['success' => false, 'error' => 'Method user_auth not implemented yet.'];
        }

        // 25. Run system diagnostics
        public function diagnostic_tool($arguments)
        {
            // Placeholder for diagnostic_tool method
            return ['success' => false, 'error' => 'Method diagnostic_tool not implemented yet.'];
        }

        // 26. Interact with the calendar system
        public function calendar_tool($arguments)
        {
            // Placeholder for calendar_tool method
            return ['success' => false, 'error' => 'Method calendar_tool not implemented yet.'];
        }

        // 27. Access the knowledge base
        public function knowledge_base($arguments)
        {
            // Placeholder for knowledge_base method
            return ['success' => false, 'error' => 'Method knowledge_base not implemented yet.'];
        }

        // 28. Fetch data from external sources
        public function data_fetch($arguments)
        {
            // Placeholder for data_fetch method
            return ['success' => false, 'error' => 'Method data_fetch not implemented yet.'];
        }

        // 29. Enhance a prompt for better results
        public function prompt_enhancer($arguments)
        {
            // Placeholder for prompt_enhancer method
            return ['success' => false, 'error' => 'Method prompt_enhancer not implemented yet.'];
        }

    // 29. Enhance a prompt for better results
    public function create_ticket($arguments)
    {

        $ticket = TroubleTicketService::createFromJson($arguments);
        // Placeholder for prompt_enhancer method
        return ['success' => true, 'ticket' => $ticket];

    }

    public function get_new_ticket()
    {


        $ticket = TroubleTicketService::getNewTicket();
        // Placeholder for prompt_enhancer method
        return ['success' => true, 'ticket' => $ticket];

    }
    public function summerize_ticket($arguements)
    {


        Log::info('summerize_text:'.json_encode($arguements));

        $ticket = new TicketSummary(['assistant_name'=>$arguements['assistant_name'],'ticket_id'=>$arguements['ticket_id'],'summary_text'=>$arguements['summary_text']]);
$ticket->save();

        Log::info(json_encode($ticket));

        return ['success' => true, 'ticket_summary' => $ticket];

    }


    // 30. Search for customer records
    public function address_search($arguments)
    {

        $account = false;
        $search_string = $arguments['search_string'];
        //   $search_field = $arguments['search_field'];
        $search_field = 'address';


        $rainbow = new RainbowDashService();
        $login_info = $rainbow->login('rich@rainbowtel.com','richlikestowork');
        if($login_info['token']){
            $token = $login_info['token'];

        } else {

            $token = false;
        }

        $admin_check = $rainbow->getUserData($token);


        if($search_field == 'address'){

            $account = $rainbow->customerSearch($token,'address',$search_string);
            Log::error("Customer Search Type: Account ".print_r($account,true));
            return ['success' => true, 'message' => 'User found!','account'=>$account];


        }

        Log::error("Customer Search Response: ");

        return ['success' => false, 'message' => 'User not found!'];


    }


        // 30. Search for customer records
        public function customer_search($arguments)
        {

            $account = false;
            $search_string = $arguments['search_string'];
         //   $search_field = $arguments['search_field'];
            $search_field = 'account';


            $rainbow = new RainbowDashService();
            $login_info = $rainbow->login('rich@rainbowtel.com','richlikestowork');
            if($login_info['token']){
                $token = $login_info['token'];

            } else {

                $token = false;
            }

            $admin_check = $rainbow->getUserData($token);


            if($search_field == 'account'){

                $account = $rainbow->customerSearch($token,'account',$search_string);
                Log::error("Customer Search Type: Account ".print_r($account,true));
                return ['success' => true, 'message' => 'User found!','account'=>$account];


            }

            Log::error("Customer Search Response: ");

            return ['success' => false, 'message' => 'User not found!'];


        }

        // 31. Verify customer CPNI
        public function customer_verify_cpni($arguments)
        {

            $account    = $arguments['account'];
            $answer     = $arguments['answer'];
            $question   = $arguments['question'];

            $rainbow = new RainbowDashService();
            $login_info = $rainbow->login('rich@rainbowtel.com','richlikestowork');
            if($login_info['token']){
                $token = $login_info['token'];
            } else {
                $token = false;
            }

            $admin_check = $rainbow->getUserData($token);

            $response = $rainbow->verifyCpniAnswer($token,$account,$question,$answer);

            Log::info($response['status']);

            if($response['status'] == 'success'){

                return ['success' => true, 'message' => 'Customer Verified.'];
            }

            // Placeholder for customer_verify_cpni method
            return ['success' => false, 'error' => 'Method customer_verify_cpni not implemented yet.'];
        }

        // 32. Answer a customer question
        public function customer_question($arguments)
        {
            // Placeholder for customer_question method
            return ['success' => false, 'error' => 'Method customer_question not implemented yet.'];
        }



    public function verifyToolDb(){

        $reflection = new \ReflectionClass(ToolExecutor::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $methodNames = [];

        foreach ($methods as $method) {
            if ($method->class === ToolExecutor::class) {
                $methodNames[] = $method->getName();
            }
        }


        $tools = Tool::all();

        $toolNames = $tools->pluck('name')->toArray();

        $missingMethods = array_diff($toolNames, $methodNames);
        $extraMethods = array_diff($methodNames, $toolNames);

if (!empty($missingMethods)) {
    echo "The following tools do not have corresponding methods in ToolExecutor:\n";
    echo implode(", ", $missingMethods) . "\n";
} else {
    echo "All tools have corresponding methods in ToolExecutor.\n";
}

if (!empty($extraMethods)) {
    echo "The following methods in ToolExecutor do not have corresponding tools in the database:\n";
    echo implode(", ", $extraMethods) . "\n";
} else {
    echo "All methods in ToolExecutor have corresponding tools in the database.\n";
}




    }



    function verifyToolMethods()
    {
        // Step 1: Retrieve all tools
        $tools = Tool::all();
        $toolNames = $tools->pluck('name')->toArray();

        // Step 2: Get all public methods from ToolExecutor
        $reflection = new \ReflectionClass(ToolExecutor::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        // Step 3: Filter methods declared in ToolExecutor only
        $methodNames = [];
        foreach ($methods as $method) {
            if ($method->class === ToolExecutor::class) {
                $methodNames[] = $method->getName();
            }
        }

        // Step 4: Compare tool names with method names
        $missingMethods = array_diff($toolNames, $methodNames);
        $extraMethods = array_diff($methodNames, $toolNames);

        // Step 5: Output the results
        if (!empty($missingMethods)) {
            echo "The following tools do not have corresponding methods in ToolExecutor:\n";
            echo implode(", ", $missingMethods) . "\n";
        } else {
            echo "All tools have corresponding methods in ToolExecutor.\n";
        }

        if (!empty($extraMethods)) {
            echo "The following methods in ToolExecutor do not have corresponding tools in the database:\n";
            echo implode(", ", $extraMethods) . "\n";
        } else {
            echo "All methods in ToolExecutor have corresponding tools in the database.\n";
        }
    }


}
