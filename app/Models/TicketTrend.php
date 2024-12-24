<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketTrend extends Model
{
    use HasFactory;

    protected $table = 'ticket_trends';
    protected $primaryKey = 'trend_id';

    protected $fillable = ['category_tag', 'frequency', 'last_detected'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

}
