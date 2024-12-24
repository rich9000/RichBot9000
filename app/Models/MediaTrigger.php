<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaTrigger extends Model
{
    protected $fillable = [
        'richbot_id',
        'type',
        'prompt',
        'action',
    ];

    public function richbot()
    {
        return $this->belongsTo(RemoteRichbot::class, 'richbot_id');
    }

    public function events()
    {
        return $this->hasMany(MediaTriggerEvent::class);
    }
}
