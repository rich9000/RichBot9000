<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    protected $table = 'models';

    protected $fillable = [
        'type',
        'name',
    ];

    // If you want to use timestamps (created_at, updated_at), keep this as is.
    public $timestamps = true;
}
