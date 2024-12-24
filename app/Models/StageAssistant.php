<?php


// StageAssistant.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StageAssistant extends Model
{
    protected $fillable = ['stage_id','success_stage_id','success_tool_id', 'assistant_id', 'order', 'created_at', 'updated_at'];



    public function successTool()
    {
        return $this->belongsTo(Tool::class, 'success_tool_id');
    }

    public function successStage()
    {
        return $this->belongsTo(Stage::class, 'success_stage_id');
    }


}
