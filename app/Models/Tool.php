<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    protected $fillable = [
        'name',
        'description',
        'strict',
        'method',
        'summary',
        'operation_id',

        'responses',
    ];

    protected $casts = [

        'responses' => 'array',
    ];

// Relationship with Tools (many-to-many relationship)
    public function assistants()
    {
        return $this->belongsToMany(Assistant::class, 'assistant_tool');
    }

    public function ollamaParameters()
    {
        return $this->hasMany(Parameter::class);
    }


    public function parameters()
    {
        return $this->hasMany(Parameter::class);
    }

    /**
     * Get the logs for the tool.
     */
    public function logs()
    {
        return $this->hasMany(ToolLog::class);
    }

    public function stageTools()
    {
        return $this->hasMany(StageTool::class);

    }


}
