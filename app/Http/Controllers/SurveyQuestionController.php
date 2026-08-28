<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SurveyQuestionController extends Controller
{
    /**
     * Store a newly created question in storage.
     */
    public function store(Request $request, Survey $survey)
    {
        //$this->authorize('update', $survey);

        $validator = Validator::make($request->all(), [
            'question_text' => 'required|string',
            'question_type' => 'required|string',
            'options' => 'nullable|array',
            'required' => 'boolean',
            'order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Get the highest current order value
        $maxOrder = $survey->questions()->max('order') ?? 0;

        $question = $survey->questions()->create([
            'question_text' => $request->question_text,
            'question_type' => $request->question_type,
            'options' => $request->options,
            'required' => $request->required ?? true,
            'order' => $request->order ?? ($maxOrder + 1),
        ]);

        return response()->json($question, 201);
    }

    /**
     * Update the specified question in storage.
     */
    public function update(Request $request, SurveyQuestion $question)
    {
        //$this->authorize('update', $question->survey);

        $validator = Validator::make($request->all(), [
            'question_text' => 'required|string',
            'question_type' => 'required|string',
            'options' => 'nullable|array',
            'required' => 'boolean',
            'order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $question->update([
            'question_text' => $request->question_text,
            'question_type' => $request->question_type,
            'options' => $request->options,
            'required' => $request->required ?? $question->required,
            'order' => $request->order ?? $question->order,
        ]);

        return response()->json($question);
    }

    /**
     * Remove the specified question from storage.
     */
    public function destroy(SurveyQuestion $question)
    {
        //$this->authorize('update', $question->survey);
        
        $question->delete();

        // Reorder remaining questions
        $survey = $question->survey;
        $questions = $survey->questions()->orderBy('order')->get();
        
        foreach ($questions as $index => $q) {
            $q->update(['order' => $index + 1]);
        }

        return response()->json(null, 204);
    }

    /**
     * Update the order of questions.
     */
    public function updateOrder(Request $request, Survey $survey)
    {
        //$this->authorize('update', $survey);

        $validator = Validator::make($request->all(), [
            'questions' => 'required|array',
            'questions.*.id' => 'required|integer|exists:survey_questions,id',
            'questions.*.order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->questions as $item) {
            $question = SurveyQuestion::find($item['id']);
            
            // Verify question belongs to this survey
            if ($question->survey_id === $survey->id) {
                $question->update(['order' => $item['order']]);
            }
        }

        return response()->json(['message' => 'Question order updated successfully']);
    }
} 