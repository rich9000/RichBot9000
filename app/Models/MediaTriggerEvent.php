<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaTriggerEvent extends Model
{
    protected $fillable = [
        'media_trigger_id',
        'media_id',
        'action_taken',
        'details',
    ];

    public function trigger()
    {
        return $this->belongsTo(MediaTrigger::class, 'media_trigger_id');
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }
}
