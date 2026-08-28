<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyContact extends Model
{
    protected $fillable = [
        'survey_campaign_id',
        'contact_group_id',
        'status',
        'sent_at',
        'completed_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Get the campaign that owns the survey contact.
     */
    public function campaign()
    {
        return $this->belongsTo(SurveyCampaign::class, 'survey_campaign_id');
    }

    /**
     * Get the contact group that owns the survey contact.
     */
    public function contactGroup()
    {
        return $this->belongsTo(ContactGroup::class);
    }

    /**
     * Get the contact that owns the survey contact.
     */
    public function contact()
    {
        return $this->hasOneThrough(
            Contact::class,
            ContactGroup::class,
            'id',
            'id',
            'contact_group_id',
            'contact_id'
        );
    }

    /**
     * Get the response for the survey contact.
     */
    public function response()
    {
        return $this->hasOne(SurveyResponse::class);
    }
} 