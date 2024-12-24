<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodingSession extends Model
{


    protected $fillable = [
        'user_id', 'session_name', 'prompt', 'files', 'status'
    ];

    /**
     * Get the user that owns the coding session.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
