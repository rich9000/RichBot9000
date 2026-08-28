<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyQuestion extends Model
{
    protected $fillable = [
        'survey_id',
        'question_text',
        'question_type',
        'options',
        'required',
        'order'
    ];

    protected $casts = [
        'options' => 'array',
        'required' => 'boolean',
        'order' => 'integer'
    ];

    /**
     * Get the survey that owns the question.
     */
    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    /**
     * Get the answers for the question.
     */
    public function answers()
    {
        return $this->hasMany(SurveyAnswer::class);
    }
} 