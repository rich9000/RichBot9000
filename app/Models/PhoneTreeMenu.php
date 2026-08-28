<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhoneTreeMenu extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'phone_tree_id',
        'parent_menu_id',
        'name',
        'description',
        'prompt_message',
        'timeout_message',
        'invalid_input_message',
        'max_retries',
        'timeout_seconds',
        'order',
        'is_active',
        'welcome_audio_id',
        'welcome_message',
        'prompt_audio_id',
        'finish_audio_id',
        'finish_message',
        'finish_menu_id',
        'websocket_id',
        'disconnect_on_finish',
        'transfer_number',
        'websocket_fail_menu_id',
        'script_id',
        'assistant_id',
        'pipeline_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'disconnect_on_finish' => 'boolean',
        'max_retries' => 'integer',
        'timeout_seconds' => 'integer',
        'order' => 'integer'
    ];

    public function phoneTree()
    {
        return $this->belongsTo(PhoneTree::class);
    }

    public function parentMenu()
    {
        return $this->belongsTo(PhoneTreeMenu::class, 'parent_menu_id');
    }

    public function childMenus()
    {
        return $this->hasMany(PhoneTreeMenu::class, 'parent_menu_id');
    }

    public function options()
    {
        return $this->hasMany(PhoneTreeOption::class, 'phone_tree_menu_id');
    }

    public function welcomeAudio()
    {
        return $this->belongsTo(AudioFile::class, 'welcome_audio_id');
    }

    public function promptAudio()
    {
        return $this->belongsTo(AudioFile::class, 'prompt_audio_id');
    }

    public function finishAudio()
    {
        return $this->belongsTo(AudioFile::class, 'finish_audio_id');
    }

    public function finishMenu()
    {
        return $this->belongsTo(PhoneTreeMenu::class, 'finish_menu_id');
    }

    public function websocket()
    {
        return $this->belongsTo(PhoneTreeWebsocket::class, 'websocket_id');
    }

    public function websocketFailMenu()
    {
        return $this->belongsTo(PhoneTreeMenu::class, 'websocket_fail_menu_id');
    }

    public function calls()
    {
        return $this->hasMany(PhoneTreeCall::class, 'current_menu_id');
    }

    public function assistant()
    {
        return $this->belongsTo(Assistant::class, 'assistant_id');
    }

    public function script()
    {
        return $this->belongsTo(PhoneTreeScript::class, 'script_id');
    }

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class, 'pipeline_id');
    }
} 