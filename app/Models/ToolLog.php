<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToolLog extends Model
{
    use HasFactory;

    protected $fillable = ['tool_id', 'event_type', 'details'];

    /**
     * Get the tool that owns the log.
     */
    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }
}
