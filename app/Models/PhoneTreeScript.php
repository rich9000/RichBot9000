<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhoneTreeScript extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'phone_tree_id',
        'name',
        'description',
        'path',
        'parameters',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'parameters' => 'array'
    ];

    public function phoneTree()
    {
        return $this->belongsTo(PhoneTree::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
} 