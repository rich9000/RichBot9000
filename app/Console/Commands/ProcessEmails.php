<?php

namespace App\Console\Commands;;


use App\Models\Assistant;
use App\Models\Email;
use App\Models\Project;
use App\Models\User;
use App\Services\OpenAIAssistant;
use Illuminate\Console\Command;
use DOMDocument;
use App\Models\Task;

class ProcessEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zz:processEmails  {email_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //

        $openai = new OpenAIAssistant();

        $true = true;
        while($true) {

            $assistants = $openai->list_assistants();
            if(!count($assistants)) {

                break;
            }

            $functions = [];

            $i = 0;

            foreach ($assistants as $assistant) {

                $openai->delete_assistant($assistant['id']);


                $i++;

                if ($i++ > 25) {
                    $i = 0;

                    break;


                }


            }
        }

        $userId = 1; // Assuming Rich Carroll has user_id 1
        $user = User::find(1);


        $contactId = 3;

        $ass = Assistant::find(25);

        $assistant_id = $ass->createOpenAiAssistant();
        dump("Assistant ID: {$assistant_id}");

        $thread_id = $openai->create_thread();
        dump("Thread ID: {$thread_id}");

        $projects = Project::where('user_id',1)->with('tasks')->get();

        // Fetch all emails for Rich Carroll
       // $emails = Email::where('user_id', $userId)->with(['contacts'])->get();

        $emailId = $this->argument('email_id');
        if($emailId){

            $email = Email::where('id' ,$emailId)->first();
            $emails = [$email];




        } else {


            $emails = Email::where('user_id', $userId)
                ->whereHas('contacts', function ($query) use ($contactId) {
                    $query->where('contact_id', $contactId);
                })
                ->where('processed',false)
                ->with('contacts')
                ->get();




        }








