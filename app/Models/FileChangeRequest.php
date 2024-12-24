<?php

// app/Models/FileChangeRequest.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'conversation_id',
        'file_path',
        'new_content',
        'original_content',
        'status'
    ];
}
