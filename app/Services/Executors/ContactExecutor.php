<?php

namespace App\Services\Executors;

use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\User;
use App\Models\Conversation;
use App\Models\ConversationPath;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;
use Twilio\Rest\Client as TwilioClient;
use Illuminate\Support\Facades\File;

class ContactExecutor
{
    private $user;
    private $conversation;

    public function __construct($user = null)
    {
        $this->user = $user ?? null;
    }

    public function setConversation($conversation)
    {
        $this->conversation = $conversation;
    }

    public function getMethodSchema()
    {
        return [
            [
                'name' => 'contact_list',
                'description' => 'List all contacts for the authenticated user',
                'strict' => true,
                'parameters' => []
            ],
            [
                'name' => 'contact_add',
                'description' => 'Add a new contact',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'email',
                        'type' => 'string',
                        'required' => false,
                        'description' => 'The email address of the contact'
                    ],
                    [
                        'name' => 'phone',
                        'type' => 'string',
                        'required' => false,
                        'description' => 'The phone number of the contact'
                    ],
                    [
                        'name' => 'name',
                        'type' => 'string',
                        'required' => false,
                        'description' => 'The name of the contact'
                    ],
                    [
                        'name' => 'type',
                        'type' => 'string',
                        'required' => false,
                        'description' => 'The type of contact (e.g., contact, lead, customer)'
                    ],
                    [
                        'name' => 'allowed_to_contact',
                        'type' => 'boolean',
                        'required' => false,
                        'description' => 'Whether the contact can be contacted'
                    ]
                ]
            ],
            [
                'name' => 'contact_update',
                'description' => 'Update an existing contact',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'contact_id',
                        'type' => 'integer',
                        'required' => true,
                        'description' => 'The ID of the contact to update'
                    ],
                    [
                        'name' => 'email',
                        'type' => 'string',
                        'required' => false,
                        'description' => 'The email address of the contact'
                    ],
                    [
                        'name' => 'phone',
                        'type' => 'string',
                        'required' => false,
                        'description' => 'The phone number of the contact'
                    ],
                    [
                        'name' => 'name',
                        'type' => 'string',
                        'required' => false,
                        'description' => 'The name of the contact'
                    ],
                    [
                        'name' => 'type',
                        'type' => 'string',
                        'required' => false,
                        'description' => 'The type of contact (e.g., contact, lead, customer)'
                    ],
                    [
                        'name' => 'allowed_to_contact',
                        'type' => 'boolean',
                        'required' => false,
                        'description' => 'Whether the contact can be contacted'
                    ]
                ]
            ],
            [
                'name' => 'contact_delete',
                'description' => 'Delete a contact',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'contact_id',
                        'type' => 'integer',
                        'required' => true,
                        'description' => 'The ID of the contact to delete'
                    ]
                ]
            ],
            [
                'name' => 'contact_get',
                'description' => 'Get details for a specific contact',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'contact_id',
                        'type' => 'integer',
                        'required' => true,
                        'description' => 'The ID of the contact to retrieve'
                    ]
                ]
            ],
            [
                'name' => 'contact_search',
                'description' => 'Search for contacts',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'search_term',
                        'type' => 'string',
                        'required' => true,
                        'description' => 'The term to search for'
                    ],
                    [
                        'name' => 'search_type',
                        'type' => 'string',
                        'required' => false,
                        'description' => 'The type of search (all, name, email, phone)'
                    ]
                ]
            ],
            [
                'name' => 'contact_start_opt_in',
                'description' => 'Start the opt-in process for a contact',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'contact_id',
                        'type' => 'integer',
                        'required' => true,
                        'description' => 'The ID of the contact to start opt-in process for'
                    ]
                ]
            ],
            [
                'name' => 'contact_opt_in',
                'description' => 'Complete the opt-in process for a contact',
                'strict' => true,
                'parameters' => [
                    [
                        'name' => 'contact_id',
                        'type' => 'integer',
                        'required' => true,
                        'description' => 'The ID of the contact to opt in'
                    ],
                    [
                        'name' => 'conversation_id',
                        'type' => 'integer',
                        'required' => false,
                        'description' => 'The ID of the conversation handling the opt-in process'
                    ]
                ]
            ]
        ];
    }

    /**
     * List all contacts for the authenticated user.
     * 
     * @param array $arguments No parameters required
     * @return array Returns an array with:
     *               - success: boolean indicating if the operation was successful
     *               - data: Collection of Contact models with their contact groups
     *               - error: string error message if success is false
     */
    public function contact_list($arguments)
    {
        try {
            $contacts = Contact::whereHas('contactGroups', function($query) {
                $query->where('groupable_type', User::class)
                      ->where('groupable_id', $this->user->id)
                      ->where('type', '!=', 'deleted');
            })->with(['contactGroups' => function($query) {
                $query->where('groupable_type', User::class)
                      ->where('groupable_id', $this->user->id);
            }])->get();
            
            return ['success' => true, 'data' => $contacts];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add a new contact to the user's contact list.
     * 
     * @param array $arguments Parameters:
     *                        - email: string (optional) Contact's email address
     *                        - phone: string (optional) Contact's phone number
     *                        - name: string (optional) Contact's name
     *                        - type: string (optional) Contact type (default: 'contact')
     *                        - allowed_to_contact: boolean (optional) Whether contact can be reached
     * @return array Returns an array with:
     *               - success: boolean indicating if the operation was successful
     *               - data: Contact model with its contact groups
     *               - error: string error message if success is false
     */
    public function contact_add($arguments)
    {
        try {
            $email = $arguments['email'] ?? null;
            $phone = $arguments['phone'] ?? null;
            $name = $arguments['name'] ?? null;
            $type = $arguments['type'] ?? 'contact';
            $allowedToContact = $arguments['allowed_to_contact'] ?? true;

            if (empty($email) && empty($phone)) {
                return ['success' => false, 'error' => 'Either email or phone must be provided'];
            }

            // Find or create contact
            $contact = Contact::firstOrCreate(
                ['email' => $email],
                [
                    'user_id' => $this->user->id,
                    'phone' => $phone
                ]
            );

            // Create or update user's contact group
            $contactGroup = ContactGroup::firstOrCreate(
                [
                    'contact_id' => $contact->id,
                    'groupable_type' => User::class,
                    'groupable_id' => $this->user->id
                ],
                [
                    'name' => $name ?? $contact->email,
                    'type' => $type,
                    'allowed_to_contact' => $allowedToContact
                ]
            );

            return ['success' => true, 'data' => $contact->load('contactGroups')];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Start the opt-in process for a contact by initiating a phone call.
     * 
     * @param array $arguments Parameters:
     *                        - contact_id: integer (required) ID of the contact to start opt-in for
     * @return array Returns an array with:
     *               - success: boolean indicating if the operation was successful
     *               - message: string success message
     *               - data: array containing conversation_id and call_sid
     *               - error: string error message if success is false
     */
    public function contact_start_opt_in($arguments)
    {
        try {
            $contactId = $arguments['contact_id'] ?? null;
            
            if (!$contactId) {
                return ['success' => false, 'error' => 'Contact ID is required'];
            }

            $contact = Contact::findOrFail($contactId);

            // Create a conversation for the opt-in process
            $conversation = Conversation::create([
                'title' => "Opt-in Process for {$contact->email}",
                'type' => 'opt_in',
                'status' => 'active',
                'user_id' => $this->user->id
            ]);

            // Add initial system message
            $conversation->addMessage('system', "Starting opt-in process for contact: {$contact->email}");

            // Format phone number
            $targetNumber = $this->formatPhoneNumber($contact->phone);

            $conversation->room = $conversation->id;
            $conversation->save();

            // Associate with the opt-in conversation path
            $conversationPath = ConversationPath::where('name', 'RichBot 9000 Opt-In')->first();
            $conversation->conversationPath()->associate($conversationPath);
            $conversation->save();

            // Initialize Twilio client
            $sid = env('TWILIO_SID');
            $token = env('TWILIO_TOKEN');
            $twilioNumber = env('TWILIO_FROM');
            $client = new TwilioClient($sid, $token);

            // Make the call
            $call = $client->calls->create(
                $targetNumber,
                $twilioNumber,
                ["url" => "'.config('app.url')."/api/conversation-path-call/continue/{$conversation->id}"]
            );

            // Update conversation with Twilio call details
            $path_state = $conversation->path_state ?? [];
            $path_state['twilio_call'] = [
                'CallSid' => $call->sid,
                'CallStatus' => $call->status,
                'CallDuration' => $call->duration,
                'CallDirection' => $call->direction,
                'CallFrom' => $call->from,
                'CallTo' => $call->to,
                'twilio_number' => $twilioNumber,
            ];
            $path_state['contact'] = $contact;
            $conversation->path_state = $path_state;
            $conversation->save();

            Log::channel('openai_tools')->info('Contact opt-in started', [
                'contact_id' => $contact->id,
                'conversation_id' => $conversation->id,
                'call_sid' => $call->sid
            ]);

            return [
                'success' => true,
                'message' => 'Opt-in process started',
                'data' => [
                    'conversation_id' => $conversation->id,
                    'call_sid' => $call->sid
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Format a phone number to international format.
     * 
     * @param string $number Phone number to format
     * @return string Formatted phone number with country code
     */
    private function formatPhoneNumber($number)
    {
        // Remove any non-digit characters
        $number = preg_replace('/[^0-9]/', '', $number);
        
        // Add country code if not present
        if (strlen($number) === 10) {
            $number = '1' . $number;
        }
        
        return '+' . $number;
    }

    /**
     * Complete the opt-in process for a contact.
     * 
     * @param array $arguments Parameters:
     *                        - contact_id: integer (required) ID of the contact to opt in
     *                        - conversation_id: integer (optional) ID of the opt-in conversation
     * @return array Returns an array with:
     *               - success: boolean indicating if the operation was successful
     *               - message: string success message
     *               - data: array containing contact details and opt-in timestamp
     *               - error: string error message if success is false
     */
    public function contact_opt_in($arguments)
    {


        File::put(storage_path('logs/openai_tools.log'), json_encode($arguments, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);


        try {
            $contactId = $arguments['contact_id'] ?? null;
            $conversationId = $arguments['conversation_id'] ?? null;
            
            if (!$contactId) {
                return ['success' => false, 'error' => 'Contact ID is required'];
            }

            $contact = Contact::findOrFail($contactId);

            // Update contact's opt-in status
            $contact->update([
                'opt_in_at' => now()
            ]);

            // If we have a conversation ID, update its status
            if ($conversationId) {
                $conversation = Conversation::find($conversationId);
                if ($conversation) {
                    $conversation->update([
                        'status' => 'completed'
                    ]);
                    
                    // Add completion message
                    $conversation->addMessage('system', "Contact {$contact->email} has been opted in successfully");
                }
            }

            return [
                'success' => true,
                'message' => 'Contact has been opted in successfully',
                'data' => [
                    'contact' => $contact,
                    'opt_in_at' => $contact->opt_in_at
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update an existing contact's details.
     * 
     * @param array $arguments Parameters:
     *                        - contact_id: integer (required) ID of the contact to update
     *                        - email: string (optional) New email address
     *                        - phone: string (optional) New phone number
     *                        - name: string (optional) New name
     *                        - type: string (optional) New contact type
     *                        - allowed_to_contact: boolean (optional) New contact permission
     * @return array Returns an array with:
     *               - success: boolean indicating if the operation was successful
     *               - message: string success message
     *               - data: Contact model with updated contact groups
     *               - error: string error message if success is false
     */
    public function contact_update($arguments)
    {
        try {
            $contactId = $arguments['contact_id'] ?? null;
            
            if (!$contactId) {
                return ['success' => false, 'error' => 'Contact ID is required'];
            }

            $contact = Contact::findOrFail($contactId);

            // Update contact details
            $contact->update([
                'email' => $arguments['email'] ?? $contact->email,
                'phone' => $arguments['phone'] ?? $contact->phone,
                'type' => $arguments['type'] ?? $contact->type
            ]);

            // Update contact group
            $contactGroup = ContactGroup::where('contact_id', $contact->id)
                ->where('groupable_type', User::class)
                ->where('groupable_id', $this->user->id)
                ->first();

            if ($contactGroup) {
                $contactGroup->update([
                    'name' => $arguments['name'] ?? $contactGroup->name,
                    'type' => $arguments['type'] ?? $contactGroup->type,
                    'allowed_to_contact' => $arguments['allowed_to_contact'] ?? $contactGroup->allowed_to_contact
                ]);
            }

            return [
                'success' => true,
                'message' => 'Contact updated successfully',
                'data' => $contact->load('contactGroups')
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Soft delete a contact by marking its group as deleted.
     * 
     * @param array $arguments Parameters:
     *                        - contact_id: integer (required) ID of the contact to delete
     * @return array Returns an array with:
     *               - success: boolean indicating if the operation was successful
     *               - message: string success message
     *               - error: string error message if success is false
     */
    public function contact_delete($arguments)
    {
        try {
            $contactId = $arguments['contact_id'] ?? null;
            
            if (!$contactId) {
                return ['success' => false, 'error' => 'Contact ID is required'];
            }

            $contact = Contact::findOrFail($contactId);

            // Instead of deleting, update the contact group type
            $contactGroup = ContactGroup::where('contact_id', $contact->id)
                ->where('groupable_type', User::class)
                ->where('groupable_id', $this->user->id)
                ->first();

            if ($contactGroup) {
                $contactGroup->update([
                    'type' => 'deleted',
                    'allowed_to_contact' => false
                ]);
            }

            return [
                'success' => true,
                'message' => 'Contact deleted successfully'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get detailed information about a specific contact.
     * 
     * @param array $arguments Parameters:
     *                        - contact_id: integer (required) ID of the contact to retrieve
     * @return array Returns an array with:
     *               - success: boolean indicating if the operation was successful
     *               - data: Contact model with its contact groups
     *               - error: string error message if success is false
     */
    public function contact_get($arguments)
    {
        try {
            $contactId = $arguments['contact_id'] ?? null;
            
            if (!$contactId) {
                return ['success' => false, 'error' => 'Contact ID is required'];
            }

            $contact = Contact::with(['contactGroups' => function($query) {
                $query->where('groupable_type', User::class)
                      ->where('groupable_id', $this->user->id);
            }])->findOrFail($contactId);

            return [
                'success' => true,
                'data' => $contact
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Search for contacts based on various criteria.
     * 
     * @param array $arguments Parameters:
     *                        - search_term: string (required) Term to search for
     *                        - search_type: string (optional) Type of search (all, name, email, phone)
     * @return array Returns an array with:
     *               - success: boolean indicating if the operation was successful
     *               - data: Collection of matching Contact models with their contact groups
     *               - error: string error message if success is false
     */
    public function contact_search($arguments)
    {
        try {
            $searchTerm = $arguments['search_term'] ?? '';
            $searchType = $arguments['search_type'] ?? 'all';
            
            if (empty($searchTerm)) {
                return ['success' => false, 'error' => 'Search term is required'];
            }

            $query = Contact::whereHas('contactGroups', function($query) {
                $query->where('groupable_type', User::class)
                      ->where('groupable_id', $this->user->id)
                      ->where('type', '!=', 'deleted');
            });

            switch ($searchType) {
                case 'name':
                    $query->whereHas('contactGroups', function($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%");
                    });
                    break;
                case 'email':
                    $query->where('email', 'like', "%{$searchTerm}%");
                    break;
                case 'phone':
                    $query->where('phone', 'like', "%{$searchTerm}%");
                    break;
                default:
                    $query->where(function($q) use ($searchTerm) {
                        $q->where('email', 'like', "%{$searchTerm}%")
                          ->orWhere('phone', 'like', "%{$searchTerm}%")
                          ->orWhereHas('contactGroups', function($q) use ($searchTerm) {
                              $q->where('name', 'like', "%{$searchTerm}%");
                          });
                    });
            }

            $contacts = $query->with(['contactGroups' => function($query) {
                $query->where('groupable_type', User::class)
                      ->where('groupable_id', $this->user->id);
            }])->get();

            return [
                'success' => true,
                'data' => $contacts
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
} 