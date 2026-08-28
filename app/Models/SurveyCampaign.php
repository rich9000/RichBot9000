<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyCampaign extends Model
{
    protected $fillable = [
        'survey_id',
        'created_by',
        'name',
        'description',
        'start_date',
        'end_date',
        'status'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    /**
     * Get the survey that owns the campaign.
     */
    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    /**
     * Get the user who created the campaign.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the survey contacts for the campaign.
     */
    public function surveyContacts()
    {
        return $this->hasMany(SurveyContact::class);
    }

    /**
     * Get the responses for the campaign.
     */
    public function responses()
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /**
     * Get the contacts for the campaign through contact groups.
     */
    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_groups')
            ->where('groupable_type', SurveyCampaign::class)
            ->where('groupable_id', $this->id)
            ->withPivot(['name', 'type', 'allowed_to_contact'])
            ->withTimestamps();
    }
} 