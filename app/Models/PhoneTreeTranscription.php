<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhoneTreeTranscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'phone_tree_call_id',
        'transcription_sid',
        'transcription_text',
        'language',
        'confidence',
        'status',
        'metadata'
    ];

    protected $casts = [
        'confidence' => 'float',
        'metadata' => 'json'
    ];

    public function call()
    {
        return $this->belongsTo(PhoneTreeCall::class, 'phone_tree_call_id');
    }
} 