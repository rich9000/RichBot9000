<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsMessage extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'from_number',
        'to_number',
        'body',
        'direction',
        'status',
    ];

    /**
     * Get the user associated with the SMS message.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
