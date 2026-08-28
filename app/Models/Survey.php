<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    protected $fillable = [
        'created_by',
        'title',
        'description',
        'status'
    ];

    /**
     * Get the user who created the survey.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the questions for the survey.
     */
    public function questions()
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('order');
    }

    /**
     * Get the campaigns for the survey.
     */
    public function campaigns()
    {
        return $this->hasMany(SurveyCampaign::class);
    }

    /**
     * Get the responses for the survey.
     */
    public function responses()
    {
        return $this->hasMany(SurveyResponse::class);
    }
} 