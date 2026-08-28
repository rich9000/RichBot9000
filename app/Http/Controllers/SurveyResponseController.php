<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyCampaign;
use App\Models\SurveyContact;
use App\Models\SurveyResponse;
use App\Models\SurveyAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SurveyResponseController extends Controller
{
    /**
     * Store a newly created response in storage.
     */
    public function store(Request $request, SurveyContact $surveyContact)
    {
        $validator = Validator::make($request->all(), [
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:survey_questions,id',
            'answers.*.answer_text' => 'nullable|string',
            'answers.*.answer_data' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Begin transaction to ensure data consistency
        DB::beginTransaction();
        
        try {
            // Create the response
            $response = SurveyResponse::create([
                'survey_id' => $surveyContact->campaign->survey_id,
                'survey_campaign_id' => $surveyContact->survey_campaign_id,
                'survey_contact_id' => $surveyContact->id,
                'started_at' => $request->started_at ?? now(),
                'completed_at' => now(),
            ]);

            // Create answers for each question
            foreach ($request->answers as $answerData) {
                SurveyAnswer::create([
                    'survey_response_id' => $response->id,
                    'survey_question_id' => $answerData['question_id'],
                    'answer_text' => $answerData['answer_text'] ?? null,
                    'answer_data' => $answerData['answer_data'] ?? null,
                ]);
            }

            // Update the survey contact status
            $surveyContact->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Survey response submitted successfully',
                'response' => $response
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to submit survey response', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(SurveyResponse $response){

        $response->load(['surveyContact.contact:id,name,email,phone','answers.question']);

        return response()->json($response);


    }

    /**
     * Get responses for a campaign.
     */
    public function getResponses(SurveyCampaign $campaign)
    {
        $this->authorize('view', $campaign->survey);
        
        $responses = $campaign->responses()
            ->with(['surveyContact.contact:id,name,email,phone', 'answers'])
            ->get();
        
        return response()->json($responses);
    }

    /**
     * Get details of a response.
     */
    public function getResponseDetails(SurveyResponse $response)
    {
        $this->authorize('view', $response->survey);
        
        $response->load([
            'survey:id,title',
            'campaign:id,name',
            'surveyContact.contact:id,name,email,phone',
            'answers.question'
        ]);
        
        return response()->json($response);
    }
} 