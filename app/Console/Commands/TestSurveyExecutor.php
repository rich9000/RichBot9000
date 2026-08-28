<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Executors\SurveyExecutor;
use App\Models\User;
use App\Models\Role;
use App\Models\Contact;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyCampaign;
use App\Models\SurveyContact;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\SurveyResponse;
use App\Models\SurveyAnswer;

class TestSurveyExecutor extends Command
{
    protected $signature = 'test:survey';
    protected $description = 'Test the Survey Executor functionality';

    private $user;
    private $executor;
    private $contacts = [];
    private $survey;
    private $campaign;
    private $response;

    public function handle()
    {
        $this->info('Starting Survey Executor Test...');

        // Create test user and contacts
        $this->setupTestData();

        
        // Initialize executor with the test user
        $this->executor = new SurveyExecutor($this->user);

        // Run tests
        $this->testSurveyList();
        $this->testSurveyCreate();
        $this->testSurveyGet();
        $this->testSurveyUpdate();
        $this->testQuestionCreate();
        $this->testQuestionUpdate();
        $this->testQuestionOrderUpdate();
        $this->testCampaignCreate();
        $this->testCampaignContactsAdd();
        $this->testCampaignGet();
        $this->testCampaignUpdate();
        $this->testSurveyStart();
        $this->testQuestionNext();
        $this->testAnswerSubmit();
        $this->testProgressGet();
        $this->testSurveyComplete();
        $this->testResultsGet();

        $this->info('Survey Executor Test completed.');
    }

