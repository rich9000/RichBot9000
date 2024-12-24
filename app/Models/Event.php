<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'remote_richbot_id',
        'event_type',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function remoteRichbot()
    {
        return $this->belongsTo(RemoteRichbot::class);
    }
}
