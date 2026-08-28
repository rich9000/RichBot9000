<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Script extends Model
{
    use HasFactory, SoftDeletes;

    const RETURN_TYPES = [
        // Basic types
        'string' => 'String',
        'number' => 'Number',
        'boolean' => 'Boolean',
        'object' => 'Object',
        'array' => 'Array',
        'null' => 'Null',
        
        // Twilio specific
        'twiml' => 'Twilio Markup Language',
        'audio_url' => 'Audio URL',
        'redirect' => 'Redirect URL',
        
        // Phone tree specific
        'phone_tree_id' => 'Phone Tree ID',
        'menu_id' => 'Menu ID',
        
        // User specific
        'user_id' => 'User ID',
        
        // Additional useful types
        'json' => 'JSON Response',
        'xml' => 'XML Response',
        'html' => 'HTML Response',
        'file_path' => 'File Path',
        'email' => 'Email Address',
        'phone_number' => 'Phone Number',
        'date' => 'Date',
        'datetime' => 'Date and Time',
        'time' => 'Time',
        'currency' => 'Currency Amount',
        'percentage' => 'Percentage',
        'coordinates' => 'Geographic Coordinates',
        'ip_address' => 'IP Address',
        'url' => 'URL',
        'image_url' => 'Image URL',
        'video_url' => 'Video URL',
        'document_url' => 'Document URL'
    ];

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'path',
        'parameters',
        'return_type',
        'description',
        'is_active',
        'execution_count',
        'last_executed_at'
    ];

    protected $casts = [
        'parameters' => 'array',
        'is_active' => 'boolean',
        'last_executed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the human-readable name for a return type
     *
     * @param string $type
     * @return string
     */
    public static function getReturnTypeName($type)
    {
        return self::RETURN_TYPES[$type] ?? $type;
    }

    /**
     * Get all available return types
     *
     * @return array
     */
    public static function getAvailableReturnTypes()
    {
        return self::RETURN_TYPES;
    }
} 