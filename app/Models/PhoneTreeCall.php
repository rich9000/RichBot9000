<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhoneTreeCall extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'phone_tree_id',
        'call_sid',
        'from_number',
        'to_number',
        'start_time',
        'end_time',
        'status',
        'current_menu_id',
        'last_input',
        'websocket_connection_id',
        'metadata'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'metadata' => 'json'
    ];

    public function phoneTree()
    {
        return $this->belongsTo(PhoneTree::class);
    }

    public function currentMenu()
    {
        return $this->belongsTo(PhoneTreeMenu::class, 'current_menu_id');
    }

    public function recordings()
    {
        return $this->hasMany(PhoneTreeRecording::class);
    }

    public function transcriptions()
    {
        return $this->hasMany(PhoneTreeTranscription::class);
    }

    public function conversation()
    {
        return $this->hasOne(Conversation::class);
    }
} 