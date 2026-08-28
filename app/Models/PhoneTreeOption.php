<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\PhoneTreeScript;
use App\Models\PhoneTreeMenu;
use App\Models\AudioFile;
use App\Models\PhoneTreeWebsocket;
use App\Models\PhoneTreeNumber;
use App\Models\Assistant;

class PhoneTreeOption extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'phone_tree_menu_id',
        'digit',
        'action_type',
        'target_id',
        'description',
        'order',
        'is_active',
        'welcome_message',
        'welcome_audio_id',
        'finish_menu_id',
        'assistant_id',
        'pipeline_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer'
    ];

    
    public function menu()
    {
        return $this->belongsTo(PhoneTreeMenu::class, 'phone_tree_menu_id');
    }

    public function welcomeAudio()
    {
        return $this->belongsTo(AudioFile::class, 'welcome_audio_id');
    }

    public function finishMenu()
    {
        return $this->belongsTo(PhoneTreeMenu::class, 'finish_menu_id');
    }

    public function target()
    {
        $targetClass = match($this->action_type) {
            'menu' => PhoneTreeMenu::class,
            'audio_file' => AudioFile::class,
            'script' => PhoneTreeScript::class,
            'websocket' => PhoneTreeWebsocket::class,
            'number' => PhoneTreeNumber::class,
            'assistant' => Assistant::class,
            default => PhoneTreeMenu::class,
        };

        return $this->belongsTo($targetClass, 'target_id');
    }

    public function assistant()
    {
        return $this->belongsTo(Assistant::class, 'assistant_id');
    }

    /**
     * Get the display name of the target based on action type
     */
    public function getTargetName()
    {
        if (!$this->target) {
            return 'Unknown';
        }

        switch ($this->action_type) {
            case 'menu':
                return $this->target->name ?? 'Unknown Menu';
            case 'websocket':
                return $this->target->endpoint_url ?? 'Unknown WebSocket';
            case 'script':
                return $this->target->name ?? 'Unknown Script';
            case 'number':
                return $this->target->phone_number ?? 'Unknown Number';
            case 'audio_file':
                return $this->target->name ?? 'Unknown Audio';
            case 'assistant':
                return $this->target->name ?? 'Unknown Assistant';
            default:
                return 'Unknown Target';
        }
    }

    /**
     * Get the target description if available
     */
    public function getTargetDescription()
    {
        if (!$this->target) {
            return null;
        }

        switch ($this->action_type) {
            case 'script':
            case 'menu':
                return $this->target->description;
            default:
                return null;
        }
    }

    // Convenience methods for accessing specific target types
    public function targetMenu()
    {
        return $this->belongsTo(PhoneTreeMenu::class, 'target_id')
            ->when($this->action_type === 'menu', function($query) {
                return $query;
            });
    }

    public function targetAudio()
    {
        return $this->belongsTo(AudioFile::class, 'target_id')
            ->when($this->action_type === 'audio_file', function($query) {
                return $query;
            });
    }

    public function targetScript()
    {
        return $this->belongsTo(PhoneTreeScript::class, 'target_id')
            ->when($this->action_type === 'script', function($query) {
                return $query;
            });
    }

    public function targetWebSocket()
    {
        return $this->belongsTo(PhoneTreeWebsocket::class, 'target_id')
            ->when($this->action_type === 'websocket', function($query) {
                return $query;
            });
    }

    public function targetNumber()
    {
        return $this->belongsTo(PhoneTreeNumber::class, 'target_id')
            ->when($this->action_type === 'number', function($query) {
                return $query;
            });
    }
} 