        foreach ($emails as $email) {



            //$openai->add_message($thread_id, $prompt, 'user');

            $this->info("Processing Email ID: {$email->id}");

            $body = $email->body;

            if(!$body) {

                echo "NO BODY!!!!!!!! $body\n";
                continue;

            }

            $projects = Project::where('user_id',1)->with('tasks')->get();

            $prompt = "Current Projects: \n\n";
            foreach ($projects as $project){

                $prompt .= "Project Id: {$project->id}\n";
                $prompt .= "Name: {$project->name}\n";
                $prompt .= "Description: {$project->description}\n";
                $prompt .= "Task Count: {$project->tasks->count()}\n";
            //    foreach ($project->tasks as $task){
//                    $prompt .= "    Task ID: {$task->id}\n";
  //                  $prompt .= "    Task Name: {$task->title}\n";
    //                $prompt .= "    Task Description: {$task->description}\n\n";
            //    }

            }

            if(!count($projects)){

                $prompt .= "No Current Projects\n";
            }
            $prompt .= "\n";

            $prompt .= "Process Email Start Email ID: {$email->id}:\n";

            $email->load('contacts');

            foreach ($email->contacts as $contact){

                $prompt .= "{$contact->pivot->context}:$contact->email:$contact->name\n";

            }


         $clean_body = $this->convertHtmlToShortText($body);
         $prompt .= "Email Subject:\n$email->subject\n";
         $prompt .= "Email Message Body:\n$clean_body\n";



         echo "\n\n\n\n$prompt\n\n\n\n";


            $openai->add_message($thread_id, $prompt, 'user');

            $run_id = $openai->create_run($thread_id, $assistant_id);
            dump("Run ID: {$run_id}");

            // Poll for completion
            $retryDelay = 3;
            $maxRetries = 3;
            $retryCount = 0;

            do {

                sleep($retryDelay);
                try {
                    $run = $openai->get_run($thread_id, $run_id);
                } catch (\Exception $e) {
                    \Log::error("Error retrieving run (attempt $retryCount): " . $e->getMessage());
                    if (++$retryCount > $maxRetries) {
                        throw $e;
                    }
                    continue;
                }

                if ($run['status'] == 'requires_action') {

                    $calls = $run['required_action']['submit_tool_outputs']['tool_calls'];
                    $outputs = [];
                    $log_entry = '';

                    $success_called = false;
                    $stage_success_called = false;
                    $assistant_success_called = false;

                    // dump($calls);

                    foreach ($calls as $call) {

                        echo "Call\n";

                        //var_dump($call);

                        $method_name = $call['function']['name'];
                        $method_args = json_decode($call['function']['arguments'], true);

                        echo "$method_name,".json_encode($method_args);
                        echo "\n";

                        $methodName = $method_name;
                        $methodArgs = $method_args;


                        if ($methodName === 'create_project') {
                            // Create a project
                            $project = new Project();
                            $project->name = $methodArgs['name'];
                            $project->user_id = $userId;
                            $project->description = $methodArgs['description'];
                            $project->save();

                            $outputs[] = [
                                'tool_call_id' => $call['id'],
                                'output' => json_encode(['message' => 'Project Created', 'project_id' => $project->id, 'project' => $project])
                            ];

                        } elseif ($methodName === 'update_email_info') {
                            // Fetch the email by email_id
                            $email = Email::find($methodArgs['email_id']);

                            if ($email) {
                                // Update the specified field with the given value
                                $field = $methodArgs['field'];
                                $value = $methodArgs['value'];

                                // Ensure the field is valid to prevent mass assignment issues
                                if (in_array($field, ['project_id', 'task_id', 'user_id','information'])) {
                                    $email->$field = $value;
                                    $email->save();

                                    $outputs[] = [
                                        'tool_call_id' => $call['id'],
                                        'output' => json_encode(['message' => 'Email Info Updated', 'email_id' => $email->id, 'field' => $field, 'value' => $value])
                                    ];
                                } else {
                                    $outputs[] = [
                                        'tool_call_id' => $call['id'],
                                        'output' => json_encode(['message' => 'Invalid Field Specified', 'field' => $field])
                                    ];
                                }
                            } else {
                                $outputs[] = [
                                    'tool_call_id' => $call['id'],
                                    'output' => json_encode(['message' => 'Email Not Found', 'email_id' => $methodArgs['email_id']])
                                ];
                            }
                        } elseif ($methodName === 'list_user_projects') {
                            // List user projects
                            $user_id = $methodArgs['user_id'];
                            $projects = Project::where('user_id', $user_id)->get();

                            $outputs[] = [
                                'tool_call_id' => $call['id'],
                                'output' => json_encode(['message' => 'User Projects Listed', 'user_id' => $user_id, 'projects' => $projects])
                            ];

                        } elseif ($methodName === 'list_project_tasks') {
                            // List tasks for a project
                            $projectId = $methodArgs['project_id'];
                            $tasks = Task::where('project_id', $projectId)->get();

                            $outputs[] = [
                                'tool_call_id' => $call['id'],
                                'output' => json_encode(['message' => 'Project Tasks Listed', 'project_id' => $projectId, 'tasks' => $tasks])
                            ];

                        } elseif ($methodName === 'set_task_complete') {
                            // Set task as complete
                            $task = Task::find($methodArgs['task_id']);
                            if ($task) {
                                $task->is_complete = true;
                                $task->save();

                                $outputs[] = [
                                    'tool_call_id' => $call['id'],
                                    'output' => json_encode(['message' => 'Task Marked as Complete', 'task_id' => $task->id, 'task' => $task])
                                ];
                            }

                        } elseif ($methodName === 'create_project_task') {
                            // Create a task for a project
                            $task = new Task();
                            $task->title = $methodArgs['title'];
                            $task->user_id = $userId;
                            $task->description = $methodArgs['description'];
                            $task->project_id = $methodArgs['project_id'];
                            $task->save();

                            $outputs[] = [
                                'tool_call_id' => $call['id'],
                                'output' => json_encode(['message' => 'Task Created', 'task_id' => $task->id, 'task' => $task])
                            ];

                        } elseif ($methodName === 'update_project_description') {
                            // Update project description
                            $project = Project::find($methodArgs['project_id']);
                            if ($project) {
                                $project->description = $methodArgs['description'];
                                $project->save();

                                $outputs[] = [
                                    'tool_call_id' => $call['id'],
                                    'output' => json_encode(['message' => 'Project Description Updated', 'project_id' => $project->id, 'project' => $project])
                                ];
                            }

                        } else {
                            // Unknown method
                            $outputs[] = [
                                'tool_call_id' => $call['id'],
                                'output' => json_encode(['message' => 'Unknown Method', 'method_name' => $methodName])
                            ];
                        }

                    }


                    $response = $openai->submitToolOutputs($thread_id, $run_id, $outputs);


                }


            } while ($run['status'] != 'completed' && $run['status'] != 'failed');


            $messages = $openai->list_thread_messages($thread_id);

            foreach ($messages as $msg) {
                if (in_array($msg['role'], ['assistant', 'user'])) {
                    if ($msg['content'][0]['type'] == 'text') {
                        $message_content = $msg['content'][0]['text']['value'];
                        $role = $msg['role'];
                     //   echo " $role, $message_content\n";
                    }
                }
            }

            $email->processed = true;
            $email->save();
           // dd($run);

        }
    }


    function convertHtmlToShortText($html, $maxLength = 50000) {
        // Convert HTML to text
        $dom = new DOMDocument();
        @$dom->loadHTML($html);

        // Remove unwanted tags
        $tagsToRemove = ['script', 'style'];
        foreach ($tagsToRemove as $tag) {
            $elements = $dom->getElementsByTagName($tag);
            for ($i = $elements->length - 1; $i >= 0; $i--) {
                $element = $elements->item($i);
                $element->parentNode->removeChild($element);
            }
        }

        // Get text content, decode entities, collapse spaces, and trim
        $text = html_entity_decode($dom->textContent);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Shorten the text
        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength) . '...';
        }

        return $text;
    }
















}
