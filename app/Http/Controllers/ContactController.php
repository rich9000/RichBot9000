<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\User;
use App\Models\Conversation;
use App\Models\ConversationPath;
use Illuminate\Http\Request;
use Twilio\Rest\Client as TwilioClient;

class ContactController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        if ($user->id === 1) {
            // Admin sees all contacts with their groups
            $contacts = Contact::with(['contactGroups' => function($query) {
                $query->where('groupable_type', User::class);
            }])->get();
        } else {
            // Regular users only see their non-deleted contacts
            $contacts = Contact::whereHas('contactGroups', function($query) use ($user) {
                $query->where('groupable_type', User::class)
                      ->where('groupable_id', $user->id)
                      ->where('type', '!=', 'deleted');
            })->with(['contactGroups' => function($query) use ($user) {
                $query->where('groupable_type', User::class)
                      ->where('groupable_id', $user->id);
            }])->get();
        }
        
        return response()->json([
            'draw' => request()->get('draw'),
            'recordsTotal' => $contacts->count(),
            'recordsFiltered' => $contacts->count(),
            'data' => $contacts
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20', 
            'type' => 'nullable|string|max:50',
            'allowed_to_contact' => 'nullable|boolean'
        ], [
            'email.email' => 'The email must be a valid email address.',
            'phone.max' => 'The phone number cannot be longer than 20 characters.'
        ]);

        // Ensure at least one of email or phone is provided
        if (empty($validated['email']) && empty($validated['phone'])) {
            return response()->json([
                'message' => 'Either email or phone must be provided.',
                'errors' => [
                    'email' => ['Either email or phone must be provided.'],
                    'phone' => ['Either email or phone must be provided.']
                ]
            ], 422);
        }

        // Try to find existing contact by email or phone
        $contact = null;
        if (!empty($validated['email'])) {
            $contact = Contact::where('email', $validated['email'])->first();
        }
        if (!$contact && !empty($validated['phone'])) {
            $contact = Contact::where('phone', $validated['phone'])->first();
        }

        // If no existing contact, create new one
        if (!$contact) {
            $contact = Contact::create([
                'user_id' => auth()->id(),
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'type' => $validated['type'] ?? 'contact'
            ]);
        }

        // Create or update the contact group
        $contactGroup = ContactGroup::updateOrCreate(
            [
                'contact_id' => $contact->id,
                'groupable_type' => User::class,
                'groupable_id' => $request->user()->id
            ],
            [
                'name' => $validated['name'],
                'type' => $validated['type'] ?? 'contact',
                'allowed_to_contact' => $validated['allowed_to_contact'] ?? true
            ]
        );

        // Load the relationship before returning
        $contact->load(['contactGroups' => function($query) {
            $query->where('groupable_type', User::class)
                  ->where('groupable_id', auth()->id());
        }]);

        return response()->json($contact, 201);
    }

    public function show(Contact $contact)
    {
        $contact->load(['contactGroups' => function($query) {
            $query->where('groupable_type', User::class)
                  ->where('groupable_id', auth()->id());
        }]);
        
        return response()->json($contact);
    }

    public function update(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'type' => 'nullable|string|max:50',
            'allowed_to_contact' => 'nullable|boolean'
        ], [
            'email.email' => 'The email must be a valid email address.',
            'phone.max' => 'The phone number cannot be longer than 20 characters.'
        ]);

        // Ensure at least one of email or phone is provided
        if (empty($validated['email']) && empty($validated['phone'])) {
            return response()->json([
                'message' => 'Either email or phone must be provided.',
                'errors' => [
                    'email' => ['Either email or phone must be provided.'],
                    'phone' => ['Either email or phone must be provided.']
                ]
            ], 422);
        }

        // Update contact details
        $contact->update([
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'type' => $validated['type'] ?? 'contact'
        ]);

        // Update the contact group
        $contactGroup = ContactGroup::updateOrCreate(
            [
                'contact_id' => $contact->id,
                'groupable_type' => User::class,
                'groupable_id' => auth()->id()
            ],
            [
                'name' => $validated['name'],
                'type' => $validated['type'] ?? 'contact',
                'allowed_to_contact' => $validated['allowed_to_contact'] ?? true
            ]
        );

        // Reload the contact with group data
        $contact->load(['contactGroups' => function($query) {
            $query->where('groupable_type', User::class)
                  ->where('groupable_id', auth()->id());
        }]);

        return response()->json($contact);
    }

    public function destroy(Contact $contact)
    {
        // Instead of deleting, update the contact group type
        $contactGroup = ContactGroup::where('contact_id', $contact->id)
            ->where('groupable_type', User::class)
            ->where('groupable_id', auth()->id())
            ->first();

        if ($contactGroup) {
            $contactGroup->update([
                'type' => 'deleted',
                'allowed_to_contact' => false
            ]);
        }

        return response()->json(null, 200);
    }

    public function startOptInProcess(Request $request, Contact $contact)
    {
        // Create a conversation for the opt-in process
        $conversation = Conversation::create([
            'title' => "Opt-in Process for {$contact->email}",
            'type' => 'opt_in',
            'status' => 'active',
            'user_id' => auth()->id()
        ]);

        // Add initial system message
        $conversation->addMessage('system', "Starting opt-in process for contact: {$contact->email}");

        // Format phone number
        $targetNumber = $this->formatPhoneNumber($contact->phone);

        try {
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

            // Return the conversation ID for tracking
            return response()->json([
                'conversation_id' => $conversation->id,
                'message' => 'Opt-in process started',
                'call_sid' => $call->sid
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to start opt-in call',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format phone number to standard format
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

    public function optOut(Contact $contact)
    {
        // Update the contact's opt-in status
        $contact->update([
            'opt_in_at' => null
        ]);

        // Create a conversation to log the opt-out
        $conversation = Conversation::create([
            'title' => "Opt-out Process for {$contact->email}",
            'type' => 'opt_out',
            'status' => 'completed',
            'user_id' => auth()->id()
        ]);

        // Add system message
        $conversation->addMessage('system', "Contact {$contact->email} has been opted out");

        return response()->json([
            'message' => 'Contact has been opted out successfully'
        ]);
    }
}
