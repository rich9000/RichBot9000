<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CliRequest extends Model
{
    protected $fillable = ['user_id', 'conversation_id', 'command', 'parameters', 'status', 'output'];
}
