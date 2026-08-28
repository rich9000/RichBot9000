<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhoneTreeWebsocket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'phone_tree_id',
        'endpoint_url',
        'connection_type',
        'authentication_type',
        'authentication_credentials',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'authentication_credentials' => 'json',
        'metadata' => 'json'
    ];

    public function phoneTree()
    {
        return $this->belongsTo(PhoneTree::class);
    }

    public function calls()
    {
        return $this->hasMany(PhoneTreeCall::class, 'websocket_connection_id');
    }
} 