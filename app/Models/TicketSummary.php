<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketSummary extends Model
{
    use HasFactory;
    protected $table = 'ticket_summaries';
    protected $primaryKey = 'summary_id';

    protected $fillable = ['ticket_id', 'summary_text', 'assistant_name'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
