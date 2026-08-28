<?php

namespace App\Services\Executors;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Conversation;



class RichBotExecutor
{
    private $session_data;
    private $conversation;
    private $user;

    public function __construct($user = null)
    {
        $this->user = $user;
    }

    public function setConversation($conversation)
    {
        $this->conversation = $conversation;

        Log::info('[RichBotExecutor] setConversation: ' . json_encode($this->conversation));

        if(isset($this->conversation->path_state['richbot_user_id']) && $this->conversation->path_state['richbot_user_id']) {
            $this->user = User::find($this->conversation->path_state['richbot_user_id']);
            if(!$this->user) {
                Log::error('[RichBotExecutor] setConversation error: User is required');
                return;
            }
        }
    }
   

    private function getRichbotUserId($arguments = [])
    {
        $richbot_user_id = null;

        if(isset($arguments['user_id'])) {
            $richbot_user_id = intval($arguments['user_id']);
        } else if($this->user && $this->user->id) {  
            $richbot_user_id = $this->user->id;
        } else if(isset($this->conversation->path_state['richbot_user_id']) && $this->conversation->path_state['richbot_user_id']) {
            $richbot_user_id = $this->conversation->path_state['richbot_user_id'];

        }

        return $richbot_user_id;
        
    }

    /**
     * Add a new contact
     * 
     * @param array $arguments
     * @return array
     */
    public function richbot_add_contact($arguments)
    {



        if(is_string($arguments)) {
            $arguments = json_decode($arguments, true);
        }

        $user_id = $this->getRichbotUserId($arguments);

        if(!$user_id) {

            Log::error('[RichBotExecutor] add_contact error: User ID is required');
            return ['success' => false, 'error' => 'User ID is required'];
        }

        Log::info('[RichBotExecutor] add_contact arguments: ' . json_encode($arguments));

        try {
            $name = $arguments['name'] ?? null;
            $email = $arguments['email'] ?? null;
            $phone = $arguments['phone'] ?? null;

            if (!$name || !$email) {
                return [
                    'success' => false, 
                    'error' => 'Name and email are required fields'
                ];
            }

            // Check if contact already exists
            $existingContact = DB::table('contacts')
                ->where('email', $email)
                ->first();

            if ($existingContact) {
                return [
                    'success' => false,
                    'error' => 'Contact with this email already exists'
                ];
            }

            $contactId = DB::table('contacts')->insertGetId([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'user_id' => $user_id,
              
            ]);

            $newContact = DB::table('contacts')->where('id', $contactId)->first();

            return [
                'success' => true,
                'message' => 'Contact added successfully',
                'contact' => $newContact
            ];

        } catch (\Exception $e) {
            Log::error('[RichBotExecutor] add_contact error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Search for contacts by various criteria
     * 
     * @param array $arguments
     * @return array
     */
    public function richbot_search_contacts($arguments)
    {
        Log::info('[RichBotExecutor] search_contacts arguments: ' . json_encode($arguments));

        try {


            if(is_string($arguments)) {
                $arguments = json_decode($arguments, true);
            }
    

            $user_id = $this->getRichbotUserId($arguments);

            if(!$user_id) {
                Log::error('[RichBotExecutor] search_contacts error: User ID is required');
                return ['success' => false, 'error' => 'User ID is required'];
            }



            $search_term = $arguments['search_term'] ?? null;
            $search_type = $arguments['search_type'] ?? 'all'; // all, name, email, phone

            if (!$search_term) {
                return ['success' => false, 'error' => 'Search term is required'];
            }

            $query = DB::table('contacts');
            $query->where('user_id', $user_id);

            switch ($search_type) {
                case 'name':
                    $query->where('name', 'LIKE', "%{$search_term}%");
                    break;
                case 'email':
                    $query->where('email', 'LIKE', "%{$search_term}%");
                    break;
                case 'phone':
                    $query->where('phone', 'LIKE', "%{$search_term}%");
                    break;
                default:
                    $query->where(function($q) use ($search_term) {
                        $q->where('name', 'LIKE', "%{$search_term}%")
                          ->orWhere('email', 'LIKE', "%{$search_term}%")
                          ->orWhere('phone', 'LIKE', "%{$search_term}%");
                    });
            }

            $contacts = $query->get();

            return [
                'success' => true,
                'message' => 'Contacts found successfully',
                'contacts' => $contacts
            ];

        } catch (\Exception $e) {
            Log::error('[RichBotExecutor] search_contacts error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Search for assistants by various criteria
     * 
     * @param array $arguments
     * @return array
     */
    public function richbot_search_assistants($arguments)
    {
        Log::info('[RichBotExecutor] search_assistants arguments: ' . json_encode($arguments));


        if(is_string($arguments)) {
            $arguments = json_decode($arguments, true);
        }

        try {
            $search_term = $arguments['search_term'] ?? null;
            $search_type = $arguments['search_type'] ?? 'all'; // all, name, specialty, availability

            if (!$search_term) {
                return ['success' => false, 'error' => 'Search term is required'];
            }

            $query = DB::table('assistants')
                ->where('is_public', true); // Only show public assistants in search

            switch ($search_type) {
                case 'name':
                    $query->where('name', 'LIKE', "%{$search_term}%");
                    break;
                case 'system_message':
                    $query->where('system_message', 'LIKE', "%{$search_term}%");
                    break;
               
                default:
                    $query->where(function($q) use ($search_term) {
                        $q->where('name', 'LIKE', "%{$search_term}%")
                          ->orWhere('system_message', 'LIKE', "%{$search_term}%");
                    });
            }

            $assistants = $query->get();

            return [
                'success' => true,
                'message' => 'Assistants found successfully',
                'assistants' => $assistants
            ];

        } catch (\Exception $e) {
            Log::error('[RichBotExecutor] search_assistants error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get contact details by ID
     * 
     * @param array $arguments
     * @return array
     */
    public function richbot_get_contact($arguments)
    {
        Log::info('[RichBotExecutor] get_contact arguments: ' . json_encode($arguments));


        if(is_string($arguments)) {
            $arguments = json_decode($arguments, true);
        }

        try {
            $contact_id = $arguments['contact_id'] ?? null;

            if (!$contact_id) {
                return ['success' => false, 'error' => 'Contact ID is required'];
            }

            $contact = DB::table('contacts')->where('id', $contact_id)->first();

            if (!$contact) {
                return ['success' => false, 'message' => 'Contact not found'];
            }

            return [
                'success' => true,
                'message' => 'Contact retrieved successfully',
                'contact' => $contact
            ];

        } catch (\Exception $e) {
            Log::error('[RichBotExecutor] get_contact error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get assistant details by ID
     * 
     * @param array $arguments
     * @return array
     */
    public function richbot_get_assistant($arguments)
    {
        Log::info('[RichBotExecutor] get_assistant arguments: ' . json_encode($arguments));

        if(is_string($arguments)) {
            $arguments = json_decode($arguments, true);
        }

        try {
            $assistant_id = $arguments['assistant_id'] ?? null;

            if (!$assistant_id) {
                return ['success' => false, 'error' => 'Assistant ID is required'];
            }

            $assistant = DB::table('assistants')
                ->where('id', $assistant_id)
                ->where(function($query) {
                    $query->where('is_public', true)
                          ->orWhere('user_id', $this->getRichbotUserId($arguments));
                })
                ->first();

            if (!$assistant) {
                return [
                    'success' => false, 
                    'message' => 'Assistant not found or you do not have permission to access this assistant'
                ];
            }

            return [
                'success' => true,
                'message' => 'Assistant retrieved successfully',
                'assistant' => $assistant
            ];

        } catch (\Exception $e) {
            Log::error('[RichBotExecutor] get_assistant error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get contact interaction history
     * 
     * @param array $arguments
     * @return array
     */
    public function richbot_get_contact_history($arguments)
    {


        

        if(is_string($arguments)) {
            $arguments = json_decode($arguments, true);
        }

        Log::info('[RichBotExecutor] get_contact_history arguments: ' . json_encode($arguments));

        try {
            $contact_id = $arguments['contact_id'] ?? null;
            $limit = $arguments['limit'] ?? 10;

            if (!$contact_id) {
                return ['success' => false, 'error' => 'Contact ID is required'];
            }

            $history = DB::table('interactions')
                        ->where('contact_id', $contact_id)
                        ->orderBy('created_at', 'desc')
                        ->limit($limit)
                        ->get();

            return [
                'success' => true,
                'message' => 'Contact history retrieved successfully',
                'history' => $history
            ];

        } catch (\Exception $e) {
            Log::error('[RichBotExecutor] get_contact_history error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get available assistants for a specific time slot
     * 
     * @param array $arguments
     * @return array
     */
    public function richbot_get_available_assistants($arguments)
    {

        if(is_string($arguments)) {
            $arguments = json_decode($arguments, true);
        }

        Log::info('[RichBotExecutor] get_available_assistants arguments: ' . json_encode($arguments));

        try {
            $start_time = $arguments['start_time'] ?? null;
            $end_time = $arguments['end_time'] ?? null;
            $specialty = $arguments['specialty'] ?? null;

            if (!$start_time || !$end_time) {
                return ['success' => false, 'error' => 'Start time and end time are required'];
            }

            $query = DB::table('assistants')
                      ->where('availability', 'available')
                      ->where('start_time', '<=', $start_time)
                      ->where('end_time', '>=', $end_time);

            if ($specialty) {
                $query->where('specialty', $specialty);
            }

            $assistants = $query->get();

            return [
                'success' => true,
                'message' => 'Available assistants retrieved successfully',
                'assistants' => $assistants
            ];

        } catch (\Exception $e) {
            Log::error('[RichBotExecutor] get_available_assistants error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
} 