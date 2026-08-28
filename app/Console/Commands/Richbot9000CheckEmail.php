<?php

namespace App\Console\Commands;

use App\Services\RichbotEmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Richbot9000CheckEmail extends Command
{
    protected $signature = 'richbot:check-email {--limit=10}';
    protected $description = 'Check emails using IMAP and display them';

    public function handle()
    {
        $this->info('Starting email check...');
        
        $emailService = new RichbotEmailService();
        
        $this->info('Connection details:');
        $this->info('Server: ' . env('RICHBOT_9000_EMAIL_SERVER'));
        $this->info('Username: ' . env('RICHBOT_9000_EMAIL'));
        $this->info('Mailbox: ' . $emailService->getMailbox());
        
        if (!$emailService->connect()) {
            $this->error('Failed to connect to IMAP server');
            $this->error('Last IMAP error: ' . imap_last_error());
            $this->error('Check the Laravel logs for more details');
            return 1;
        }

        $this->info('Successfully connected to IMAP server');
        
        // Get all folders
        $folders = $emailService->listFolders();
        if (empty($folders)) {
            $this->error('No folders found');
            return 1;
        }
        
        $this->info('Available folders: ' . implode(', ', $folders));
        
        $limit = $this->option('limit');
        $allMessages = [];
        
        // Check each folder
        foreach ($folders as $folder) {
            $this->info("\nChecking folder: " . $folder);

            $messages = $emailService->checkFolder($folder, $limit);
            //dump($messages);
            
            if (!empty($messages)) {
                $this->info("Found " . count($messages) . " messages in " . $folder);
                $allMessages = array_merge($allMessages, $messages);
                
                // Display messages from this folder
                foreach ($messages as $message) {
                    $this->info("\nMessage ID: " . $message['id']);
                    $this->info("Folder: " . $message['folder']);
                    $this->info("Subject: " . $message['subject']);
                    $this->info("From: " . $message['from']);
                    $this->info("Date: " . $message['date']);
                    $this->info("Size: " . $message['size'] . " bytes");
                    $this->info("Status: " . ($message['seen'] ? 'Read' : 'Unread'));
                    $this->info("Has Attachments: " . ($message['has_attachments'] ? 'Yes' : 'No'));
                    $this->info("----------------------------------------");
                    //$this->info("Body: " . $message['body']);
                    $this->info("----------------------------------------");
                    $this->info("Body: " . substr($message['body'], 0, 200) . "...");
                    $this->info("----------------------------------------");
                }
            } else {
                $this->info("No messages found in " . $folder);
            }
        }

        // Get full content of the first message as an example
        if (!empty($allMessages)) {
            $this->info("\nFetching full content of the first message:");
            $firstMessage = $allMessages[0];
            $fullMessage = $emailService->getMessage($firstMessage['id']);
            
            if ($fullMessage) {
                $this->info("\nFull Message Details:");
                $this->info("----------------------------------------");
                $this->info("Body: " . substr($fullMessage['body'], 0, 200) . "...");
                
                if (!empty($fullMessage['attachments'])) {
                    $this->info("\nAttachments:");
                    foreach ($fullMessage['attachments'] as $attachment) {
                        $this->info("- " . $attachment['filename']);
                    }
                }
            }
        }

        $emailService->disconnect();
        $this->info("\nEmail check completed.");
        return 0;
    }
}
