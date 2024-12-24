<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketSentiment extends Model
{
    use HasFactory;

    protected $table = 'ticket_sentiments';
    protected $primaryKey = 'sentiment_id';

    protected $fillable = ['ticket_id', 'sentiment_score'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
