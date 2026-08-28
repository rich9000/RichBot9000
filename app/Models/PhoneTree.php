<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhoneTree extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'welcome_message',
        'welcome_audio_id',
        'timeout_message',
        'invalid_input_message',
        'max_retries',
        'timeout_seconds',
        'is_active',
        'is_default',
        'root_menu_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'max_retries' => 'integer',
        'timeout_seconds' => 'integer'
    ];

    public function numbers()
    {
        return $this->hasMany(PhoneTreeNumber::class);
    }

    public function menus()
    {
        return $this->hasMany(PhoneTreeMenu::class);
    }

    public function calls()
    {
        return $this->hasMany(PhoneTreeCall::class);
    }

    public function websockets()
    {
        return $this->hasMany(PhoneTreeWebsocket::class);
    }

    public function scripts()
    {
        return $this->hasMany(PhoneTreeScript::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function rootMenu()
    {
        return $this->belongsTo(PhoneTreeMenu::class, 'root_menu_id');
    }

    public function welcomeAudio()
    {
        return $this->belongsTo(AudioFile::class, 'welcome_audio_id');
    }
} 