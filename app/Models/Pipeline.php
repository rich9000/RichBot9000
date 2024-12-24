<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pipeline extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function stages()
    {

        return $this->hasMany(Stage::class)
            ->with(['assistants', 'successTool'])
            ->orderBy('order');

    }

}
