<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SqlRequest extends Model
{
    //
    protected $fillable = ['user_id', 'conversation_id', 'sql_query', 'status', 'result'];
}
