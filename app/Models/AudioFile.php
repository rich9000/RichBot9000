<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AudioFile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'file_path',
        'file_type',
        'duration',
        'source_type',
        'type',
        'context',
        'metadata',
        'is_active',
        'user_id',
        'file_size',
        'bitrate',
        'sample_rate',
        'channels',
        'transcription',
        'tags'
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'duration' => 'integer',
        'file_size' => 'integer',
        'bitrate' => 'integer',
        'sample_rate' => 'integer',
        'channels' => 'integer',
        'tags' => 'array'
    ];

    // Define the available types
    const TYPE_GENERAL = 'general';
    const TYPE_PHONE_TREE = 'phone-tree';
    const TYPE_USER = 'user';
    const TYPE_SYSTEM = 'system';
    const TYPE_MEMO = 'memo';
    const TYPE_STREAM = 'stream';

    // Add user relationship
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFullPathAttribute()
    {
        return storage_path('app/' . $this->file_path);
    }

    public function getPublicUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }

    public function getUrlAttribute()
    {
        return url('api/audio-files/' . $this->id . '/serve');
    }

    // Helper method to get all available types
    public static function getAvailableTypes()
    {
        return [
            self::TYPE_GENERAL => 'General',
            self::TYPE_PHONE_TREE => 'Phone Tree',
            self::TYPE_USER => 'User',
            self::TYPE_SYSTEM => 'System',
            self::TYPE_MEMO => 'Memo',
            self::TYPE_STREAM => 'Stream'
        ];
    }
} 