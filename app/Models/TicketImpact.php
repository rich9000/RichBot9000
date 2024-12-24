<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketImpact extends Model
{
    use HasFactory;

    protected $table = 'ticket_impact';
    protected $primaryKey = 'impact_id';

    protected $fillable = ['ticket_id', 'impact_rating', 'affected_users', 'resolution_estimate'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