    private function setupTestData()
    {
        $this->info('Setting up test data...');

        // Create roles if they don't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $surveyRole = Role::firstOrCreate(['name' => 'surveys_user']);

        // Create test user
        $this->user = User::firstOrCreate(
            ['email' => 'rich@richbot9000.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign roles
        $this->user->roles()->sync([$adminRole->id, $surveyRole->id]);

        // Create contacts
        $this->contacts[] = Contact::firstOrCreate(
            ['email' => 'richcarroll@gmail.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Rich Carroll',
                'phone' => '7855480022',
                'opt_in_at' => now(),
            ]
        );

        $this->contacts[] = Contact::firstOrCreate(
            ['email' => 'rich@rainbowtel.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Rich Rainbow',
                'phone' => '7852881144',
                'opt_in_at' => now(),
            ]
        );

        $this->info('Test data setup complete.');
    }

    private function testSurveyList()
    {
        $this->info("\nTesting survey_list...");
        $result = $this->executor->survey_list(['status' => 'all']);
        
        if ($result['success']) {
            $this->info('✓ Survey list successful');
            $surveys = $result['data'];
            $this->info('Found ' . count($surveys) . ' surveys');
            
            //dump($surveys);

            if (count($surveys) > 0) {
                $this->table(
                    ['ID', 'Title', 'Status', 'Created'],
                    array_map(function($survey) {

                       // dump($survey);

                        return [
                            $survey['id'],
                            $survey['title'],
                            $survey['status'],
                            $survey['created_at']
                        ];
                    }, $surveys->toArray())
                );
            }
        } else {
            $this->error('✗ Survey list failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    private function testSurveyCreate()
    {
        $this->info("\nTesting survey_create...");
        $result = $this->executor->survey_create([
            'title' => 'Test Survey ' . date('Y-m-d H:i:s'),
            'description' => 'This is a test survey created by the test command',
            'status' => 'draft'
        ]);

        if ($result['success']) {
            $this->info('✓ Survey create successful');
            $this->survey = $result['data'];
            $this->info('Created survey ID: ' . $this->survey->id);
        } else {
            $this->error('✗ Survey create failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    private function testSurveyGet()
    {
        $this->info("\nTesting survey_get...");
        $result = $this->executor->survey_get([
            'survey_id' => $this->survey->id
        ]);

        if ($result['success']) {
            $this->info('✓ Survey get successful');
            $survey = $result['data'];
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $survey->id],
                    ['Title', $survey->title],
                    ['Description', $survey->description],
                    ['Status', $survey->status],
                    ['Created By', $survey->creator->name ?? 'N/A']
                ]
            );
        } else {
            $this->error('✗ Survey get failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    private function testSurveyUpdate()
    {
        $this->info("\nTesting survey_update...");
        $result = $this->executor->survey_update([
            'survey_id' => $this->survey->id,
            'title' => 'Updated Test Survey ' . date('Y-m-d H:i:s'),
            'description' => 'This survey has been updated by the test command',
            'status' => 'active'
        ]);

        if ($result['success']) {
            $this->info('✓ Survey update successful');
            $survey = $result['data'];
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $survey->id],
                    ['Title', $survey->title],
                    ['Description', $survey->description],
                    ['Status', $survey->status]
                ]
            );
        } else {
            $this->error('✗ Survey update failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    private function testQuestionCreate()
    {
        $this->info("\nTesting survey_question_create...");
        $result = $this->executor->survey_question_create([
            'survey_id' => $this->survey->id,
            'question_text' => 'What is your favorite color?',
            'question_type' => 'single_choice',
            'options' => json_encode(['Red', 'Blue', 'Green', 'Yellow']),
            'required' => true,
            'order' => 1
        ]);

        if ($result['success']) {
            $this->info('✓ Question create successful');
            $question = $result['data'];
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $question->id],
                    ['Text', $question->question_text],
                    ['Type', $question->question_type],
                    ['Required', $question->required ? 'Yes' : 'No'],
                    ['Order', $question->order]
                ]
            );
        } else {
            $this->error('✗ Question create failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    private function testQuestionUpdate()
    {
        $this->info("\nTesting survey_question_update...");
        $question = $this->survey->questions()->first();
        
        $result = $this->executor->survey_question_update([
            'question_id' => $question->id,
            'question_text' => 'Updated: What is your favorite color?',
            'question_type' => 'single_choice',
            'options' => json_encode(['Red', 'Blue', 'Green', 'Yellow', 'Purple']),
            'required' => true,
            'order' => 1
        ]);

        if ($result['success']) {
            $this->info('✓ Question update successful');
            $question = $result['data'];
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $question->id],
                    ['Text', $question->question_text],
                    ['Type', $question->question_type],
                    ['Required', $question->required ? 'Yes' : 'No'],
                    ['Order', $question->order]
                ]
            );
        } else {
            $this->error('✗ Question update failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    private function testQuestionOrderUpdate()
    {
        $this->info("\nTesting survey_question_order_update...");
        
        // Create a second question
        $this->executor->survey_question_create([
            'survey_id' => $this->survey->id,
            'question_text' => 'What is your favorite number?',
            'question_type' => 'text',
            'required' => true,
            'order' => 2
        ]);

        $questions = $this->survey->questions()->get();
        $orderData = $questions->map(function($q) {
            return [
                'id' => $q->id,
                'order' => $q->order === 1 ? 2 : 1
            ];
        })->toArray();

        $result = $this->executor->survey_question_order_update([
            'survey_id' => $this->survey->id,
            'questions' => json_encode($orderData)
        ]);

        if ($result['success']) {
            $this->info('✓ Question order update successful');
            $this->info('Questions reordered successfully');
        } else {
            $this->error('✗ Question order update failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    private function testCampaignCreate()
    {
        $this->info("\nTesting survey_campaign_create...");
        $result = $this->executor->survey_campaign_create([
            'survey_id' => $this->survey->id,
            'name' => 'Test Campaign ' . date('Y-m-d H:i:s'),
            'description' => 'This is a test campaign created by the test command',
            'start_date' => now(),
            'end_date' => now()->addDays(7),
            'status' => 'active'
        ]);

        if ($result['success']) {
            $this->info('✓ Campaign create successful');
            $this->campaign = $result['data'];
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $this->campaign->id],
                    ['Name', $this->campaign->name],
                    ['Description', $this->campaign->description],
                    ['Status', $this->campaign->status],
                    ['Start Date', $this->campaign->start_date],
                    ['End Date', $this->campaign->end_date]
                ]
            );
        } else {
            $this->error('✗ Campaign create failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    private function testCampaignContactsAdd()
    {
        $this->info("\nTesting survey_campaign_contacts_add...");
        $result = $this->executor->survey_campaign_contacts_add([
            'campaign_id' => $this->campaign->id,
            'contact_ids' => json_encode([$this->contacts[0]->id])
        ]);

        if ($result['success']) {
            $this->info('✓ Campaign contacts add successful');
            $this->info('Added ' . count($result['data']) . ' contacts to campaign');
        } else {
            $this->error('✗ Campaign contacts add failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    private function testCampaignGet()
    {
        $this->info("\nTesting survey_campaign_get...");
        $result = $this->executor->survey_campaign_get([
            'campaign_id' => $this->campaign->id
        ]);

        if ($result['success']) {
            $this->info('✓ Campaign get successful');
            $campaign = $result['data'];
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $campaign->id],
                    ['Name', $campaign->name],
                    ['Description', $campaign->description],
                    ['Status', $campaign->status],
                    ['Start Date', $campaign->start_date],
                    ['End Date', $campaign->end_date],
                    ['Created By', $campaign->creator->name ?? 'N/A']
                ]
            );
        } else {
            $this->error('✗ Campaign get failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    private function testCampaignUpdate()
    {
        $this->info("\nTesting survey_campaign_update...");
        $result = $this->executor->survey_campaign_update([
            'campaign_id' => $this->campaign->id,
            'name' => 'Updated Test Campaign ' . date('Y-m-d H:i:s'),
            'description' => 'This campaign has been updated by the test command',
            'status' => 'active'
        ]);

        if ($result['success']) {
            $this->info('✓ Campaign update successful');
            $campaign = $result['data'];
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $campaign->id],
                    ['Name', $campaign->name],
                    ['Description', $campaign->description],
                    ['Status', $campaign->status]
                ]
            );
        } else {
            $this->error('✗ Campaign update failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    private function testSurveyStart()
    {
        $this->info("\nTesting survey_start...");
        
        // Test 1: Start survey with existing campaign
        $this->info("\nTest 1: Starting survey with existing campaign...");
        $result = $this->executor->survey_start([
            'campaign_id' => $this->campaign->id,
            'contact_id' => $this->contacts[0]->id
        ]);

        if ($result['success']) {
            $this->response = SurveyResponse::find($result['data']['response_id']);
            $this->info('✓ Survey start with existing campaign successful');
            $this->info('Response ID: ' . ($result['data']['response_id'] ?? 'N/A'));
        } else {
            $this->error('✗ Survey start with existing campaign failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        // Test 2: Start survey without campaign (should create one)
        $this->info("\nTest 2: Starting survey without campaign...");
        $result = $this->executor->survey_start([
            'survey_id' => $this->survey->id,
            'contact_id' => $this->contacts[1]->id
        ]);

        if ($result['success']) {
            $this->info('✓ Survey start without campaign successful');
            $this->info('Response ID: ' . ($result['data']['response_id'] ?? 'N/A'));
            $this->info('Campaign ID: ' . ($result['data']['campaign_id'] ?? 'N/A'));
            
            // Verify campaign was created
            $campaign = SurveyCampaign::find($result['data']['campaign_id']);
            if ($campaign) {
                $this->info('Campaign created successfully:');
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['ID', $campaign->id],
                        ['Name', $campaign->name],
                        ['Survey ID', $campaign->survey_id],
                        ['Status', $campaign->status]
                    ]
                );
            }
        } else {
            $this->error('✗ Survey start without campaign failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    private function testQuestionNext()
    {
        $this->info("\nTesting survey_question_next...");
        $result = $this->executor->survey_question_next([
            'campaign_id' => $this->campaign->id,
            'contact_id' => $this->contacts[0]->id
        ]);

        if ($result['success']) {
            $this->info('✓ Question next successful');
            $question = $result['data'];
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $question->id],
                    ['Text', $question->question_text],
                    ['Type', $question->question_type],
                    ['Required', $question->required ? 'Yes' : 'No'],
                    ['Order', $question->order]
                ]
            );
        } else {
            $this->error('✗ Question next failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    private function testAnswerSubmit()
    {
        $this->info("\nTesting survey_answer_submit...");
        $question = $this->survey->questions()->first();
        
        $result = $this->executor->survey_answer_submit([
         
        ]);

        if ($result['success']) {
            $this->info('✓ Answer submit successful');
            
            // Verify the answer was saved
            $surveyContact = SurveyContact::where('survey_campaign_id', $this->campaign->id)
                ->where('contact_id', $this->contacts[0]->id)
                ->first();
                
            if ($surveyContact) {
                $surveyResponse = SurveyResponse::where('survey_contact_id', $surveyContact->id)
                    ->where('completed_at', null)
                    ->latest()
                    ->first();
                    
                if ($surveyResponse) {
                    $answer = SurveyAnswer::where('survey_response_id', $surveyResponse->id)
                        ->where('question_id', $question->id)
                        ->first();
                        
                    if ($answer) {
                        $this->info('Answer verified: ' . ($answer->answer_text ?? json_encode($answer->answer_data)));
                    }
                }
            }
        } else {
            $this->error('✗ Answer submit failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    private function testProgressGet()
    {
        $this->info("\nTesting survey_progress_get...");
        $result = $this->executor->survey_progress_get([
            'campaign_id' => $this->campaign->id,
            'contact_id' => $this->contacts[0]->id
        ]);

        if ($result['success']) {
            $this->info('✓ Progress get successful');
            $progress = $result['data'];
            $this->table(
                ['Field', 'Value'],
                [
                    ['Total Questions', $progress['total_questions']],
                    ['Answered Questions', $progress['answered_questions']],
                    ['Progress', $progress['progress_percentage'] . '%']
                ]
            );
        } else {
            $this->error('✗ Progress get failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    private function testSurveyComplete()
    {
        $this->info("\nTesting survey_complete...");
        $result = $this->executor->survey_complete([
            'campaign_id' => $this->campaign->id,
            'contact_id' => $this->contacts[0]->id
        ]);

        if ($result['success']) {
            $this->info('✓ Survey complete successful');
            
            // Verify completion
            $surveyContact = SurveyContact::where('survey_campaign_id', $this->campaign->id)
                ->where('contact_id', $this->contacts[0]->id)
                ->first();
                
            if ($surveyContact) {
                $surveyResponse = SurveyResponse::where('survey_contact_id', $surveyContact->id)
                    ->where('completed_at', '!=', null)
                    ->latest()
                    ->first();
                    
                if ($surveyResponse) {
                    $this->info('Survey response completed at: ' . $surveyResponse->completed_at);
                }
            }
        } else {
            $this->error('✗ Survey complete failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    private function testResultsGet()
    {
        $this->info("\nTesting survey_results_get...");
        $result = $this->executor->survey_results_get([
            'campaign_id' => $this->campaign->id
        ]);

        if ($result['success']) {
            $this->info('✓ Results get successful');
            $results = $result['data'];
            
            foreach ($results as $questionResult) {
                $this->info("\nQuestion: " . $questionResult['question_text']);
                $this->info("Type: " . $questionResult['question_type']);
                $this->info("Total Responses: " . $questionResult['total_responses']);
                
                if (count($questionResult['answers']) > 0) {
                    $this->table(
                        ['Contact', 'Answer', 'Time'],
                        array_map(function($answer) {
                            return [
                                $answer['contact']['name'],
                                is_array($answer['answer_data']) ? implode(', ', $answer['answer_data']) : $answer['answer_text'],
                                $answer['created_at']
                            ];
                        }, $questionResult['answers']->toArray())
                    );
                }
            }
        } else {
            $this->error('✗ Results get failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }
} 