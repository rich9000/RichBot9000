<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketRootCause extends Model
{
    use HasFactory;

    protected $table = 'ticket_root_causes';
    protected $primaryKey = 'root_cause_id';

    protected $fillable = ['ticket_id', 'cause_description', 'correlation_score'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
