<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class RichbotEmailService
{
    private $imap;
    private $mailbox;
    private $username;
    private $password;

    public function __construct()
    {
        $server = env('RICHBOT_9000_EMAIL_SERVER', 'notify.richbot9000.com');
        $this->mailbox = "{{$server}:993/imap/ssl/novalidate-cert}";
        $this->username = env('RICHBOT_9000_EMAIL');
        $this->password = env('RICHBOT_9000_PASSWORD');
    }

    /**
     * Connect to the IMAP server
     */
    public function connect()
    {
        try {
            Log::info('Attempting IMAP connection with:', [
                'mailbox' => $this->mailbox,
                'username' => $this->username,
                'server' => env('RICHBOT_9000_EMAIL_SERVER'),
            ]);
            
            $this->imap = \imap_open($this->mailbox , $this->username, $this->password);
            
            if (!$this->imap) {
                $error = \imap_last_error();
                Log::error('IMAP Connection Failed:', [
                    'error' => $error,
                    'mailbox' => $this->mailbox,
                    'username' => $this->username,
                    'server' => env('RICHBOT_9000_EMAIL_SERVER'),
                ]);
                throw new Exception('Failed to connect to IMAP server: ' . $error);
            }

            Log::info('IMAP Connection Successful');
            return true;

        } catch (Exception $e) {
            Log::error('IMAP Connection Error:', [
                'message' => $e->getMessage(),
                'mailbox' => $this->mailbox . 'INBOX',
                'username' => $this->username,
                'server' => env('RICHBOT_9000_EMAIL_SERVER'),
            ]);
            return false;
        }
    }

    /**
     * List all messages in the mailbox
     * @param int $limit Number of messages to retrieve
     * @return array Array of message details
     */
    public function listMessages($limit = 10)
    {
        if (!$this->imap) {
            $this->connect();
        }

        try {
            Log::info('Attempting to list messages');
            $totalMessages = \imap_num_msg($this->imap);
            Log::info('Total messages found: ' . $totalMessages);
            
            $messages = [];
            $start = max(1, $totalMessages - $limit + 1);
            Log::info("Fetching messages from {$start} to {$totalMessages}");

            for ($i = $start; $i <= $totalMessages; $i++) {
                $header = \imap_headerinfo($this->imap, $i);
                $structure = \imap_fetchstructure($this->imap, $i);
                
                $messages[] = [
                    'id' => $i,
                    'subject' => $this->decodeSubject($header->subject),
                    'from' => $header->from[0]->mailbox . '@' . $header->from[0]->host,
                    'date' => date('Y-m-d H:i:s', strtotime($header->date)),
                    'size' => $header->Size,
                    'seen' => $header->Seen,
                    'answered' => $header->Answered,
                    'flagged' => $header->Flagged,
                    'has_attachments' => $this->hasAttachments($structure),
                ];
            }

            Log::info('Successfully retrieved ' . count($messages) . ' messages');
            return $messages;
        } catch (Exception $e) {
            Log::error('Error listing messages: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the full message content
     * @param int $messageId The ID of the message to retrieve
     * @return array Message details including body and attachments
     */
    public function getMessage($messageId)
    {
        if (!$this->imap) {
            $this->connect();
        }

        try {
            $header = \imap_headerinfo($this->imap, $messageId);
            $structure = \imap_fetchstructure($this->imap, $messageId);
            
            $message = [
                'id' => $messageId,
                'subject' => $this->decodeSubject($header->subject),
                'from' => $header->from[0]->mailbox . '@' . $header->from[0]->host,
                'date' => date('Y-m-d H:i:s', strtotime($header->date)),
                'body' => $this->getBody($messageId, $structure),
                'attachments' => $this->getAttachments($messageId, $structure),
            ];

            return $message;
        } catch (Exception $e) {
            Log::error('Error getting message: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the message body
     */
    private function getBody($messageId, $structure)
    {
        if ($structure->type == 0) {
            return \imap_fetchbody($this->imap, $messageId, 1);
        } elseif ($structure->type == 1) {
            return \imap_fetchbody($this->imap, $messageId, 1.1);
        }
        return '';
    }

    /**
     * Get attachments from the message
     */
    private function getAttachments($messageId, $structure)
    {
        $attachments = [];
        
        if (isset($structure->parts)) {
            foreach ($structure->parts as $partNum => $part) {
                if (isset($part->disposition) && $part->disposition == 'attachment') {
                    $filename = $part->dparameters[0]->value;
                    $attachments[] = [
                        'filename' => $filename,
                        'part_number' => $partNum + 1,
                    ];
                }
            }
        }
        
        return $attachments;
    }

    /**
     * Check if message has attachments
     */
    private function hasAttachments($structure)
    {
        if (isset($structure->parts)) {
            foreach ($structure->parts as $part) {
                if (isset($part->disposition) && $part->disposition == 'attachment') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Decode email subject
     */
    private function decodeSubject($subject)
    {
        $decoded = \imap_mime_header_decode($subject);
        $result = '';
        foreach ($decoded as $part) {
            $result .= $part->text;
        }
        return $result;
    }

    /**
     * Close the IMAP connection
     */
    public function disconnect()
    {
        if ($this->imap) {
            \imap_close($this->imap);
            $this->imap = null;
        }
    }

    public function listFolders()
    {
        try {
            Log::info('Attempting to list folders with mailbox: ' . $this->mailbox);
            $folders = \imap_list($this->imap, $this->mailbox, '*');
            
            if ($folders === false) {
                Log::error('Failed to list folders: ' . \imap_last_error());
                return [];
            }
            
            // Strip the mailbox prefix from folder names
            $cleanFolders = array_map(function($folder) {
                return str_replace($this->mailbox, '', $folder);
            }, $folders);
            
            Log::info('Found folders: ' . print_r($cleanFolders, true));
            return $cleanFolders;
        } catch (Exception $e) {
            Log::error('Error listing folders: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the mailbox connection string
     */
    public function getMailbox()
    {
        return $this->mailbox . 'INBOX';
    }

    /**
     * Destructor to ensure connection is closed
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Check INBOX status and message count
     */
    public function checkInbox()
    {
        try {
            Log::info('Attempting to check INBOX');
            
            // Try to get message count directly
            $messageCount = \imap_num_msg($this->imap);
            
            if ($messageCount === false) {
                Log::error('Failed to get message count: ' . \imap_last_error());
                return false;
            }
            
            Log::info('Successfully checked INBOX. Message count: ' . $messageCount);
            return $messageCount;
        } catch (Exception $e) {
            Log::error('Error checking INBOX: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check messages in a specific folder
     * @param string $folder The folder name to check
     * @param int $limit Number of messages to retrieve
     * @return array Array of message details
     */
    public function checkFolder($folder, $limit = 10)
    {
        try {
            echo "Attempting to check folder: {$folder}\n";
            
            // Select the folder - use the full mailbox string
            $folderString = $this->mailbox . $folder;
            echo "Opening folder with string: " . $folderString . "\n";
            

            // First try to select the folder
            $result = \imap_open($folderString, $this->username, $this->password);
           
           // dump($result, \imap_num_msg($result));
            
            if ($result === false) {
                Log::error("Failed to select folder {$folder}: " . \imap_last_error());
                return [];
            }

             $totalMessages = \imap_num_msg($result);
            //$totalMessages = \imap_num_msg($this->imap);
            echo "Found {$totalMessages} messages in folder {$folder}\n";
            
            if ($totalMessages === 0) {
                return [];
            }
            
            $messages = [];
            $start = max(1, $totalMessages - $limit + 1);
            
            for ($i = $start; $i <= $totalMessages; $i++) {
                $header = \imap_headerinfo($result, $i);
                $structure = \imap_fetchstructure($result, $i);
                $body = \imap_body($result, $i);

                dump($header, $structure, $body);
                

                $messages[] = [
                    'id' => $i,
                    'folder' => $folder,
                    'message_id' => $header->MessageId ?? '',
                    'subject' => $this->decodeSubject($header->subject),
                    'from' => $header->from[0]->mailbox . '@' . $header->from[0]->host,
                    'date' => date('Y-m-d H:i:s', strtotime($header->date)),
                    'size' => $header->Size,
                    'seen' => $header->Seen ?? '',
                    'answered' => $header->Answered ?? '',
                    'flagged' => $header->Flagged ?? '',
                    'has_attachments' => $this->hasAttachments($structure),
                    'body' => $body,
                ];

                //dump($messages);
            }
            
            echo "Successfully retrieved " . count($messages) . " messages from folder {$folder}\n";
            return $messages;
        } catch (Exception $e) {
            echo "Error checking folder {$folder}: " . $e->getMessage() . "\n";
            return [];
        }
    }
} 