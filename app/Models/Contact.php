<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'type',
        'opt_in_at'
    ];

    protected $casts = [
        'opt_in_at' => 'datetime',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_contacts')
            ->withPivot('context', 'allowed_to_contact','name')
            ->withTimestamps();
    }
 
    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
