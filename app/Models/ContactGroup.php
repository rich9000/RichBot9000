<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactGroup extends Model
{
    protected $fillable = [
        'contact_id',
        'name',
        'type',
        'allowed_to_contact',
        'groupable_type',
        'groupable_id'
    ];

    protected $casts = [
        'allowed_to_contact' => 'boolean'
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function groupable()
    {
        return $this->morphTo();
    }

    public function surveyContacts()
    {
        return $this->hasMany(SurveyContact::class);
    }
} 