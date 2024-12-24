<?php

namespace App\Console\Commands;

use App\Services\OpenAIAssistant;
use App\Services\ToolExecutor;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class RunOpenAiApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xx:tt';

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

        $openAIAssistant = new OpenAIAssistant();

        //$assistantId = $this->argument('assistantId');
        //$prompt = $this->argument('prompt');

        $assistant_id = 'asst_kIgtLGI33HkIjbb6x1qEw4b5';
        $instructions = 'This is a one shot task, you need to complete everything in this run, because there is only one run.';
        $prompt = 'This thing working?';




        $client = new Client();

        $apiKey = env('OPENAI_API_KEY');
        $base_url = 'https://api.openai.com/v1';
        $version_header = 'OpenAI-Beta: assistants=v1';

        $thread_id = $openAIAssistant->create_thread($instructions, 'user');

        $msg = $openAIAssistant->add_message($thread_id, $prompt, 'user');

        $run_id = $openAIAssistant->create_run($thread_id, $assistant_id);

        $run = $openAIAssistant->get_run($thread_id, $run_id);

       // echo "status: {$run['status']},{$run['required_action']}, {$run['id']}, {$run['thread_id']} ,{$run['assistant_id']}";

        $retryCount = 0;
        $maxRetries = 10;
        $retryDelay = 5; // seconds

        do {

            sleep($retryDelay);
            try {

                $run = $openAIAssistant->get_run($thread_id, $run_id);
                //echo "status: {$run['status']},{$run['required_action']}, {$run['id']}, {$run['thread_id']} ,{$run['assistant_id']}";

            } catch (\Exception $e) {

                \Log::error("Error retrieving run (attempt $retryCount): " . $e->getMessage());
                if (++$retryCount > $maxRetries) {
                    throw $e;
                }
                continue;
            }

            if ($run['status'] == 'requires_action') {
                $toolExecutor = new ToolExecutor(); // Implement this class
                $outputs = $openAIAssistant->execute_tools($thread_id, $run_id, $toolExecutor);
                $openAIAssistant->submit_tool_outputs($thread_id, $run_id, $outputs);
            }

        } while (!($run['status'] == 'completed' || $run['status'] == 'failed') );


        if($run['status'] == 'completed') {
            dd($openAIAssistant->list_thread_messages($thread_id));

        } else {

            echo "Run not successfull";
            dd($run);

        }



    }


}
