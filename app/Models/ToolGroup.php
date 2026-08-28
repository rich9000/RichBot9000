<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ToolGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'user_id'
    ];

    const TYPE_GLOBAL = 'global';
    const TYPE_PERSONAL = 'personal';
    const TYPE_TEAM = 'team';

    public static function getTypes()
    {
        return [
            self::TYPE_GLOBAL => 'Global',
            self::TYPE_PERSONAL => 'Personal',
            self::TYPE_TEAM => 'Team',
            self::TYPE_USER => 'User'
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'tool_tool_group');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tool_group_user');
    }
} 