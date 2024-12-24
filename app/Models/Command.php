<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Command extends Model
{
    use HasFactory;

    protected $fillable = [
        'remote_richbot_id',
        'command',
        'parameters',
        'status',
        'user_id',
        'richbot_id'

    ];

    protected $casts = [
        'parameters' => 'array',
    ];

    public function remoteRichbot()
    {
        return $this->belongsTo(RemoteRichbot::class);
    }
}
