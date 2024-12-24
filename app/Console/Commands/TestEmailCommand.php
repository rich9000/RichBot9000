<?php

namespace App\Console\Commands;

use App\Services\EventLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to a specified address';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        try {

            Mail::raw('This is the body of an email.', function ($message) {
                $message->to('richcarroll@gmail.com')
                    ->subject('This is a test')
                    ->from('TheRichBot9000@RichBot9000.com');
            });

            $this->info("Test email sent successfully to {$email}.");
            EventLogger::simpleLog( 'cli', 'Test email sent to '.$email, []);



        } catch (\Exception $e) {
            $this->error("Failed to send test email: " . $e->getMessage());
        }

        return 0;
    }
}
