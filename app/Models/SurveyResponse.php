<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    protected $fillable = [
        'survey_id',
        'survey_campaign_id',
        'survey_contact_id',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Get the survey that owns the response.
     */
    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    /**
     * Get the campaign that owns the response.
     */
    public function campaign()
    {
        return $this->belongsTo(SurveyCampaign::class, 'survey_campaign_id');
    }

    /**
     * Get the survey contact that owns the response.
     */
    public function surveyContact()
    {
        return $this->belongsTo(SurveyContact::class);
    }

    /**
     * Get the contact that owns the response.
     */
    public function contact()
    {
        return $this->hasOneThrough(
            Contact::class,
            SurveyContact::class,
            'id', // Foreign key on survey_contacts table
            'id', // Foreign key on contacts table
            'survey_contact_id', // Local key on survey_responses table
            'contact_group_id' // Local key on survey_contacts table
        )->through('contactGroup');
    }

    /**
     * Get the answers for the response.
     */
    public function answers()
    {
        return $this->hasMany(SurveyAnswer::class);
    }
} 