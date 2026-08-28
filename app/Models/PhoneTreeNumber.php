<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhoneTreeNumber extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'phone_tree_id',
        'phone_number',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function phoneTree()
    {
        return $this->belongsTo(PhoneTree::class);
    }
} 