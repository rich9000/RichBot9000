<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParameterOption extends Model
{
    use HasFactory;

    protected $fillable = ['parameter_id', 'value'];

    /**
     * Get the parameter that owns the option.
     */
    public function parameter()
    {
        return $this->belongsTo(Parameter::class);
    }
}
