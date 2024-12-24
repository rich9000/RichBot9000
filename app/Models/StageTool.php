<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StageTool extends Model
{
    use HasFactory;

    protected $fillable = [
        'stage_id',
        'tool_id',
        'success_stage_id',
    ];

    // Define the relationship with Stage
    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    // Define the relationship with Tool
    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }

    // Define the optional success stage relationship
    public function successStage()
    {
        return $this->belongsTo(Stage::class, 'success_stage_id');
    }



}
