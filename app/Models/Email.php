<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $fillable = [
        'message_id',
        'parent_folder_id',
        'received_datetime',
        'body',
        'summary',
        'information',
        'subject',
        'to_contact_id',
        'from_contact_id',
        'project_id',
        'task_id',
        'user_id',
    ];

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'email_contacts')
            ->withPivot('context')
            ->withTimestamps();
    }

    public function fromContact()
    {
        return $this->belongsTo(Contact::class, 'from_contact_id');
    }

    public function toContact()
    {
        return $this->belongsTo(Contact::class, 'to_contact_id');
    }
}
