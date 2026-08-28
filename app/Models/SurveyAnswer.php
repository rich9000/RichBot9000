<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyAnswer extends Model
{
    protected $fillable = [
        'survey_question_id',
        'question_id',       
        'survey_campaign_id',
        'survey_response_id',
        'answer_text',
        'answer_data'
    ];

    protected $casts = [
        'answer_data' => 'array'
    ];

    /**
     * Get the response that owns the answer.
     */
    public function response()
    {
        return $this->belongsTo(SurveyResponse::class, 'survey_response_id');
    }

    /**
     * Get the contact that owns the answer through the response.
     */
    public function contact()
    {
        return $this->hasOneThrough(
            Contact::class,
            SurveyResponse::class,
            'id', // Foreign key on survey_responses table
            'id', // Foreign key on contacts table
            'survey_response_id', // Local key on survey_answers table
            'survey_contact_id' // Local key on survey_responses table
        )->through('surveyContact.contactGroup');
    }

    /**
     * Get the question that owns the answer.
     */
    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }
} 