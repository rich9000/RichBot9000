<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailContact extends Model
{
    protected $fillable = [
        'email_id',
        'contact_id',
        'context',
    ];

    public function email()
    {
        return $this->belongsTo(Email::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
