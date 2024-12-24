<?php
namespace App\Console\Commands;

use App\Models\Assistant;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Command;
use Illuminate\Console\Command as ConsoleCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use React\EventLoop\Factory;
use Ratchet\Client\Connector;
use React\Socket\Connector as ReactConnector;
use App\Models\Ticket;

class ParseTickets extends ConsoleCommand
{


    protected $signature = 'xx:parseTickets';

    protected $description = 'Command description';

    public function handle()
    {


        $assistant = Assistant::where('name','PDF_Parser')->first();

        $assistant_id  = $assistant->createOpenAiAssistant();


        $path = '/text_tickets'; // Change this to 'public', 's3', etc., as needed

        // Check if the directory exists on the disk
        if (!Storage::disk('local')->exists($path)) {
            echo "folder not found";
        }

        // Get all files in the folder from the specified disk
        $files = Storage::disk('local')->files($path);

        // Initialize an array to hold the results
        $results = [];

        foreach ($files as $file) {
            // Get the file name
            $fileName = basename($file);

            $filePath = "$path/$fileName";

            // Skip if the file doesn't end with '.txt'
            if (pathinfo($fileName, PATHINFO_EXTENSION) !== 'txt') {
                continue; // Skip this file and move to the next one
            }

            // Check if a record with this file name exists in the database
            $recordExists = Ticket::where('file_name', $fileName)->exists();

            if(!$recordExists){

                echo "new record never seen before\n";

                $fileContent = Storage::disk('local')->get($filePath);

                $ticket = new Ticket(['file_name'=>$fileName,'raw_data'=>$fileContent]);
                $ticket->save();

            }

        }

        foreach (Ticket::where('order_number',null)->get() as $ticket){









            dd($ticket);
            exit;
        }

        dd($results);

    }
}

