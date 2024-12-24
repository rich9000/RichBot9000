<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'all_day',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
        'all_day'    => 'boolean',
    ];

    /**
     * Get the user that owns the appointment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
