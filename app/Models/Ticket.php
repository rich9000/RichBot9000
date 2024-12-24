<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    protected $table = 'ticket_tickets';
    protected $primaryKey = 'id';

    // Add 'raw_data' to the fillable array
    protected $fillable = [
        'raw_data',
        'order_number',
        'file_name',
        'start_date',
        'complete_date',
        'status',
        'account_number',
        'customer_name',
        'service_address',
        'contact_number',
        'email',
        'product_type',
        'service_type',
        'connect_date',
        'disconnect_date',
        'equipment',
        'technician_name',
        'install_notes',
        'drop_type',
        'issues_reported',
        'resolution_notes',
        'billing_amount',
        'promotions',
        'monthly_charge',
        'fractional_charge',
        'prorated_charge',
        'warnings',
        'comments',
        'latitude',
        'longitude',
        'ssid',
        'password'
    ];

    public function summaries() {
        return $this->hasMany(TicketSummary::class, 'ticket_id');
    }

    public function categories() {
        return $this->hasMany(TicketCategory::class, 'ticket_id');
    }

    public function impacts() {
        return $this->hasMany(TicketImpact::class, 'ticket_id');
    }

    public function rootCauses() {
        return $this->hasMany(TicketRootCause::class, 'ticket_id');
    }

    public function sentiments() {
        return $this->hasMany(TicketSentiment::class, 'ticket_id');
    }
}
