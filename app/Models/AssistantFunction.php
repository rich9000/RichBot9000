<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssistantFunction extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'parameters',
        'code',
        'status',
        'version',
        'execution_count',
        'last_executed_at'
    ];
}
