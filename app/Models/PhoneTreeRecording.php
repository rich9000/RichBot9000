<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhoneTreeRecording extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'phone_tree_call_id',
        'recording_sid',
        'recording_url',
        'duration',
        'start_time',
        'end_time',
        'status',
        'metadata'
    ];

    protected $casts = [
        'duration' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'metadata' => 'json'
    ];

    public function call()
    {
        return $this->belongsTo(PhoneTreeCall::class, 'phone_tree_call_id');
    }
} 