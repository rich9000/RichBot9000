<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StageFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'stage_id',
        'file_path',
        'file_type',
        'description',
    ];

    // Define the relationship with Stage
    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }
}
