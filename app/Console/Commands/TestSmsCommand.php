<?php

namespace App\Console\Commands;

use App\Services\EventLogger;
use App\Services\Twilio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
//use GuzzleHttp\Client;
use Twilio\Rest\Client;

class TestSmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:sms {targetNumber} {message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test sms to a specified address';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetNumber = $this->argument('targetNumber');
        $message = $this->argument('message');

        //Get Twilio credentials from environment
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_TOKEN');
        $twilioNumber = env('TWILIO_FROM');

        // Instantiate a new Twilio client
        $client = new Client($sid, $token);

        // Send a test message
        try {
            $message = $client->messages->create(
                $targetNumber,
                [
                    'from' => $twilioNumber,
                    'body' => $message
                ]
            );

            $this->info('Message sent successfully! SID: ' . $message->sid);
        } catch (\Exception $e) {
            $this->error('Failed to send message: ' . $e->getMessage());
        }













exit;










        $twilio = new Client($sid, $token);

        $new_key = $twilio->newKeys->create(["friendlyName" => "RichBot9000 Test KEy"]);


        print $new_key->sid;

        exit;














        $endpoint = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Balance.json";

        // Initialize the Guzzle HTTP client
        $client = new Client();

        try {
            // Make the request with Basic Auth (SID as the username, Token as the password)
            $response = $client->get($endpoint, [
                'auth' => [$sid, $token], // Basic Auth: [$username, $password]
            ]);

            // Decode the JSON response
            $data = json_decode($response->getBody(), true);

            dd($data);

            // Return the balance or whatever data you want from the response
            return $data['balance'];

        } catch (RequestException $e) {
            // Handle any exceptions or errors
            return 'Error: ' . $e->getMessage();
        }




        return 0;
    }
}
