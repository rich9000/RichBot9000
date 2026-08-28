<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\Survey;
use App\Models\SurveyCampaign;
use App\Models\SurveyContact;
use App\Models\SurveyResponse;
use App\Models\Conversation;
use App\Models\ConversationPath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Events\StartPhoneCall;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;


class SurveyCampaignController extends Controller
{


    

    /**
     * Display a listing of campaigns for a survey.
     */
    public function index(Survey $survey)
    {
       // $this->authorize('view', $survey);
        
        $campaigns = $survey->campaigns()
            ->with('creator:id,name')
            ->withCount('surveyContacts')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($campaigns);
    }

    /**
     * Store a newly created campaign in storage.
     */
    public function store(Request $request, Survey $survey)
    {
       // $this->authorize('update', $survey);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:pending,active,completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $campaign = $survey->campaigns()->create([
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status ?? 'pending',
            'created_by' => Auth::id(),
        ]);

        return response()->json($campaign, 201);
    }

    /**
     * Display the specified campaign.
     */
    public function show(SurveyCampaign $campaign)
    {
        //$this->authorize('view', $campaign->survey);
        

        $campaign->load(['creator:id,name', 'survey:id,title','responses']);
        
        return response()->json($campaign);
    }

    /**
     * Update the specified campaign in storage.
     */
    public function update(Request $request, SurveyCampaign $campaign)
    {
        //$this->authorize('update', $campaign->survey);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:pending,active,completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $campaign->update([
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status ?? $campaign->status,
        ]);

        return response()->json($campaign);
    }

    /**
     * Remove the specified campaign from storage.
     */
    public function destroy(SurveyCampaign $campaign)
    {
        //$this->authorize('update', $campaign->survey);
        
        $campaign->delete();

        return response()->json(null, 204);
    }

    /**
     * Get all contacts for a campaign.
     */
    public function getContacts(SurveyCampaign $campaign)
    {
        $contacts = $campaign->surveyContacts()
            ->with(['contactGroup.contact:id,email,phone,opt_in_at'])
            ->get();
        
        return response()->json($contacts);
    }

    /**
     * Add contacts to a campaign.
     */
    public function addContacts(Request $request, SurveyCampaign $campaign)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'integer|exists:contacts,id',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $addedContacts = [];
        $errors = [];

        // Add existing contacts by IDs
        if ($request->has('contact_ids') && is_array($request->contact_ids)) {
            foreach ($request->contact_ids as $contactId) {
                try {
                    $contact = Contact::findOrFail($contactId);
                    
                    // Create or get user's contact group
                    $userContactGroup = ContactGroup::firstOrCreate(
                        [
                            'contact_id' => $contact->id,
                            'groupable_type' => User::class,
                            'groupable_id' => $user->id
                        ],
                        [
                            'name' => $contact->email,
                            'type' => 'contact',
                            'allowed_to_contact' => true
                        ]
                    );

                    // Create campaign's contact group
                    $campaignContactGroup = ContactGroup::firstOrCreate(
                        [
                            'contact_id' => $contact->id,
                            'groupable_type' => SurveyCampaign::class,
                            'groupable_id' => $campaign->id
                        ],
                        [
                            'name' => $userContactGroup->name,
                            'type' => 'survey_contact',
                            'allowed_to_contact' => true
                        ]
                    );

                    // Create survey contact
                    $surveyContact = SurveyContact::firstOrCreate(
                        [
                            'survey_campaign_id' => $campaign->id,
                            'contact_group_id' => $campaignContactGroup->id
                        ],
                        ['status' => 'pending']
                    );

                    if ($surveyContact->wasRecentlyCreated) {
                        $addedContacts[] = $surveyContact;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error adding contact ID {$contactId}: " . $e->getMessage();
                }
            }
        }

        // Add new contact by email
        if ($request->has('email') && $request->email) {
            try {
                // Find or create contact
                $contact = Contact::firstOrCreate(
                    ['email' => $request->email],
                    [
                        'user_id' => $user->id,
                        'phone' => $request->phone,
                        'opt_in_at' => now()
                    ]
                );

                // Create user's contact group
                $userContactGroup = ContactGroup::firstOrCreate(
                    [
                        'contact_id' => $contact->id,
                        'groupable_type' => User::class,
                        'groupable_id' => $user->id
                    ],
                    [
                        'name' => $request->name ?? $contact->email,
                        'type' => 'contact',
                        'allowed_to_contact' => true
                    ]
                );

                // Create campaign's contact group
                $campaignContactGroup = ContactGroup::firstOrCreate(
                    [
                        'contact_id' => $contact->id,
                        'groupable_type' => SurveyCampaign::class,
                        'groupable_id' => $campaign->id
                    ],
                    [
                        'name' => $userContactGroup->name,
                        'type' => 'survey_contact',
                        'allowed_to_contact' => true
                    ]
                );

                // Create survey contact
                $surveyContact = SurveyContact::firstOrCreate(
                    [
                        'survey_campaign_id' => $campaign->id,
                        'contact_group_id' => $campaignContactGroup->id
                    ],
                    ['status' => 'pending']
                );

                if ($surveyContact->wasRecentlyCreated) {
                    $addedContacts[] = $surveyContact;
                }
            } catch (\Exception $e) {
                $errors[] = "Error adding contact with email {$request->email}: " . $e->getMessage();
            }
        }

        return response()->json([
            'message' => count($addedContacts) . ' contacts added to campaign',
            'added_contacts' => $addedContacts,
            'errors' => $errors
        ]);
    }

    /**
     * Remove a contact from a campaign.
     */
    public function removeContact(SurveyCampaign $campaign, SurveyContact $surveyContact)
    {
        // Ensure the contact belongs to this campaign
        if ($surveyContact->survey_campaign_id !== $campaign->id) {
            return response()->json(['message' => 'Contact not found in this campaign'], 404);
        }
        
        $surveyContact->delete();

        return response()->json(null, 204);
    }

    /**
     * Start a survey call for a contact.
     */
    public function startSurvey(Request $request, SurveyCampaign $campaign, SurveyContact $surveyContact)
    {
        //$this->authorize('update', $campaign->survey);
        
        // Ensure the contact belongs to this campaign
        if ($surveyContact->survey_campaign_id !== $campaign->id) {
            return response()->json(['message' => 'Contact not found in this campaign'], 404);
        }

        $targetNumber = $this->formatPhoneNumber($surveyContact->contact->phone);





        Log::info("[SurveyCampaignController][startSurvey] **********************", 
        ['surveyContact' => $surveyContact,
        'campaign' => $campaign,
        'request' => $request->all()   ]);




        $validator = Validator::make($request->all(), [
            'style' => 'required|in:casual,formal'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
           
            $response = SurveyResponse::create([
                'survey_id' => $campaign->survey_id,
                'survey_campaign_id' => $campaign->id,
                'survey_contact_id' => $surveyContact->id,
                'contact_id' => $surveyContact->contact_id,
                'started_at' => now()
            ]);

            // Update contact status
            $surveyContact->update([
                'status' => 'in_progress',
                'sent_at' => now()
            ]);

            $pathState = [
                'survey_id' => $campaign->survey_id,
                'campaign_id' => $campaign->id,
                'response_id' => $response->id,
                'contact' => $surveyContact->contact,
                'style' => $request->style,
                'contact_id' => $surveyContact->contact_id,
                'campaign_contact_id' => $surveyContact->id
            ];

            // Create a conversation with the survey path
            $conversation = Conversation::create([
                'contact_id' => $surveyContact->contact_id,
                'type' => 'survey_call',
                'status' => 'active',                
                'path_state' => $pathState
            ]);            
            
            $conversation->room = $conversation->id;
            $conversation->save();
                
                $conversationPath = ConversationPath::where('name','RichBot 9000 Survey')->first();
                $conversation->conversationPath()->associate($conversationPath);
                $conversation->save();



                $sid = env('TWILIO_SID');
                $token = env('TWILIO_TOKEN');
                $twilioNumber = env('TWILIO_FROM');
           

          
            $call = $client = new TwilioClient($sid, $token);

            Log::info("Starting call to $targetNumber from $twilioNumber in room $conversation->room");

            Log::info("'.config('app.url')."/api/conversation-path-call/continue/{$conversation->id}");
            Log::info("conversationId: {$conversation->id}",['conversation'=>$conversation]);

            $call = $client->calls->create(
                $targetNumber,
                $twilioNumber,
                ["url" => "'.config('app.url')."/api/conversation-path-call/continue/{$conversation->id}"]
            );


            $path_state = $conversation->path_state;
            $path_state['twilio_call'] = [
                'CallSid' => $call->sid,
                'CallStatus' => $call->status,
                'CallDuration' => $call->duration,
                'CallDirection' => $call->direction,
                'CallFrom' => $call->from,
                'CallTo' => $call->to,
                'twilio_number' => $twilioNumber,
            ];
            $conversation->path_state = $path_state;
            $conversation->save();


            
            Log::info('[SurveyCampaignController][startSurvey]', [
                'phone_number' => $targetNumber,
                'room' => $conversation->room,
                'call_sid' => $call->sid,
                'conversation_id' => $conversation->id,
                'conversation_path' => $conversation->conversationPath->name,
                'path_state' => $pathState
            ]);

            return response()->json([
                'message' => 'Survey call started successfully',
                'data' => [
                    'response_id' => $response->id,
                    'conversation_id' => $conversation->id,
                    'call_sid' => $call->sid
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to start survey call', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Start a campaign for multiple contacts.
     */
    public function bulkStartCampaign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'campaign_id' => 'required|exists:survey_campaigns,id',
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:survey_contacts,id',
            'style' => 'required|in:casual,formal'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $campaign = SurveyCampaign::findOrFail($request->campaign_id);
            $results = [];
            $errors = [];

            foreach ($request->contact_ids as $contactId) {
                try {
                    $surveyContact = SurveyContact::findOrFail($contactId);
                    
                    // Ensure the contact belongs to this campaign
                    if ($surveyContact->survey_campaign_id !== $campaign->id) {
                        $errors[] = "Contact ID {$contactId} not found in this campaign";
                        continue;
                    }

                    // Create a new survey response
                    $response = SurveyResponse::create([
                        'survey_id' => $campaign->survey_id,
                        'survey_campaign_id' => $campaign->id,
                        'survey_contact_id' => $surveyContact->id,
                        'contact_id' => $surveyContact->contact_id,
                        'started_at' => now()
                    ]);

                    // Update contact status
                    $surveyContact->update([
                        'status' => 'in_progress',
                        'sent_at' => now()
                    ]);

                    // Create a conversation with the survey path
                    $conversation = Conversation::create([
                        'contact_id' => $surveyContact->contact_id,
                        'type' => 'survey',
                        'status' => 'active',
                        'metadata' => [
                            'survey_id' => $campaign->survey_id,
                            'campaign_id' => $campaign->id,
                            'response_id' => $response->id,
                            'style' => $request->style
                        ]
                    ]);

                    // Trigger the phone call
                    event(new StartPhoneCall($conversation));

                    $results[] = [
                        'contact_id' => $contactId,
                        'response_id' => $response->id,
                        'conversation_id' => $conversation->id
                    ];
                } catch (\Exception $e) {
                    $errors[] = "Error processing contact ID {$contactId}: " . $e->getMessage();
                }
            }

            return response()->json([
                'message' => count($results) . ' survey calls started successfully',
                'results' => $results,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to start survey calls', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Start a new campaign for a survey with multiple contacts.
     */
    public function startSurveyCampaign(Request $request, Survey $survey)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
            'style' => 'required|in:casual,formal'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Create the campaign
            $campaign = $survey->campaigns()->create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => 'active',
                'created_by' => Auth::id()
            ]);

            $results = [];
            $errors = [];

            // Add contacts to the campaign and start surveys
            foreach ($request->contact_ids as $contactId) {
                try {
                    // Create survey contact
                    $surveyContact = SurveyContact::create([
                        'survey_campaign_id' => $campaign->id,
                        'contact_id' => $contactId,
                        'status' => 'pending'
                    ]);

                    // Create survey response
                    $response = SurveyResponse::create([
                        'survey_id' => $survey->id,
                        'survey_campaign_id' => $campaign->id,
                        'survey_contact_id' => $surveyContact->id,
                        'contact_id' => $contactId,
                        'started_at' => now()
                    ]);

                    // Update contact status
                    $surveyContact->update([
                        'status' => 'in_progress',
                        'sent_at' => now()
                    ]);

                    // Create conversation
                    $conversation = Conversation::create([
                        'contact_id' => $contactId,
                        'type' => 'survey',
                        'status' => 'active',
                        'metadata' => [
                            'survey_id' => $survey->id,
                            'campaign_id' => $campaign->id,
                            'response_id' => $response->id,
                            'style' => $request->style
                        ]
                    ]);

                    // Trigger the phone call
                    event(new StartPhoneCall($conversation));

                    $results[] = [
                        'contact_id' => $contactId,
                        'survey_contact_id' => $surveyContact->id,
                        'response_id' => $response->id,
                        'conversation_id' => $conversation->id
                    ];
                } catch (\Exception $e) {
                    $errors[] = "Error processing contact ID {$contactId}: " . $e->getMessage();
                }
            }

            return response()->json([
                'message' => 'Campaign created and ' . count($results) . ' survey calls started successfully',
                'campaign_id' => $campaign->id,
                'results' => $results,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create campaign and start survey calls', 'error' => $e->getMessage()], 500);
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
} 