<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['name', 'description'];

    // Relationships
    public function tasks()
    {
        return $this->hasMany(Task::class)->orderBy('order');
    }
}