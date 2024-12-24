<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketCategory extends Model
{
    use HasFactory;

    protected $table = 'ticket_categories';
    protected $primaryKey = 'category_id';

    protected $fillable = ['ticket_id', 'category_tag', 'confidence_score'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
