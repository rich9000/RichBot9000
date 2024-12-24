<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    //protected $fillable = ['pipeline_id', 'type', 'assistant_id','success_tool_id', 'order', 'config'];
    protected $fillable = ['name','pipeline_id', 'type', 'success_tool_id', 'order', 'config', 'created_at', 'updated_at'];
    protected $casts = [
        'config' => 'array',
    ];

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function assistants()
    {
        return $this->belongsToMany(Assistant::class, 'stage_assistants', 'stage_id', 'assistant_id')
            ->withPivot('order', 'success_stage_id', 'success_tool_id')
            ->withTimestamps();
    }





    public function successStage()
    {
        return $this->belongsTo(Stage::class, 'success_stage_id');
    }

    public function successTool()
    {
        return $this->belongsTo(Tool::class, 'success_tool_id');
    }

    public function files()
    {
        return $this->hasMany(StageFile::class);
    }

    // One-to-Many: A stage can have multiple tools associated with it
    public function tools()
    {
        return $this->hasMany(StageTool::class);
    }

// Relationship for pivot table overrides (stage_assistant's success_tool_id and success_stage_id)
    public function assistantSuccessTool()
    {
        return $this->belongsToMany(Tool::class, 'stage_assistants', 'stage_id', 'success_tool_id')
            ->withPivot('success_stage_id', 'success_tool_id', 'order');
    }

    public function assistantSuccessStage()
    {
        return $this->belongsToMany(Stage::class, 'stage_assistants', 'stage_id', 'success_stage_id')
            ->withPivot('success_stage_id', 'success_tool_id', 'order');
    }

}
