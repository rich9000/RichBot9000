<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parameter extends Model
{
    use HasFactory;

    protected $fillable = ['tool_id', 'name', 'type', 'description', 'required'];

    /**
     * Get the tool that owns the parameter.
     */
    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }

    /**
     * Get the options for the parameter.
     */
    public function options()
    {
        return $this->hasMany(ParameterOption::class);
    }

}
