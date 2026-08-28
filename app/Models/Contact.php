<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'phone',
        'type',
        'opt_in_at'
    ];

    protected $casts = [
        'opt_in_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($contact) {
            if (empty($contact->email) && empty($contact->phone)) {
                throw new \Exception('Either email or phone must be provided.');
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contactGroups()
    {
        return $this->hasMany(ContactGroup::class);
    }

    public function userGroups()
    {
        return $this->contactGroups()->where('groupable_type', User::class);
    }

    public function surveyGroups()
    {
        return $this->contactGroups()->where('groupable_type', SurveyCampaign::class);
    }
}
