<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SurveyController extends Controller
{
    /**
     * Display a listing of the surveys.
     */
    public function index()
    {
        $surveys = Survey::with(['creator:id,name', 'questions'])
          //  ->where('created_by', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($surveys);
    }

    /**
     * Store a newly created survey in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:draft,active,archived',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $survey = Survey::create([
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status ?? 'draft',
            'created_by' => Auth::id(),
        ]);

        return response()->json($survey, 201);
    }

    /**
     * Display the specified survey.
     */
    public function show(Survey $survey)
    {
       // $this->authorize('view', $survey);
        
        $survey->load(['creator:id,name', 'questions' => function($query) {
            $query->orderBy('order');
        }]);
        
        return response()->json($survey);
    }

    /**
     * Update the specified survey in storage.
     */
    public function update(Request $request, Survey $survey)
    {
       // $this->authorize('update', $survey);
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:draft,active,archived',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $survey->update([
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status ?? $survey->status,
        ]);

        return response()->json($survey);
    }

    /**
     * Remove the specified survey from storage.
     */
    public function destroy(Survey $survey)
    {
       // $this->authorize('delete', $survey);
        
        // Delete related questions, campaigns, etc. will be handled by cascade
        $survey->delete();

        return response()->json(null, 204);
    }

    /**
     * Bulk delete multiple surveys.
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'survey_ids' => 'required|array',
            'survey_ids.*' => 'exists:surveys,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $surveyIds = $request->survey_ids;
        
        // Start a database transaction
        DB::beginTransaction();
        
        try {
            // Get all campaigns for these surveys
            $campaigns = \App\Models\SurveyCampaign::whereIn('survey_id', $surveyIds)->get();
            $campaignIds = $campaigns->pluck('id')->toArray();
            
            // Delete survey answers first
            \App\Models\SurveyAnswer::whereIn('survey_campaign_id', $campaignIds)->delete();
            
            // Delete survey responses
            \App\Models\SurveyResponse::whereIn('survey_campaign_id', $campaignIds)->delete();
            
            // Delete survey contacts
            \App\Models\SurveyContact::whereIn('survey_campaign_id', $campaignIds)->delete();
            
            // Delete campaigns
            \App\Models\SurveyCampaign::whereIn('survey_id', $surveyIds)->delete();
            
            // Delete survey questions
            \App\Models\SurveyQuestion::whereIn('survey_id', $surveyIds)->delete();
            
            // Finally delete the surveys
            Survey::whereIn('id', $surveyIds)->delete();
            
            // Commit the transaction
            DB::commit();
            
            return response()->json(['message' => count($surveyIds) . ' surveys deleted successfully']);
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete surveys: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get all questions for a survey.
     */
    public function getQuestions(Survey $survey)
    {
      //  $this->authorize('view', $survey);
        
        $questions = $survey->questions()->orderBy('order')->get();
        
        return response()->json($questions);
    }
} 