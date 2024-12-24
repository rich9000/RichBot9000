<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventLog extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'event_type', 'description', 'data', 'loggable_type', 'loggable_id'];


    protected $casts = [
        'data' => 'array', // Cast the data attribute to an array
    ];

    /**
     * Get the user that owns the event log.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the owning loggable model.
     */
    public function loggable()
    {
        return $this->morphTo();
    }
}
