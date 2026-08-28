<?php

namespace App\Services\Executors;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyCampaign;
use App\Models\SurveyContact;
use App\Models\Contact;
use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\ContactGroup;
use App\Models\User;

class SurveyExecutor
{

    private $conversation;
    private $user;

    public function __construct($user = null)
    {
        $this->user = $user;
    }


  


    public function setConversation($conversation)
    {
        $this->conversation = $conversation;
    }






    /**
     * List all surveys for the authenticated user.
     */
    public function survey_list($arguments)
    {
        try {
            $status = $arguments['status'] ?? 'all';
            $surveys = Survey::when($status !== 'all', function($query) use ($status) {
                return $query->where('status', $status);
            })->get();
            
            return ['success' => true, 'data' => $surveys];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a new survey.
     */
    public function survey_create($arguments)
    {
        $validator = Validator::make($arguments, [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:draft,active,archived',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'error' => $validator->errors()->all()];
        }

        try {
            $survey = Survey::create([
                'title' => $arguments['title'],
                'description' => $arguments['description'] ?? null,
                'status' => $arguments['status'] ?? 'draft',
                'created_by' => $this->user->id,
            ]);

            return ['success' => true, 'data' => $survey];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get a specific survey's details.
     */
    public function survey_get($arguments)
    {
        $surveyId = $arguments['survey_id'] ?? null;
        
        if (!$surveyId) {
            return ['success' => false, 'error' => 'Survey ID is required'];
        }

        try {
            $survey = Survey::with(['creator:id,name', 'questions' => function($query) {
                $query->orderBy('order');
            }])->find($surveyId);

            if (!$survey) {
                return ['success' => false, 'error' => 'Survey not found'];
            }

            if($this->conversation){

                $path_state = $this->conversation->path_state;

                $path_state['survey_info'] = $survey;

                $this->conversation->path_state = $path_state;

                $this->conversation->save();

            }

            return ['success' => true, 'data' => $survey];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update a survey.
     */
    public function survey_update($arguments)
    {
        $surveyId = $arguments['survey_id'] ?? null;
        
        if (!$surveyId) {
            return ['success' => false, 'error' => 'Survey ID is required'];
        }

        $validator = Validator::make($arguments, [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:draft,active,archived',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'error' => $validator->errors()->all()];
        }

        try {
            $survey = Survey::find($surveyId);
            
            if (!$survey) {
                return ['success' => false, 'error' => 'Survey not found'];
            }

            $survey->update([
                'title' => $arguments['title'],
                'description' => $arguments['description'] ?? $survey->description,
                'status' => $arguments['status'] ?? $survey->status,
            ]);

            return ['success' => true, 'data' => $survey];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a survey.
     */
    public function survey_delete($arguments)
    {
        $surveyId = $arguments['survey_id'] ?? null;
        
        if (!$surveyId) {
            return ['success' => false, 'error' => 'Survey ID is required'];
        }

        try {
            $survey = Survey::find($surveyId);
            
            if (!$survey) {
                return ['success' => false, 'error' => 'Survey not found'];
            }

            $survey->delete();
            return ['success' => true, 'message' => 'Survey deleted successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add a question to a survey.
     */
    public function survey_question_create($arguments)
    {
        $surveyId = $arguments['survey_id'] ?? null;
        
        if (!$surveyId) {
            return ['success' => false, 'error' => 'Survey ID is required'];
        }

        $validator = Validator::make($arguments, [
            'question_text' => 'required|string',
            'question_type' => 'required|string',
            'options' => 'nullable|string',
            'required' => 'boolean',
            'order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'error' => $validator->errors()->all()];
        }

        try {
            $survey = Survey::find($surveyId);
            
            if (!$survey) {
                return ['success' => false, 'error' => 'Survey not found'];
            }

            $maxOrder = $survey->questions()->max('order') ?? 0;

            // Process options string into array
            $options = null;
            if (!empty($arguments['options'])) {
                // Try to decode as JSON first
                $jsonOptions = json_decode($arguments['options'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $options = $jsonOptions;
                } else {
                    // If not JSON, try CSV
                    $options = array_map('trim', explode(',', $arguments['options']));
                }
            }

            $question = $survey->questions()->create([
                'question_text' => $arguments['question_text'],
                'question_type' => $arguments['question_type'],
                'options' => $options,
                'required' => $arguments['required'] ?? true,
                'order' => $arguments['order'] ?? ($maxOrder + 1),
            ]);

            return ['success' => true, 'data' => $question];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update a survey question.
     */
    public function survey_question_update($arguments)
    {
        $questionId = $arguments['question_id'] ?? null;
        
        if (!$questionId) {
            return ['success' => false, 'error' => 'Question ID is required'];
        }

        $validator = Validator::make($arguments, [
            'question_text' => 'required|string',
            'question_type' => 'required|string',
            'options' => 'nullable|string',
            'required' => 'boolean',
            'order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'error' => $validator->errors()->all()];
        }

        try {
            $question = SurveyQuestion::find($questionId);
            
            if (!$question) {
                return ['success' => false, 'error' => 'Question not found'];
            }

            // Process options string into array
            $options = null;
            if (!empty($arguments['options'])) {
                // Try to decode as JSON first
                $jsonOptions = json_decode($arguments['options'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $options = $jsonOptions;
                } else {
                    // If not JSON, try CSV
                    $options = array_map('trim', explode(',', $arguments['options']));
                }
            }

            $question->update([
                'question_text' => $arguments['question_text'],
                'question_type' => $arguments['question_type'],
                'options' => $options,
                'required' => $arguments['required'] ?? $question->required,
                'order' => $arguments['order'] ?? $question->order,
            ]);

            return ['success' => true, 'data' => $question];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a survey question.
     */
    public function survey_question_delete($arguments)
    {
        $questionId = $arguments['question_id'] ?? null;
        
        if (!$questionId) {
            return ['success' => false, 'error' => 'Question ID is required'];
        }

        try {
            $question = SurveyQuestion::find($questionId);
            
            if (!$question) {
                return ['success' => false, 'error' => 'Question not found'];
            }

            $survey = $question->survey;
            $question->delete();

            // Reorder remaining questions
            $questions = $survey->questions()->orderBy('order')->get();
            foreach ($questions as $index => $q) {
                $q->update(['order' => $index + 1]);
            }

            return ['success' => true, 'message' => 'Question deleted successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update question order in a survey.
     */
    public function survey_question_order_update($arguments)
    {
        $surveyId = $arguments['survey_id'] ?? null;
        $questions = $arguments['questions'] ?? null;
        
        if (!$surveyId || !$questions) {
            return ['success' => false, 'error' => 'Survey ID and questions string are required'];
        }

        // Try to parse questions string as JSON
        if (is_string($questions)) {
            $questions = json_decode($questions, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['success' => false, 'error' => 'Questions must be a valid JSON string'];
            }
        }

        $validator = Validator::make(['questions' => $questions], [
            'questions' => 'required|array',
            'questions.*.id' => 'required|integer|exists:survey_questions,id',
            'questions.*.order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'error' => $validator->errors()->all()];
        }

        try {
            foreach ($questions as $item) {
                $question = SurveyQuestion::find($item['id']);
                if ($question->survey_id == $surveyId) {
                    $question->update(['order' => $item['order']]);
                }
            }

            return ['success' => true, 'message' => 'Question order updated successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * List campaigns for a survey.
     */
    public function survey_campaign_list($arguments)
    {
        $surveyId = $arguments['survey_id'] ?? null;
        
        if (!$surveyId) {
            return ['success' => false, 'error' => 'Survey ID is required'];
        }

        try {
            $campaigns = SurveyCampaign::where('survey_id', $surveyId)
                ->with('creator:id,name')
                ->withCount('surveyContacts')
                ->orderBy('created_at', 'desc')
                ->get();

            return ['success' => true, 'data' => $campaigns];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a new campaign.
     */
    public function survey_campaign_create($arguments)
    {
        $surveyId = $arguments['survey_id'] ?? null;

        if(!$this->user)
        {
            return ['success' => false, 'error' => 'User is required'];
        }
        
        if (!$surveyId) {
            return ['success' => false, 'error' => 'Survey ID is required'];
        }

        $validator = Validator::make($arguments, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:pending,active,completed',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'error' => $validator->errors()->all()];
        }

        try {
            $campaign = SurveyCampaign::create([
                'survey_id' => $surveyId,
                'name' => $arguments['name'],
                'description' => $arguments['description'] ?? null,
                'start_date' => $arguments['start_date'] ?? null,
                'end_date' => $arguments['end_date'] ?? null,
                'status' => $arguments['status'] ?? 'pending',
                'created_by' => $this->user->id,
            ]);

            return ['success' => true, 'data' => $campaign];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get campaign details.
     */
    public function survey_campaign_get($arguments)
    {
        $campaignId = $arguments['campaign_id'] ?? null;
        
        if (!$campaignId) {
            return ['success' => false, 'error' => 'Campaign ID is required'];
        }

        try {
            $campaign = SurveyCampaign::with(['creator:id,name', 'survey:id,title'])
                ->find($campaignId);

            if (!$campaign) {
                return ['success' => false, 'error' => 'Campaign not found'];
            }

            return ['success' => true, 'data' => $campaign];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update a campaign.
     */
    public function survey_campaign_update($arguments)
    {
        $campaignId = $arguments['campaign_id'] ?? null;
        
        if (!$campaignId) {
            return ['success' => false, 'error' => 'Campaign ID is required'];
        }

        $validator = Validator::make($arguments, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:pending,active,completed',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'error' => $validator->errors()->all()];
        }

        try {
            $campaign = SurveyCampaign::find($campaignId);
            
            if (!$campaign) {
                return ['success' => false, 'error' => 'Campaign not found'];
            }

            $campaign->update([
                'name' => $arguments['name'],
                'description' => $arguments['description'] ?? $campaign->description,
                'start_date' => $arguments['start_date'] ?? $campaign->start_date,
                'end_date' => $arguments['end_date'] ?? $campaign->end_date,
                'status' => $arguments['status'] ?? $campaign->status,
            ]);

            return ['success' => true, 'data' => $campaign];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a campaign.
     */
    public function survey_campaign_delete($arguments)
    {
        $campaignId = $arguments['campaign_id'] ?? null;
        
        if (!$campaignId) {
            return ['success' => false, 'error' => 'Campaign ID is required'];
        }

        try {
            $campaign = SurveyCampaign::find($campaignId);
            
            if (!$campaign) {
                return ['success' => false, 'error' => 'Campaign not found'];
            }

            $campaign->delete();
            return ['success' => true, 'message' => 'Campaign deleted successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get campaign contacts.
     */
    public function survey_campaign_contacts_get($arguments)
    {
        $campaignId = $arguments['campaign_id'] ?? null;
        
        if (!$campaignId) {
            return ['success' => false, 'error' => 'Campaign ID is required'];
        }

        try {
            $contacts = SurveyContact::where('survey_campaign_id', $campaignId)
                ->with(['contactGroup.contact:id,email,phone,opt_in_at'])
                ->get();

            return ['success' => true, 'data' => $contacts];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add contacts to a campaign.
     */
    public function survey_campaign_contacts_add($arguments)
    {
        $campaignId = $arguments['campaign_id'] ?? null;
        $contactIds = $arguments['contact_ids'] ?? [];
        $email = $arguments['email'] ?? null;
        $phone = $arguments['phone'] ?? null;
        $name = $arguments['name'] ?? null;
        
        if (!$campaignId) {
            return ['success' => false, 'error' => 'Campaign ID is required'];
        }

        try {
            $campaign = SurveyCampaign::findOrFail($campaignId);
            $user = Auth::user();
            $addedContacts = [];

            // Add by contact IDs
            if (!empty($contactIds)) {
                foreach ($contactIds as $contactId) {
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
                        continue;
                    }
                }
            }

            // Add by email
            if (!empty($email)) {
                try {
                    // Find or create contact
                    $contact = Contact::firstOrCreate(
                        ['email' => $email],
                        [
                            'user_id' => $user->id,
                            'phone' => $phone,
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
                            'name' => $name ?? $contact->email,
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
                    // Continue to next contact
                }
            }

            return ['success' => true, 'data' => $addedContacts];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Remove a contact from a campaign.
     */
    public function survey_campaign_contact_remove($arguments)
    {
        $campaignId = $arguments['campaign_id'] ?? null;
        $contactId = $arguments['contact_id'] ?? null;
        
        if (!$campaignId || !$contactId) {
            return ['success' => false, 'error' => 'Campaign ID and Contact ID are required'];
        }

        try {
            $surveyContact = SurveyContact::where('survey_campaign_id', $campaignId)
                ->where('id', $contactId)
                ->first();

            if (!$surveyContact) {
                return ['success' => false, 'error' => 'Contact not found in this campaign'];
            }

            $surveyContact->delete();
            return ['success' => true, 'message' => 'Contact removed from campaign successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get the survey associated with a campaign.
     */
    public function survey_campaign_survey_get($arguments)
    {
        $campaignId = $arguments['campaign_id'] ?? null;
        
        if (!$campaignId) {
            return ['success' => false, 'error' => 'Campaign ID is required'];
        }

        try {
            $campaign = SurveyCampaign::with('survey.questions')->find($campaignId);
            
            if (!$campaign) {
                return ['success' => false, 'error' => 'Campaign not found'];
            }

            if (!$campaign->survey) {
                return ['success' => false, 'error' => 'No survey associated with this campaign'];
            }

            return ['success' => true, 'data' => $campaign->survey];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Start taking a survey.
     */
    public function survey_start($arguments)
    {



        if($arguments['contact_id'] && $arguments['survey_id']){

            $contact = Contact::find($arguments['contact_id']);
            $survey = Survey::find($arguments['survey_id']);

            $response = SurveyResponse::create([
                'survey_id' => $survey->id,
                'contact_id' => $contact->id,
                'started_at' => now()
            ]);

            if($this->conversation)
            {

                $path_state = $this->conversation->path_state;

                $path_state['response_id'] = $response->id;
                $path_state['response'] = $response;
                $path_state['survey_info'] = $survey;
                $path_state['contact'] = $contact;
                $path_state['style'] = 'casual';


                $this->conversation->path_state = $path_state;

                $this->conversation->save();
            }

            return ['success' => true, 'data' => $response];



        }

        try {


            //$surveyId = $arguments['survey_id'] ?? null;
            $campaignContactId = $arguments['campaign_contact_id'] ?? null;

            $surveyContact = SurveyContact::find($campaignContactId);

            $campaign = SurveyCampaign::find($surveyContact->survey_campaign_id);
            $survey = Survey::find($campaign->survey_id);   


            if($this->conversation)
            {

                $path_state = $this->conversation->path_state;
                
                Log::info("[SurveyCampaignController][startSurvey] **********", ['path_state' => $path_state]);

                if(!isset($path_state['response_id'])){
                    
                        $response = SurveyResponse::create([
                            'survey_id' => $campaign->survey_id,
                            'survey_campaign_id' => $campaign->id,
                            'survey_contact_id' => $surveyContact->id,
                            'contact_id' => $surveyContact->contact_id,               
                            'started_at' => now()
                        ]);
                    
                } else {
                
                        $response = SurveyResponse::find($path_state['response_id']);

                }

            } else {



                $path_state = [
                    'survey_id' => $campaign->survey_id,
                    'campaign_id' => $campaign->id,
                    'response_id' => $response->id,
                    'contact' => $surveyContact->contact,
                    'style' => $request->style ?? 'casual',
                ];



                $response = SurveyResponse::create([
                    'survey_id' => $campaign->survey_id,
                    'survey_campaign_id' => $campaign->id,
                    'survey_contact_id' => $surveyContact->id,
                    'contact_id' => $surveyContact->contact_id,               
                    'started_at' => now()
                ]);
            }   





            
                if (!$campaignContactId) {
                    return ['success' => false, 'error' => 'Campaign Contact ID is required'];
                }

        
            


                Log::info("[SurveyExecutor][survey_start] **********", ['surveyContact' => $surveyContact]);

                if (!$surveyContact) {
                    return ['success' => false, 'error' => 'Survey contact not found'];
                }

                Log::info("[SurveyExecutor][survey_start] **********", ['path_state survey_id' => $path_state['survey_id']]);
                Log::info("[SurveyExecutor][survey_start] **********", ['path_state campaign_id' => $path_state['campaign_id']]);
                Log::info("[SurveyExecutor][survey_start] **********", ['path_state response_id' => $path_state['response_id']]);

                // Get the survey
                $survey = Survey::find($path_state['survey_id']);
                Log::info("[SurveyExecutor][survey_start] **********", ['survey' => $survey]);
                if (!$survey) {
                    return ['success' => false, 'error' => 'Survey not found'];
                }

                $campaign = SurveyCampaign::find($path_state['campaign_id']);
                Log::info("[SurveyExecutor][survey_start] **********", ['campaign' => $campaign]);
                // If no campaign ID provided, create a new campaign
                if (!$campaign) {
                    $campaign = SurveyCampaign::create([
                        'survey_id' => $survey->id,
                        'name' => 'Direct Survey - ' . $survey->title,
                        'description' => 'Campaign created automatically for direct survey access',
                        'status' => 'active',
                        'created_by' => $this->user->id,
                    ]);
                    
                }

                $campaignId = $campaign->id;
                Log::info("[SurveyExecutor][survey_start] **********", ['campaignId' => $campaignId]);

                // Create a new survey response
                $surveyResponse = SurveyResponse::create([
                    'survey_id' => $survey->id,
                    'survey_campaign_id' => $campaign->id,
                    'survey_contact_id' => $surveyContact->id,
                    'contact_id' => $surveyContact->contact_id,                    
                    'status' => 'in_progress',
                    'started_at' => now(),
                ]);

                // Update contact status to in_progress
                $surveyContact->update(['status' => 'in_progress']);

                return [
                    'success' => true, 
                    'message' => 'Survey started successfully',
                    'data' => [
                        'response_id' => $surveyResponse->id,
                        'campaign_id' => $campaignId
                    ]
                ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get the next question in the survey.
     */
    public function survey_question_next($arguments)
    {
        $campaignId = $arguments['campaign_id'] ?? null;
        $contactId = $arguments['contact_id'] ?? null;
        
        if (!$campaignId || !$contactId) {
            return ['success' => false, 'error' => 'Campaign ID and Contact ID are required'];
        }

        try {
            $campaign = SurveyCampaign::with('survey.questions')->find($campaignId);
            
            if (!$campaign) {
                return ['success' => false, 'error' => 'Campaign not found'];
            }

            // Get all answered questions for this contact
            $answeredQuestionIds = SurveyAnswer::where('survey_campaign_id', $campaignId)
                ->where('contact_id', $contactId)
                ->pluck('question_id')
                ->toArray();

            // Find the first unanswered question
            $nextQuestion = $campaign->survey->questions
                ->whereNotIn('id', $answeredQuestionIds)
                ->sortBy('order')
                ->first();

            if (!$nextQuestion) {
                return ['success' => false, 'error' => 'No more questions available'];
            }

            return ['success' => true, 'data' => $nextQuestion];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Submit an answer to a survey question.
     */
    public function survey_answer_submit($arguments)
    {
        $campaignId = $arguments['campaign_id'] ?? null;
        $contactId = $arguments['contact_id'] ?? null;
        $questionId = $arguments['question_id'] ?? null;
        $answer = $arguments['answer'] ?? null;
        
        if (!$campaignId || !$contactId || !$questionId || $answer === null) {
            return ['success' => false, 'error' => 'Campaign ID, Contact ID, Question ID, and Answer are required'];
        }

        try {
            // Verify the question exists and belongs to the campaign's survey
            $campaign = SurveyCampaign::with('survey.questions')->find($campaignId);
            if (!$campaign) {
                return ['success' => false, 'error' => 'Campaign not found'];
            }

            $question = $campaign->survey->questions->find($questionId);
            if (!$question) {
                return ['success' => false, 'error' => 'Question not found in this survey'];
            }

            // Get the survey contact
            $surveyContact = SurveyContact::where('survey_campaign_id', $campaignId)
                ->where('contact_id', $contactId)
                ->first();

            if (!$surveyContact) {
                return ['success' => false, 'error' => 'Contact not found in this campaign'];
            }

            // Get the active survey response
            $surveyResponse = SurveyResponse::where('survey_campaign_id', $campaignId)
                ->where('contact_id', $contactId)
                ->where('completed_at', null)
                ->latest()
                ->first();

            if (!$surveyResponse) {
                return ['success' => false, 'error' => 'No active survey response found'];
            }

            // Process answer if it's a string that might be JSON or CSV
            $answerData = null;
            $answerText = null;

            if (is_string($answer)) {
                // Try to decode as JSON first
                $jsonAnswer = json_decode($answer, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $answerData = $jsonAnswer;
                } else {
                    // If not JSON and contains commas, treat as CSV
                    if (strpos($answer, ',') !== false) {
                        $answerData = array_map('trim', explode(',', $answer));
                    } else {
                        // Single string answer
                        $answerText = $answer;
                    }
                }
            }


            
            // Create or update the answer
            SurveyAnswer::updateOrCreate(
                [
                    'survey_response_id' => $surveyResponse->id,
                    'question_id' => $questionId
                ],
                [
                    'survey_campaign_id' => $campaignId,
                    'survey_contact_id' => $surveyContact->id,
                    
                    'contact_id' => $contactId,
                    'survey_question_id' => $questionId,
                    'answer_text' => $answerText,
                    'answer_data' => $answerData
                ]
            );

            return ['success' => true, 'message' => 'Answer submitted successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get the progress of a survey for a contact.
     */
    public function survey_progress_get($arguments)
    {
        $campaignId = $arguments['campaign_id'] ?? null;
        $contactId = $arguments['contact_id'] ?? null;
        
        if (!$campaignId || !$contactId) {
            return ['success' => false, 'error' => 'Campaign ID and Contact ID are required'];
        }

        try {
            $campaign = SurveyCampaign::with('survey.questions')->find($campaignId);
            
            if (!$campaign) {
                return ['success' => false, 'error' => 'Campaign not found'];
            }

            $totalQuestions = $campaign->survey->questions->count();
            $answeredQuestions = SurveyAnswer::where('survey_campaign_id', $campaignId)
                ->where('contact_id', $contactId)
                ->count();

            $progress = $totalQuestions > 0 ? ($answeredQuestions / $totalQuestions) * 100 : 0;

            return [
                'success' => true,
                'data' => [
                    'total_questions' => $totalQuestions,
                    'answered_questions' => $answeredQuestions,
                    'progress_percentage' => $progress
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Mark a survey as completed for a contact.
     */
    public function survey_complete($arguments)
    {
        $campaignId = $arguments['campaign_id'] ?? null;
        $contactId = $arguments['contact_id'] ?? null;
        
        if (!$campaignId || !$contactId) {
            return ['success' => false, 'error' => 'Campaign ID and Contact ID are required'];
        }

        try {
            $surveyContact = SurveyContact::where('survey_campaign_id', $campaignId)
                ->where('contact_id', $contactId)
                ->first();

            if (!$surveyContact) {
                return ['success' => false, 'error' => 'Contact not found in this campaign'];
            }

            // Get the active survey response
            $surveyResponse = SurveyResponse::where('survey_campaign_id', $campaignId)
                ->where('survey_contact_id', $surveyContact->id)
                ->where('completed_at', null)
                ->latest()
                ->first();

            if (!$surveyResponse) {
                return ['success' => false, 'error' => 'No active survey response found'];
            }

            // Update survey response status to completed
            $surveyResponse->update([
                //'status' => 'completed',
                'completed_at' => now()
            ]);

            // Update contact status to completed
            $surveyContact->update(['status' => 'completed']);

            return ['success' => true, 'message' => 'Survey completed successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get the results of a survey for a campaign.
     */
    public function survey_results_get($arguments)
    {
        $campaignId = $arguments['campaign_id'] ?? null;
        
        if (!$campaignId) {
            return ['success' => false, 'error' => 'Campaign ID is required'];
        }

        try {
            $campaign = SurveyCampaign::with('survey.questions')->find($campaignId);
            
            if (!$campaign) {
                return ['success' => false, 'error' => 'Campaign not found'];
            }

            // Get all answers for this campaign
            $answers = SurveyAnswer::where('survey_campaign_id', $campaignId)
                ->with('contact:id,name,email')
                ->get()
                ->groupBy('question_id');

            $results = [];
            foreach ($campaign->survey->questions as $question) {
                $questionAnswers = $answers->get($question->id, collect());
                
                $results[] = [
                    'question_id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'total_responses' => $questionAnswers->count(),
                    'answers' => $questionAnswers->map(function($answer) {
                        return [
                            'contact' => $answer->contact,
                            'answer_text' => $answer->answer_text,
                            'answer_data' => $answer->answer_data,
                            'created_at' => $answer->created_at
                        ];
                    })
                ];
            }

            return ['success' => true, 'data' => $results];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
} 