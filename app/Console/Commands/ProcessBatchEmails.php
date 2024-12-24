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
use Symfony\Component\Process\Process;

class ProcessBatchEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zz:pbe  {--concurrency=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'process batch emails';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $concurrency = (int) $this->option('concurrency');
        $userId = 1; // Assuming Rich Carroll has user_id 1
        $user = User::find(1);


        $contactId = 1  ;

            $emails = Email::where('user_id', $userId)
                ->whereHas('contacts', function ($query) use ($contactId) {
                    $query->where('contact_id', $contactId);
                })
                ->where('processed',false)
                ->with('contacts')
                ->get();

        $emailQueue = $emails->toArray();
        $processes = [];
        $maxProcesses = $concurrency;


        while (!empty($emailQueue) || !empty($processes)) {

            while (count($processes) < $maxProcesses && !empty($emailQueue)) {
                $email = array_shift($emailQueue);
                $emailId = $email['id'];

                $process = new Process(['php', 'artisan', 'zz:processEmails', $emailId]);
                $process->start();

                $processes[$emailId] = $process;

                $this->info("Started process for email ID: {$emailId}");
            }


            foreach ($processes as $emailId => $process) {
                if (!$process->isRunning()) {
                    if (!$process->isSuccessful()) {

                        $this->error("Process for email ID {$emailId} failed: " . $process->getErrorOutput());

                    } else {

                        $this->info("Process for email ID {$emailId} completed.");
                        // Remove completed process


                    }
                    unset($processes[$emailId]);

                }
            }

            // Sleep for a short time to prevent tight loop
            sleep(1); // Sleep for 0.5 seconds
        }

        $this->info('All emails processed.');



    }
















}
