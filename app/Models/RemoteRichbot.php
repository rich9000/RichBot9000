<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemoteRichbot extends Model
{
    use HasFactory;

    protected $fillable = [
        'remote_richbot_id',
        'name',
        'location',
        'status',
        'last_seen',
        'user_id'
    ];

    // Relationships
    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function commands()
    {
        return $this->hasMany(Command::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function mediaTriggers()
    {
        return $this->hasMany(MediaTrigger::class,'richbot_id');
    }
}
