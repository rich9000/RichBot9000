<?php

namespace App\Console\Commands;

use App\Models\Email;
use App\Models\Contact;
use App\Models\EmailContact;
use App\Models\Assistant;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Services\ToolExecutor;
use App\Models\Tool;
use App\Services\OpenAIAssistant;
use App\Models\AssistantFunction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\Conversation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\File;
use Spatie\PdfToText\Pdf;



class EmailParser extends Command
{

    // Command signature and descriptionfff
    protected $signature = 'zz:EmailParser {--user-email=} {--user-password=} {--this-year} {--months=} {--skip-number=}' ;
    protected $description = 'Parses every email into the DB for this year.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {


        $currentUserId = 1;

        $tenantId = env('AZURE_TENANT_ID');
        $clientId = env('AZURE_CLIENT_ID');
        $clientSecret = env('AZURE_CLIENT_SECRET');
        $user_id = env('AZURE_USER_ID');
        $tokenUrl = "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token";

        echo "Token url:$tokenUrl \n";

        $client = new Client();

        try {
            // Step 1: Get an access token
            $response = $client->post($tokenUrl, [
                'form_params' => [

                    'client_id' => $clientId,
                    //  'username'=>'rich',
                    // 'password'=>'richlikestowork',
                    'grant_type' => 'client_credentials',
                    'client_secret' => $clientSecret,
                    'scope' => $scope,
                ],
            ]);

            $accessToken = json_decode($response->getBody(), true)['access_token'];


            echo "Looking for elationbilling folder\n";

            $foldersUrl = "https://graph.microsoft.com/v1.0/users/$user_id/mailFolders";
            try {
                $folderResponse = $client->get($foldersUrl, [
                    'headers' => [
                        'Authorization' => "Bearer $accessToken",
                    ],
                ]);

                $folders = json_decode($folderResponse->getBody(), true);
                foreach ($folders['value'] as $folder) {

                    if ($folder['displayName'] === 'Inbox') {


                        //    var_dump($folder);
                        echo "Found Inbox ID: {$folder['id']}\n";

                        $inbox_id = $folder['id']; // Set the correct folder ID
                        var_dump($inbox_id);


                    }


                }



            } catch (RequestException $e) {
                dump("Error fetching folders: " . $e->getMessage());
                return;
            }


            $oneMonthAgo = Carbon::now()->subMonth()->toIso8601String();
            $recipientEmail = 'rich@rainbowtel.com';




// Define the date range for this year

// Define the date range for this year in ISO 8601 format ending with 'Z'
            $startOfYear = Carbon::now()->startOfYear()->format('Y-m-d\TH:i:s\Z'); // e.g., '2024-01-01T00:00:00Z'
            $endOfYear = Carbon::now()->endOfYear()->format('Y-m-d\TH:i:s\Z');     // e.g., '2024-12-31T23:59:59Z'

            // Build the Graph API URL with the date filter
            $filter = "receivedDateTime ge $startOfYear and receivedDateTime le $endOfYear";
            $graphUrl = "https://graph.microsoft.com/v1.0/users/$user_id/messages?" . http_build_query([
                    '$filter' => $filter,
                    '$expand' => 'attachments',
                    '$top'=>   1000,

                    ]);
            $graphUrl = "https://graph.microsoft.com/v1.0/users/$user_id/messages?" . http_build_query([
                    '$filter'  => $filter,
                    '$expand'  => 'attachments',
                    '$orderby' => 'receivedDateTime desc',
                    '$top'     => 100, // Adjust as needed, maximum is 1000
                ]);



// Build the Graph API URL with the date filter
        //    $graphUrl = "https://graph.microsoft.com/v1.0/users/$user_id/mailFolders/$inbox_id/messages?\$filter=receivedDateTime ge '$startOfYear' and receivedDateTime le '$endOfYear'";
        //    $graphUrl .= "&\$expand=attachments";

            $nextLink = $graphUrl; // Initialize nextLink

            do {
                $response = $client->get($nextLink, [
                    'headers' => [
                        'Authorization' => "Bearer $accessToken",
                    ],
                ]);

                $emails = json_decode($response->getBody(), true);

                foreach ($emails['value'] as $email) {

                    $messageId = $email['id']; // Unique message ID from the email server

                    // Check if the email has already been processed
                    $existingEmail = Email::where('message_id', $messageId)->first();

                    if ($existingEmail) {
                        dump("Skipping already processed email with ID: $messageId");
                        continue; // Skip processing if already in the database
                    }

                    $receivedDateTime = Carbon::parse($email['receivedDateTime'])->format('Y-m-d H:i:s');

                    $newEmail = Email::create([
                        'message_id'     => $messageId,
                        'parent_folder_id'  => $email['parentFolderId'] ?? null,
                        'received_datetime' => $receivedDateTime ?? null,
                        'body'           => $email['body']['content'], // Assuming HTML content
                        'summary'        => $email['bodyPreview'],
                        'subject'        => $email['subject'],
                        'user_id'        => $currentUserId, // Replace with your user context
                        // Other fields like 'project_id', 'task_id', etc., can be set later
                    ]);

                    // Process the 'from' contact
                    if (isset($email['from']['emailAddress']['address'])) {
                        $fromEmail = $email['from']['emailAddress']['address'];
                        $fromName = $email['from']['emailAddress']['name'];

                        $fromContact = Contact::firstOrCreate(
                            [
                                'user_id' => $currentUserId,
                                'email'   => $fromEmail,
                            ],
                            [
                                'name' => $fromName,
                            ]
                        );

                        // Update the 'from_contact_id' in the 'emails' table
                        $newEmail->from_contact_id = $fromContact->id;
                        $newEmail->save();

                        // Associate the contact with the email in 'email_contacts' table
                        EmailContact::create([
                            'email_id'   => $newEmail->id,
                            'contact_id' => $fromContact->id,
                            'context'    => 'from',
                        ]);
                    }

                    // Process the 'to' contacts
                    if (isset($email['toRecipients'])) {

                        foreach ($email['toRecipients'] as $recipient) {
                            $toEmail = $recipient['emailAddress']['address'] ?? null;
                            $toName = $recipient['emailAddress']['name'] ?? null;




                            $toContact = Contact::firstOrCreate(
                                [
                                    'user_id' => $currentUserId,
                                    'email'   => $toEmail,
                                ],
                                [
                                    'name' => $toName,
                                ]
                            );

                            // If only one 'to' recipient, set 'to_contact_id'
                            if (count($email['toRecipients']) == 1) {
                                $newEmail->to_contact_id = $toContact->id;
                                $newEmail->save();
                            }

                            // Associate the contact with the email in 'email_contacts' table
                            EmailContact::create([
                                'email_id'   => $newEmail->id,
                                'contact_id' => $toContact->id,
                                'context'    => 'to',
                            ]);
                        }
                    }

                    // Process 'cc' contacts
                    if (isset($email['ccRecipients'])) {
                        foreach ($email['ccRecipients'] as $recipient) {
                            $ccEmail = $recipient['emailAddress']['address'];
                            $ccName = $recipient['emailAddress']['name'];

                            $ccContact = Contact::firstOrCreate(
                                [
                                    'user_id' => $currentUserId,
                                    'email'   => $ccEmail,
                                ],
                                [
                                    'name' => $ccName,
                                ]
                            );

                            // Associate the contact with the email in 'email_contacts' table
                            EmailContact::create([
                                'email_id'   => $newEmail->id,
                                'contact_id' => $ccContact->id,
                                'context'    => 'cc',
                            ]);
                        }
                    }

                    // Process 'bcc' contacts (if available)
                    if (isset($email['bccRecipients'])) {
                        foreach ($email['bccRecipients'] as $recipient) {
                            $bccEmail = $recipient['emailAddress']['address'];
                            $bccName = $recipient['emailAddress']['name'];

                            $bccContact = Contact::firstOrCreate(
                                [
                                    'user_id' => $currentUserId,
                                    'email'   => $bccEmail,
                                ],
                                [
                                    'name' => $bccName,
                                ]
                            );

                            // Associate the contact with the email in 'email_contacts' table
                            EmailContact::create([
                                'email_id'   => $newEmail->id,
                                'contact_id' => $bccContact->id,
                                'context'    => 'bcc',
                            ]);
                        }
                    }

                    // Optionally, extract emails from the body content
                    $bodyContent = $email['body']['content'];
                    $emailsInBody = $this->extractEmailsFromText($bodyContent);

                    foreach ($emailsInBody as $emailAddress) {
                        $bodyContact = Contact::firstOrCreate(
                            [
                                'user_id' => $currentUserId,
                                'email'   => $emailAddress,
                            ],
                            [
                                'name' => null, // Name is unknown
                            ]
                        );

                        // Associate the contact with the email in 'email_contacts' table
                        EmailContact::create([
                            'email_id'   => $newEmail->id,
                            'contact_id' => $bodyContact->id,
                            'context'    => 'body',
                        ]);
                    }


                }

                // Get the next set of emails
                $nextLink = $emails['@odata.nextLink'] ?? null;

            } while ($nextLink);





            exit;

            echo "\nChecking Mail Folders:\n";

            $foldersUrl = "https://graph.microsoft.com/v1.0/users/$user_id/mailFolders";

            try {
                $folderResponse = $client->get($foldersUrl, [
                    'headers' => [
                        'Authorization' => "Bearer $accessToken",
                    ],
                ]);

                $folders = json_decode($folderResponse->getBody(), true);
                $folder_id = null;
                $emailCount = 0;

                foreach ($folders['value'] as $folder) {
                    if ($folder['displayName'] === 'elationbilling') {
                        $folder_id = $folder['id'];
                        $emailCount = $folder['totalItemCount']; // Get the count of emails in the folder
                        break;
                    }
                }

                if (!empty($folder_id)) {
                    dump("Folder 'elationbilling' found with ID: $folder_id");
                    dump("Total emails in 'elationbilling' folder: $emailCount");
                } else {
                    dump("Folder 'elationbilling' not found.");
                }
            } catch (RequestException $e) {
                dump("Error fetching folders: " . $e->getMessage());
                return;
            }


            exit;


// Set the sender email address and the date from one month ago
            $senderEmail = 'elationbilling@rainbowtel.com';
            $oneMonthAgo = Carbon::now()->subMonth()->toIso8601String();

            // Update the Graph API URL with filter parameters
            $graphUrl = 'https://graph.microsoft.com/v1.0/me/messages?$filter=' .
                "from/emailAddress/address eq '$senderEmail' and " .
                "receivedDateTime ge $oneMonthAgo";

// Make the request to the Graph API
            $response = $client->get($graphUrl, [
                'headers' => [
                    'Authorization' => "Bearer $accessToken",
                ],
            ]);

            $emails = json_decode($response->getBody(), true);

// Loop through and display the filtered messages
            foreach ($emails['value'] as $key => $email) {
                dump($email['receivedDateTime']);
                dump($email['subject']);
                dump($email['bodyPreview']);
            }


            exit;


            // Step 2: Use the access token to call Microsoft Graph API
            $usersUrl = 'https://graph.microsoft.com/v1.0/users';

            $response = $client->get($usersUrl, [
                'headers' => [
                    'Authorization' => "Bearer $accessToken",
                ],
            ]);
            $users = json_decode($response->getBody(), true);

            foreach ($users['value'] as $user) {

                //echo "{$user['id']}\n";
                echo "{$user['id']} {$user['displayName']} {$user['userPrincipalName']} {$user['mobilePhone']}\n";
                //var_dump($user);

                if ($user['userPrincipalName'] == 'rich@rainbowtel.com') {

                    $id = $user['id'];
                    break;
                }


            }


            exit;


            // Step 2: Use the access token to call Microsoft Graph API
            $calendarUrl = 'https://graph.microsoft.com/v1.0/users/' . $id . '/events';

            $response = $client->get($calendarUrl, [
                'headers' => [
                    'Authorization' => "Bearer $accessToken",
                ],
            ]);

            $events = json_decode($response->getBody(), true);

            // Step 3: Display the events
            foreach ($events['value'] as $event) {

                var_dump($event);


                $this->info("Event Subject: {$event['subject']}");
                $this->info("Start: {$event['start']['dateTime']}");
                $this->info("End: {$event['end']['dateTime']}");
                $this->info("Organizer: {$event['organizer']['emailAddress']['name']}");
                $this->info("Location: " . ($event['location']['displayName'] ?? 'No location'));
                $this->info("--------------------------");
            }


            exit;


            // Step 2: Use the access token to call Microsoft Graph API
            $usersUrl = 'https://graph.microsoft.com/v1.0/users';

            $response = $client->get($usersUrl, [
                'headers' => [
                    'Authorization' => "Bearer $accessToken",
                ],
            ]);
            $users = json_decode($response->getBody(), true);

            foreach ($users['value'] as $user) {

                //echo "{$user['id']}\n";
                echo "{$user['id']} {$user['displayName']} {$user['userPrincipalName']} {$user['mobilePhone']}\n";
                //var_dump($user);

            }

            $graphUrl = 'https://graph.microsoft.com/v1.0/users/67543176-9d19-4b1e-9019-a5d42449862e/messages';

            $response = $client->get($graphUrl, [
                'headers' => [
                    'Authorization' => "Bearer $accessToken",
                ],
            ]);

            $emails = json_decode($response->getBody(), true);


            foreach ($emails['value'] as $key => $email) {

                dump($email['receivedDateTime']);
                dump($email['subject']);
                dump($email['bodyPreview']);
                //dump($val);

            }


        } catch (RequestException $e) {
            $this->error('Error fetching emails: ' . $e->getMessage());
        }

        return 0;
    }


    function extractEmailsFromText($text)
    {
        // Use a regular expression to find email addresses
        preg_match_all('/[a-zA-Z0-9.\-_]+@[a-zA-Z0-9.\-_]+\.[a-zA-Z]+/', $text, $matches);

        return array_unique($matches[0]);
    }
}

